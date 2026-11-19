<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use App\Models\LekukaReceipt;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ReceiptService
{
    public function __construct(
        protected LekukaClient $client,
        protected FiscalPeriodService $fiscal,
        protected ReceiptPayloadBuilder $builder,
    ) {
    }

    /**
     * Submit a sale to Lekuka and fiscalize it.
     *
     * Important:
     *
     * 1. The receipt number is allocated locally first.
     * 2. A PENDING receipt is persisted.
     * 3. The database transaction commits.
     * 4. Only then do we call Lekuka.
     *
     * This prevents a failed HTTP/API request from rolling back
     * the fiscal counter allocation.
     */
    public function submit(
        Sale $sale,
        LekukaDevice $device,
    ): Sale {

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate fiscalization of the same sale
        |--------------------------------------------------------------------------
        */

        // Inspect every existing receipt for this sale. FAILED receipts must
        // not be ignored: the gateway may have accepted a request even when the
        // local process did not receive or persist its response.
        $existingReceipt = LekukaReceipt::query()
            ->where('sale_id', $sale->id)
            ->latest('id')
            ->first();

        if ($existingReceipt) {
            if ($existingReceipt->status === 'SUBMITTED') {
                return $sale->fresh();
            }

            if ($existingReceipt->status === 'PENDING') {
                // Reuse the exact persisted payload and fiscal numbers.
                return $this->submitPendingReceipt(
                    receipt: $existingReceipt,
                    sale: $sale,
                    device: $device,
                );
            }

            if ($existingReceipt->status === 'FAILED') {
                throw new \RuntimeException(
                    "Sale {$sale->id} already has FAILED Lekuka receipt " .
                    "{$existingReceipt->id}. Do not allocate another fiscal " .
                    "number until the existing receipt has been reconciled " .
                    "against Lekuka."
                );
            }

            throw new \RuntimeException(
                "Sale {$sale->id} has an existing Lekuka receipt in " .
                "unsupported status '{$existingReceipt->status}'. " .
                "Manual reconciliation is required."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Phase 1
        |
        | Allocate the fiscal number and create a PENDING receipt.
        |
        | This transaction must finish BEFORE we contact Lekuka.
        |--------------------------------------------------------------------------
        */

        $receipt = DB::transaction(function () use ($sale, $device) {

            // Serialize fiscalization attempts for the same sale, even if two
            // requests arrive through different devices/processes.
            Sale::query()
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Lock the device row
            |
            | This prevents two simultaneous cashiers/processes from
            | receiving the same receiptCounter/global number.
            |--------------------------------------------------------------------------
            */

            $lockedDevice = LekukaDevice::query()
                ->whereKey($device->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Recheck after acquiring the device lock. Two concurrent calls
            // may both pass the initial lookup; the lock serializes allocation,
            // and this second lookup prevents the later call from allocating a
            // second fiscal number for the same sale.
            $concurrentReceipt = LekukaReceipt::query()
                ->where('sale_id', $sale->id)
                ->latest('id')
                ->first();

            if ($concurrentReceipt) {
                throw new \RuntimeException(
                    "Sale {$sale->id} already has Lekuka receipt " .
                    "{$concurrentReceipt->id} (status: " .
                    "{$concurrentReceipt->status}). No new fiscal number " .
                    "was allocated. Re-run the operation to reuse or " .
                    "reconcile that receipt."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Ensure fiscal day is open
            |--------------------------------------------------------------------------
            */

            $day = $this->fiscal->ensureOpen($lockedDevice);

            /*
            |--------------------------------------------------------------------------
            | Refresh device after fiscal synchronization
            |--------------------------------------------------------------------------
            */

            $lockedDevice->refresh();

            /*
            |--------------------------------------------------------------------------
            | Build receipt payload
            |
            | ReceiptPayloadBuilder currently calculates:
            |
            | last_receipt_counter + 1
            | last_global_receipt_no + 1
            |
            | We therefore build the payload while the device row is
            | locked, BEFORE incrementing the counters.
            |--------------------------------------------------------------------------
            */
            // Get the latest receipt counter for THIS fiscal day only.
            $lastDayReceiptCounter = LekukaReceipt::query()
                ->where('device_id', $lockedDevice->id)
                ->where('fiscal_day_no', (string) $day->fiscal_day_no)
                ->whereNotNull('receipt_counter')
                ->orderByRaw('CAST(receipt_counter AS UNSIGNED) DESC')
                ->value('receipt_counter');

            // Counter resets automatically when a new fiscal day has no receipts.
            // Preserve independently calculated expected values. Do not
            // derive the expected value from the builder output being checked.
            $expectedCounter = ((int) $lastDayReceiptCounter) + 1;

            // Global receipt number continues across all fiscal days.
            $expectedGlobalNo = ((int) $lockedDevice->last_global_receipt_no) + 1;

            $receiptData = $this->builder->build(
                sale: $sale,
                day: $day,
                device: $lockedDevice,
                receiptCounter: $expectedCounter,
                receiptGlobalNo: $expectedGlobalNo,
            );

            $payload = $receiptData['payload'];

            $signature = $receiptData['signature'];

            /*
            |--------------------------------------------------------------------------
            | Extract fiscal numbers from the actual payload
            |--------------------------------------------------------------------------
            */

            $actualReceiptCounter = $payload['receipt']['receiptCounter']
                ?? null;

            $actualReceiptGlobalNo = $payload['receipt']['receiptGlobalNo']
                ?? null;

            $invoiceNo = $payload['receipt']['invoiceNo']
                ?? $sale->sale_number;

            if ($actualReceiptCounter === null) {
                throw new \RuntimeException(
                    'Receipt counter was not generated by ReceiptPayloadBuilder.'
                );
            }

            if ($actualReceiptGlobalNo === null) {
                throw new \RuntimeException(
                    'Receipt global number was not generated by ReceiptPayloadBuilder.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate counter allocation
            |--------------------------------------------------------------------------
            */

            if ((int) $actualReceiptCounter !== $expectedCounter) {
                throw new \RuntimeException(
                    "Receipt counter mismatch. Expected {$expectedCounter}, " .
                    "got {$actualReceiptCounter}."
                );
            }

            if ((int) $actualReceiptGlobalNo !== $expectedGlobalNo) {
                throw new \RuntimeException(
                    "Receipt global number mismatch. Expected {$expectedGlobalNo}, " .
                    "got {$actualReceiptGlobalNo}."
                );
            }

            // Use the validated payload values for persistence below.
            $receiptCounter = (int) $actualReceiptCounter;
            $receiptGlobalNo = (int) $actualReceiptGlobalNo;

            /*
            |--------------------------------------------------------------------------
            | Create PENDING local receipt
            |--------------------------------------------------------------------------
            |
            | We deliberately save the receipt BEFORE submitting to Lekuka.
            |
            | If Lekuka times out after receiving the receipt, we still know
            | exactly which fiscal receipt must be retried.
            |--------------------------------------------------------------------------
            */

            $receipt = new LekukaReceipt();

            $receipt->forceFill([

                'company_id' => $sale->company_id,

                'branch_id' => $sale->branch_id,

                /*
                 * Local FK to lekuka_devices.id
                 */
                'device_id' => $lockedDevice->id,

                'sale_id' => $sale->id,

                'correlation_id' => (string) Str::uuid(),

                /*
                 * Local POS receipt/invoice number.
                 */
                'receipt_number' => $invoiceNo,

                /*
                 * Fiscal values generated in the payload.
                 */
                'receipt_global_no' => (string) $receiptGlobalNo,

                'receipt_counter' => (string) $receiptCounter,

                'fiscal_day_no' => (string) $day->fiscal_day_no,

                /*
                 * Lekuka-specific reference fields are not known
                 * until the API responds.
                 */
                'ReceiptId' => null,

                'InvoiceNo' => null,

                'DeviceId' => $lockedDevice->device_id,

                /*
                 * Device signature information.
                 */
                'device_hash' => $signature['hash'] ?? null,

                'device_signature' => $signature['signature'] ?? null,

                /*
                 * Save the exact request that will be submitted.
                 *
                 * Your model casts request to array, so assigning the
                 * array is appropriate.
                 */
                'request' => $payload,

                'status' => 'PENDING',

                'submitted_at' => null,

            ]);

            $receipt->save();

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            |
            | Advance the local counters BEFORE committing this transaction.
            |
            | If the external Lekuka request later fails, these values stay
            | allocated because this transaction has already committed.
            |--------------------------------------------------------------------------
            */

            $lockedDevice->last_receipt_counter =
                (int) $receiptCounter;

            $lockedDevice->last_global_receipt_no =
                (int) $receiptGlobalNo;

            $lockedDevice->save();

            return $receipt->fresh();
        });

        /*
        |--------------------------------------------------------------------------
        | Phase 2
        |
        | Submit the exact PENDING receipt to Lekuka.
        |--------------------------------------------------------------------------
        */

        return $this->submitPendingReceipt(
            receipt: $receipt,
            sale: $sale,
            device: $device,
        );
    }

    /**
     * Submit an existing PENDING receipt.
     *
     * This method NEVER generates a new receipt counter.
     * It always reuses the exact payload already stored in the
     * PENDING receipt.
     */
    protected function submitPendingReceipt(
        LekukaReceipt $receipt,
        Sale $sale,
        LekukaDevice $device,
    ): Sale {

        /*
        |--------------------------------------------------------------------------
        | If already submitted, nothing to do.
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate submission
        |--------------------------------------------------------------------------
        */

        if ($receipt->status === 'SUBMITTED') {
            return $sale->fresh();
        }

        $previousResponse = $receipt->response ?? [];

        if (is_string($previousResponse)) {
            $previousResponse = json_decode($previousResponse, true) ?? [];
        }

        $previousErrors = $previousResponse['validationErrors'] ?? [];

        if (! is_array($previousErrors)) {
            $previousErrors = [];
        }

        $previousBlockingErrors = collect($previousErrors)
            ->filter(fn ($error) =>
                ($error['validationErrorColor'] ?? null) !== 'Yellow'
            );

        if ($previousBlockingErrors->isNotEmpty()) {
            throw new \RuntimeException(
                "Receipt {$receipt->id} has previously received blocking "
                . "Lekuka validation errors. It must not be resubmitted."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate stored request
        |--------------------------------------------------------------------------
        */

        $payload = $receipt->request;

        if (is_string($payload)) {

            $payload = json_decode(
                $payload,
                true
            );
        }

        if (! is_array($payload)) {

            throw new \RuntimeException(
                "Stored Lekuka request for receipt {$receipt->id} " .
                "is invalid."
            );
        }

        if (
            ! isset($payload['receipt']) ||
            ! is_array($payload['receipt'])
        ) {

            throw new \RuntimeException(
                "Stored Lekuka request for receipt {$receipt->id} " .
                "does not contain a receipt payload."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Extract values from the persisted payload
        |--------------------------------------------------------------------------
        */

        $receiptCounter =
            $payload['receipt']['receiptCounter'] ?? null;

        $receiptGlobalNo =
            $payload['receipt']['receiptGlobalNo'] ?? null;

        $invoiceNo =
            $payload['receipt']['invoiceNo']
            ?? $sale->sale_number;

        if ($receiptCounter === null) {

            throw new \RuntimeException(
                "Receipt {$receipt->id} has no receiptCounter."
            );
        }

        if ($receiptGlobalNo === null) {

            throw new \RuntimeException(
                "Receipt {$receipt->id} has no receiptGlobalNo."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Use the correlation ID already allocated to this receipt.
        |--------------------------------------------------------------------------
        */

        $correlationId =
            $receipt->correlation_id
            ?: (string) Str::uuid();

        if (! $receipt->correlation_id) {

            $receipt->forceFill([
                'correlation_id' => $correlationId,
            ])->save();
        }

        /*
        |--------------------------------------------------------------------------
        | Submit to Lekuka
        |--------------------------------------------------------------------------
        */

        try {

            $response = $this->client->securePost(

                device: $device,

                endpoint:
                    "/Device/v2/{$device->device_id}/SubmitReceipt",

                payload: $payload,

                action: 'SUBMIT_RECEIPT',

                correlationId: $correlationId,

            );

            /*
            |--------------------------------------------------------------------------
            | Handle HTTP response
            |--------------------------------------------------------------------------
            |
            | Do not mark the receipt SUBMITTED until the response has been
            | successfully interpreted.
            |--------------------------------------------------------------------------
            */

            $data = $response->json();

            /*
            |--------------------------------------------------------------------------
            | Lekuka may return validation information together with receiptID.
            |
            | We still store the complete response. We do NOT silently discard
            | validationErrors.
            |--------------------------------------------------------------------------
            */

            if (! $response->successful()) {

                $errorMessage = $this->extractErrorMessage($data);

                $httpStatus = $response->status();

                /*
                |--------------------------------------------------------------------------
                | Distinguish definitive rejection from uncertain outcome.
                |--------------------------------------------------------------------------
                |
                | HTTP 408 and 5xx responses may occur after the gateway has
                | received the submission. Preserve the original receipt as PENDING.
                |
                | Other unsuccessful responses are treated as definitive rejection.
                |--------------------------------------------------------------------------
                */

                $ambiguousResponse =
                    $httpStatus === 408 ||
                    $httpStatus >= 500;

                $receipt->forceFill([

                    'status' => $ambiguousResponse
                        ? 'PENDING'
                        : 'FAILED',

                    'response' => $data,

                    'error_message' => $errorMessage,

                ])->save();

                $sale->update([

                    'submitted_to_lekuka' => false,

                ]);

                $response->throw();
            }
            /*
            |--------------------------------------------------------------------------
            | Extract Lekuka response values
            |--------------------------------------------------------------------------
            */

            $lekukaReceiptId =
                $data['receiptID']
                ?? null;

            $serverSignature =
                $data['receiptServerSignature']
                ?? null;

            /*
            |--------------------------------------------------------------------------
            | Inspect Lekuka validation errors
            |--------------------------------------------------------------------------
            */

            $validationErrors = $data['validationErrors'] ?? [];

            if (! is_array($validationErrors)) {
                $validationErrors = [];
            }

            $blockingErrors = [];

            foreach ($validationErrors as $validationError) {

                $color = $validationError['validationErrorColor'] ?? null;

                // Only Yellow is treated as a non-blocking warning.
                if ($color !== 'Yellow') {
                    $blockingErrors[] = $validationError;
                }
            }

            if (
                ! empty($blockingErrors) ||
                empty($lekukaReceiptId) ||
                empty($serverSignature)
            ) {

                $errorMessage = ! empty($blockingErrors)
                    ? 'Lekuka rejected the receipt due to blocking validation errors: '
                        . collect($blockingErrors)
                            ->map(fn ($error) =>
                                ($error['validationErrorCode'] ?? 'UNKNOWN')
                                . ' - '
                                . ($error['validationErrorDescription'] ?? 'Unknown error')
                            )
                            ->implode('; ')
                    : 'Lekuka response did not contain a valid receipt ID and server signature.';

                $receipt->forceFill([

                    'ReceiptId' => $lekukaReceiptId,

                    'server_signature' => $serverSignature,

                    'response' => $data,

                    'status' => 'FAILED',

                    'error_message' => $errorMessage,

                ])->save();

                $sale->update([

                    'submitted_to_lekuka' => false,

                ]);

                throw new \RuntimeException($errorMessage);
            }

            /*
            |--------------------------------------------------------------------------
            | Update fiscal receipt
            |--------------------------------------------------------------------------
            */

            $receipt->forceFill([

                /*
                 * Lekuka-generated receipt ID.
                 */
                'ReceiptId' => $lekukaReceiptId,

                /*
                 * Invoice number as known by Lekuka.
                 */
                'InvoiceNo' => $invoiceNo,

                /*
                 * Lekuka device ID.
                 */
                'DeviceId' => $device->device_id,

                /*
                 * Keep our local POS receipt number.
                 */
                'receipt_number' => $invoiceNo,

                /*
                 * Fiscal numbers are taken from the ORIGINAL submitted
                 * payload, not regenerated.
                 */
                'receipt_global_no' => (string) $receiptGlobalNo,

                'receipt_counter' => (string) $receiptCounter,

                /*
                 * Keep server signature returned by Lekuka.
                 */
                'server_signature' => $serverSignature,

                /*
                 * Optional fields if Lekuka returns them.
                 */
                'qr_code' =>
                    $data['qrCode']
                    ?? $receipt->qr_code,

                'verification_code' =>
                    $data['verificationCode']
                    ?? $receipt->verification_code,

                /*
                 * Store complete response.
                 */
                'response' => $data,

                'status' => 'SUBMITTED',

                'error_message' => null,

                'submitted_at' => now(),

            ])->save();

            /*
            |--------------------------------------------------------------------------
            | Update Sale
            |--------------------------------------------------------------------------
            */

            $sale->update([

                'submitted_to_lekuka' => true,

                'lekuka_receipt_id' => $receipt->id,

                'qr_code' => $receipt->qr_code,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Refresh local fiscal-day status
            |--------------------------------------------------------------------------
            |
            | Lekuka advances lastReceiptGlobalNo after accepting the receipt.
            | Keep the local fiscal-day snapshot synchronized with Lekuka.
            |
            | This is deliberately best-effort. The receipt has already been
            | successfully submitted, so failure of this follow-up synchronization
            | must NOT turn the receipt into FAILED.
            |
            */

            try {

                $status = $this->fiscal->status($device);

                $this->fiscal->sync(
                    device: $device,
                    status: $status,
                );

            } catch (Throwable $syncException) {

                \Log::warning(
                    'Lekuka fiscal-day synchronization failed after receipt submission.',
                    [
                        'receipt_id' => $receipt->id,
                        'sale_id' => $sale->id,
                        'device_id' => $device->device_id,
                        'receipt_global_no' => $receipt->receipt_global_no,
                        'error' => $syncException->getMessage(),
                    ]
                );
            }

            return $sale->fresh();

        } catch (Throwable $e) {

            // Once Lekuka has confirmed acceptance, never downgrade the local
            // receipt to PENDING/FAILED. A subsequent local Sale update failure
            // is a reconciliation issue, not a reason to resubmit the receipt.
            if ($receipt->status === 'SUBMITTED') {
                \Log::error(
                    'Lekuka accepted the receipt, but a post-submission local operation failed.',
                    [
                        'receipt_id' => $receipt->id,
                        'sale_id' => $sale->id,
                        'device_id' => $device->device_id,
                        'error' => $e->getMessage(),
                    ]
                );

                throw $e;
            }

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            |
            | The receipt already exists as PENDING and its fiscal number has
            | already been allocated.
            |
            | We DO NOT generate another receipt number here.
            |--------------------------------------------------------------------------
            */

            if ($receipt->status !== 'FAILED') {

                $receipt->forceFill([

                    'status' => 'PENDING',

                    'error_message' => $e->getMessage(),

                ])->save();
            }

            $sale->update([

                'submitted_to_lekuka' => false,

            ]);

            throw $e;
        }
    }

    /**
     * Extract a useful error message from a Lekuka response.
     */
    protected function extractErrorMessage(
        mixed $data
    ): string {

        if (! is_array($data)) {

            return 'Lekuka submission failed.';
        }

        /*
        |--------------------------------------------------------------------------
        | Standard error message
        |--------------------------------------------------------------------------
        */

        if (! empty($data['message'])) {
            return (string) $data['message'];
        }

        if (! empty($data['errorMessage'])) {
            return (string) $data['errorMessage'];
        }

        /*
        |--------------------------------------------------------------------------
        | Validation errors
        |--------------------------------------------------------------------------
        */

        if (
            isset($data['validationErrors']) &&
            is_array($data['validationErrors'])
        ) {

            $messages = [];

            foreach ($data['validationErrors'] as $error) {

                if (! is_array($error)) {
                    continue;
                }

                $code =
                    $error['validationErrorCode']
                    ?? null;

                $description =
                    $error['validationErrorDescription']
                    ?? null;

                if ($code && $description) {

                    $messages[] =
                        "{$code}: {$description}";

                } elseif ($description) {

                    $messages[] = $description;
                }
            }

            if ($messages) {

                return implode(
                    '; ',
                    $messages
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Generic JSON fallback
        |--------------------------------------------------------------------------
        */

        return 'Lekuka submission failed: ' .
            json_encode(
                $data,
                JSON_UNESCAPED_SLASHES
            );
    }

    public function retry(
        LekukaReceipt $receipt,
        LekukaDevice $device,
    ): Sale {

        /*
        |--------------------------------------------------------------------------
        | Never retry an already submitted receipt
        |--------------------------------------------------------------------------
        */

        if ($receipt->status === 'SUBMITTED') {
            return $receipt->sale->fresh();
        }

        /*
        |--------------------------------------------------------------------------
        | Only PENDING receipts may be retried automatically.
        | FAILED receipts require reconciliation first because the gateway
        | may have accepted a request before the local failure was recorded.
        |--------------------------------------------------------------------------
        */

        if ($receipt->status === 'FAILED') {
            throw new \RuntimeException(
                "Receipt {$receipt->id} is FAILED. Reconcile its status with " .
                "Lekuka before attempting any further submission."
            );
        }

        if ($receipt->status !== 'PENDING') {
            throw new \RuntimeException(
                "Receipt {$receipt->id} cannot be retried from status " .
                "'{$receipt->status}'."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Make sure this receipt belongs to this device
        |--------------------------------------------------------------------------
        */

        if ((int) $receipt->device_id !== (int) $device->id) {

            throw new \RuntimeException(
                "Receipt {$receipt->id} does not belong to device {$device->id}."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Never generate a new fiscal number here.
        |
        | submitPendingReceipt() uses the persisted payload.
        |--------------------------------------------------------------------------
        */

        return $this->submitPendingReceipt(
            receipt: $receipt,
            sale: $receipt->sale,
            device: $device,
        );
    }
}
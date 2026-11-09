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

        $existingReceipt = LekukaReceipt::query()
            ->where('sale_id', $sale->id)
            ->whereIn('status', [
                'PENDING',
                'SUBMITTED',
            ])
            ->latest('id')
            ->first();

        if ($existingReceipt) {

            /*
            |--------------------------------------------------------------------------
            | Already submitted
            |--------------------------------------------------------------------------
            */

            if ($existingReceipt->status === 'SUBMITTED') {

                return $sale->fresh();
            }

            /*
            |--------------------------------------------------------------------------
            | Existing PENDING receipt
            |
            | We must reuse the exact same payload/counter rather than
            | generating a new fiscal receipt number.
            |--------------------------------------------------------------------------
            */

            return $this->submitPendingReceipt(
                receipt: $existingReceipt,
                sale: $sale,
                device: $device,
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
            $receiptCounter = ((int) $lastDayReceiptCounter) + 1;

            // Global receipt number continues across all fiscal days.
            $receiptGlobalNo = ((int) $lockedDevice->last_global_receipt_no) + 1;

            $receiptData = $this->builder->build(
                sale: $sale,
                day: $day,
                device: $lockedDevice,
                receiptCounter: $receiptCounter,
                receiptGlobalNo: $receiptGlobalNo,
            );

            $payload = $receiptData['payload'];

            $signature = $receiptData['signature'];

            /*
            |--------------------------------------------------------------------------
            | Extract fiscal numbers from the actual payload
            |--------------------------------------------------------------------------
            */

            $receiptCounter = $payload['receipt']['receiptCounter']
                ?? null;

            $receiptGlobalNo = $payload['receipt']['receiptGlobalNo']
                ?? null;

            $invoiceNo = $payload['receipt']['invoiceNo']
                ?? $sale->sale_number;

            if ($receiptCounter === null) {
                throw new \RuntimeException(
                    'Receipt counter was not generated by ReceiptPayloadBuilder.'
                );
            }

            if ($receiptGlobalNo === null) {
                throw new \RuntimeException(
                    'Receipt global number was not generated by ReceiptPayloadBuilder.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate counter allocation
            |--------------------------------------------------------------------------
            */

            $expectedCounter = $receiptCounter;

            $expectedGlobalNo =
                ((int) $lockedDevice->last_global_receipt_no) + 1;

            if ((int) $receiptCounter !== $expectedCounter) {

                throw new \RuntimeException(
                    "Receipt counter mismatch. " .
                    "Expected {$expectedCounter}, " .
                    "got {$receiptCounter}."
                );
            }

            if ((int) $receiptGlobalNo !== $expectedGlobalNo) {

                throw new \RuntimeException(
                    "Receipt global number mismatch. " .
                    "Expected {$expectedGlobalNo}, " .
                    "got {$receiptGlobalNo}."
                );
            }

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

                $receipt->forceFill([

                    'status' => 'FAILED',

                    'response' => $data,

                    'error_message' => $errorMessage,

                ])->save();

                $sale->update([

                    'submitted_to_lekuka' => false,

                    'lekuka_status' => 'FAILED',

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

                    'lekuka_status' => 'FAILED',

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

                'receipt_no' => $receipt->receipt_number,

                'receipt_global_no' =>
                    $receipt->receipt_global_no,

                'receipt_counter' =>
                    $receipt->receipt_counter,

                'fiscal_day_no' =>
                    $receipt->fiscal_day_no,

                'verification_code' =>
                    $receipt->verification_code,

                'qr_code' =>
                    $receipt->qr_code,

                'lekuka_status' => 'SUBMITTED',

                'submitted_at' => now(),

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

                'lekuka_status' => 'FAILED',

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
        | Only PENDING or FAILED receipts may be retried
        |--------------------------------------------------------------------------
        */

        if (! in_array($receipt->status, [
            'PENDING',
            'FAILED',
        ], true)) {

            throw new \RuntimeException(
                "Receipt {$receipt->id} cannot be retried."
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
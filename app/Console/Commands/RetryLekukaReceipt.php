<?php

namespace App\Console\Commands;

use App\Models\LekukaDevice;
use App\Models\LekukaReceipt;
use App\Services\Lekuka\LekukaClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class RetryLekukaReceipt extends Command
{
    protected $signature = 'lekuka:retry-receipt {id}';

    protected $description =
        'Retry an existing pending Lekuka receipt using its original signed payload';

    public function handle(LekukaClient $client): int
    {
        $id = $this->argument('id');

        $receipt = LekukaReceipt::find($id);

        if (! $receipt) {
            $this->error("Lekuka receipt ID {$id} was not found.");
            return self::FAILURE;
        }

        if ($receipt->status !== 'PENDING') {
            $this->error(
                "Receipt {$receipt->id} is not PENDING. " .
                "Current status: {$receipt->status}"
            );

            return self::FAILURE;
        }

        if (empty($receipt->request)) {
            $this->error(
                "Receipt {$receipt->id} has no stored request."
            );

            return self::FAILURE;
        }

        if (empty($receipt->correlation_id)) {
            $this->error(
                "Receipt {$receipt->id} has no original correlation ID. Retry blocked."
            );

            return self::FAILURE;
        }

        $device = LekukaDevice::find($receipt->device_id);

        if (! $device) {
            $this->error(
                "Device {$receipt->device_id} was not found."
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Load original persisted request
        |--------------------------------------------------------------------------
        */

        $payload = $receipt->request;

        if (! is_array($payload)) {
            $this->error('Stored request is not a valid array.');
            return self::FAILURE;
        }

        $data = $payload['receipt'] ?? $payload;

        $counter = $data['receiptCounter'] ?? null;

        $globalNo = $data['receiptGlobalNo'] ?? null;

        $invoiceNo = $data['invoiceNo'] ?? null;

        $this->newLine();

        $this->info("Local Receipt ID: {$receipt->id}");
        $this->info("Sale ID: {$receipt->sale_id}");
        $this->info("Device ID: {$device->device_id}");
        $this->info("Counter: {$counter}");
        $this->info("Global No: {$globalNo}");
        $this->info("Invoice No: {$invoiceNo}");

        /*
        |--------------------------------------------------------------------------
        | Fiscal number integrity checks
        |--------------------------------------------------------------------------
        */

        if (
            $counter === null ||
            (int) $counter !== (int) $receipt->receipt_counter
        ) {
            $this->error(
                'Stored request counter does not match database counter.'
            );

            return self::FAILURE;
        }

        if (
            $globalNo === null ||
            (int) $globalNo !== (int) $receipt->receipt_global_no
        ) {
            $this->error(
                'Stored request global number does not match database global number.'
            );

            return self::FAILURE;
        }

        if (empty($invoiceNo)) {
            $this->error(
                'Original request does not contain an invoice number.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Confirm retry
        |--------------------------------------------------------------------------
        */

        $this->warn(
            'This operation will resend the original persisted fiscal payload.'
        );

        $this->warn(
            'No new receipt counter or global receipt number will be allocated.'
        );

        if (! $this->confirm(
            "Retry receipt {$receipt->id} using its original correlation ID?"
        )) {
            $this->info('Retry cancelled.');
            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Submit original receipt
        |--------------------------------------------------------------------------
        */

        try {

            $response = $client->securePost(

                device: $device,

                endpoint:
                    "/Device/v2/{$device->device_id}/SubmitReceipt",

                payload: $payload,

                action: 'RETRY_SUBMIT_RECEIPT',

                correlationId: $receipt->correlation_id,

            );

            $responseData = $response->json();

            if (! is_array($responseData)) {
                $responseData = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Lekuka response
            |--------------------------------------------------------------------------
            */

            $lekukaReceiptId =
                $responseData['receiptID'] ?? null;

            $serverSignature =
                $responseData['receiptServerSignature'] ?? null;

            $validationErrors =
                $responseData['validationErrors'] ?? [];

            if (! is_array($validationErrors)) {
                $validationErrors = [];
            }

            $blockingErrors = [];

            foreach ($validationErrors as $validationError) {

                $color =
                    $validationError['validationErrorColor'] ?? null;

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

                    ? 'Lekuka rejected the receipt: ' .
                        collect($blockingErrors)
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

                    'response' => $responseData,

                    'status' => 'FAILED',

                    'error_message' => $errorMessage,

                ])->save();

                $receipt->sale?->update([
                    'submitted_to_lekuka' => false,
                ]);

                $this->error($errorMessage);

                return self::FAILURE;
            }

            /*
            |--------------------------------------------------------------------------
            | Save confirmed Lekuka receipt
            |--------------------------------------------------------------------------
            */

            $receipt->forceFill([

                'ReceiptId' => $lekukaReceiptId,

                'InvoiceNo' =>
                    $responseData['invoiceNo']
                    ?? $invoiceNo,

                'DeviceId' => $device->device_id,

                'receipt_number' =>
                    $receipt->receipt_number
                    ?? $invoiceNo,

                'receipt_global_no' =>
                    (string) $globalNo,

                'receipt_counter' =>
                    (string) $counter,

                'server_signature' => $serverSignature,

                'qr_code' =>
                    $responseData['qrCode']
                    ?? $receipt->qr_code,

                'verification_code' =>
                    $responseData['verificationCode']
                    ?? $receipt->verification_code,

                'response' => $responseData,

                'status' => 'SUBMITTED',

                'error_message' => null,

                'submitted_at' => now(),

            ])->save();

            /*
            |--------------------------------------------------------------------------
            | Update Sale
            |--------------------------------------------------------------------------
            */

            if ($receipt->sale) {

                $receipt->sale->update([

                    'submitted_to_lekuka' => true,

                    'lekuka_receipt_id' => $receipt->id,

                    'qr_code' => $receipt->qr_code,

                ]);
            }

            $this->newLine();

            $this->info(
                'Receipt successfully submitted/reconciled.'
            );

            $this->line(
                'Lekuka Receipt ID: ' . $lekukaReceiptId
            );

            $this->line(
                'Original Counter: ' . $counter
            );

            $this->line(
                'Original Global Number: ' . $globalNo
            );

            return self::SUCCESS;

        } catch (RequestException $e) {

            $httpStatus = $e->response->status();

            $ambiguous =
                $httpStatus === 408 ||
                $httpStatus >= 500;

            $receipt->forceFill([

                'status' => $ambiguous
                    ? 'PENDING'
                    : 'FAILED',

                'error_message' => $e->getMessage(),

                'response' => $e->response->json(),

            ])->save();

            $this->error(
                "Lekuka returned HTTP {$httpStatus}."
            );

            $this->warn(
                $ambiguous
                    ? 'Outcome is uncertain. Original receipt remains PENDING.'
                    : 'Lekuka returned an unsuccessful response. Receipt marked FAILED.'
            );

            return self::FAILURE;

        } catch (ConnectionException $e) {

            $receipt->forceFill([

                'status' => 'PENDING',

                'error_message' => $e->getMessage(),

            ])->save();

            $this->error(
                'Connection failed. Original receipt remains PENDING.'
            );

            return self::FAILURE;

        } catch (Throwable $e) {

            $this->error(
                'Retry failed: ' . $e->getMessage()
            );

            report($e);

            return self::FAILURE;
        }
    }
}
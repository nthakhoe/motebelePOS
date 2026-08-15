<?php

namespace App\Console\Commands;

use App\Models\LekukaDevice;
use App\Models\LekukaReceipt;
use App\Services\Lekuka\LekukaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class RetryLekukaReceipt extends Command
{
    protected $signature = 'lekuka:retry-receipt {id}';

    protected $description = 'Retry an existing pending Lekuka receipt using its original signed payload';

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
            $this->error("Receipt {$receipt->id} has no stored request.");
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
        | Request
        |--------------------------------------------------------------------------
        */

        $payload = is_array($receipt->request)
            ? $receipt->request
            : json_decode($receipt->request, true);

        if (! is_array($payload)) {
            $this->error('Stored request is not valid JSON.');
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
        | Safety check
        |--------------------------------------------------------------------------
        */

        if ((int) $counter !== (int) $receipt->receipt_counter) {
            $this->error(
                'Stored request counter does not match database counter.'
            );

            return self::FAILURE;
        }

        if ((int) $globalNo !== (int) $receipt->receipt_global_no) {
            $this->error(
                'Stored request global number does not match database global number.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Confirm
        |--------------------------------------------------------------------------
        */

        if (! $this->confirm(
            "Retry receipt {$receipt->id} with counter {$counter}?"
        )) {
            $this->info('Retry cancelled.');
            return self::SUCCESS;
        }

        try {

            $correlationId = (string) Str::uuid();

            $response = $client->securePost(
                device: $device,

                endpoint:
                    "/Device/v2/{$device->device_id}/SubmitReceipt",

                payload: $payload,

                action: 'RETRY_SUBMIT_RECEIPT',

                correlationId: $correlationId,
            );

            $response->throw();

            $data = $response->json();

            /*
            |--------------------------------------------------------------------------
            | Save Lekuka response
            |--------------------------------------------------------------------------
            */

            $receipt->update([

                'ReceiptId' =>
                    $data['receiptID'] ?? $receipt->ReceiptId,

                'InvoiceNo' =>
                    $data['invoiceNo']
                    ?? $invoiceNo
                    ?? $receipt->InvoiceNo,

                'DeviceId' =>
                    $device->device_id,

                'receipt_number' =>
                    $receipt->receipt_number
                    ?? $invoiceNo,

                'server_signature' =>
                    isset($data['receiptServerSignature'])
                        ? json_encode(
                            $data['receiptServerSignature']
                        )
                        : $receipt->server_signature,

                'response' => $data,

                'status' => 'SUBMITTED',

                'submitted_at' => now(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Sale
            |--------------------------------------------------------------------------
            */

            if ($receipt->sale) {

                $receipt->sale->update([

                    'submitted_to_lekuka' => true,

                    'lekuka_receipt_id' => $receipt->id,

                    'receipt_no' =>
                        $receipt->receipt_number,

                    'receipt_global_no' =>
                        $receipt->receipt_global_no,

                    'receipt_counter' =>
                        $receipt->receipt_counter,

                    'fiscal_day_no' =>
                        $receipt->fiscal_day_no,

                    'lekuka_status' => 'SUBMITTED',

                    'submitted_at' => now(),

                ]);
            }

            $this->newLine();

            $this->info(
                'Receipt successfully submitted/reconciled.'
            );

            $this->line(
                'Lekuka Receipt ID: ' .
                ($data['receiptID'] ?? 'N/A')
            );

            return self::SUCCESS;

        } catch (Throwable $e) {

            $this->error(
                'Retry failed: ' . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
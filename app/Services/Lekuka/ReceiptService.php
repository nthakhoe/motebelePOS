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
     */
    public function submit(
        Sale $sale,
        LekukaDevice $device,
    ): Sale {

        return DB::transaction(function () use ($sale, $device) {

            $correlationId = (string) Str::uuid();

            /*
            |--------------------------------------------------------------------------
            | Ensure Fiscal Day
            |--------------------------------------------------------------------------
            */

            $day = $this->fiscal->ensureOpen($device);
            dd($day);
            /*
            |--------------------------------------------------------------------------
            | Build Receipt Payload
            |--------------------------------------------------------------------------
            */

            $payload = $this->builder->build(
                sale: $sale,
                fiscalDay: $day,
                device: $device,
            );

            try {

                /*
                |--------------------------------------------------------------------------
                | Submit Receipt
                |--------------------------------------------------------------------------
                */

                $response = $this->client->securePost(

                    device: $device,

                    endpoint: "/Device/v2/{$device->device_id}/SubmitReceipt",

                    payload: $payload,

                    action: 'SUBMIT_RECEIPT',

                    correlationId: $correlationId,

                );

                $response->throw();

                $data = $response->json();

                /*
                |--------------------------------------------------------------------------
                | Save Fiscal Receipt
                |--------------------------------------------------------------------------
                */

                $receipt = LekukaReceipt::create([

                    'company_id' => $sale->company_id,

                    'branch_id' => $sale->branch_id,

                    'device_id' => $device->id,

                    'sale_id' => $sale->id,

                    'correlation_id' => $correlationId,

                    'receipt_number' => $data['receiptNumber'] ?? null,

                    'receipt_global_no' => $data['receiptGlobalNo'] ?? null,

                    'receipt_counter' => $data['receiptCounter'] ?? null,

                    'fiscal_day_no' => $day->fiscal_day_no,

                    'qr_code' => $data['qrCode'] ?? null,

                    'verification_code' => $data['verificationCode'] ?? null,

                    'server_signature' => $data['signature'] ?? null,

                    'status' => 'SUBMITTED',

                    'request' => $payload,

                    'response' => $data,

                    'submitted_at' => now(),

                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Sale
                |--------------------------------------------------------------------------
                */

                $sale->update([

                    'submitted_to_lekuka' => true,

                    'lekuka_receipt_id' => $receipt->id,

                    'receipt_no' => $receipt->receipt_number,

                    'receipt_global_no' => $receipt->receipt_global_no,

                    'receipt_counter' => $receipt->receipt_counter,

                    'fiscal_day_no' => $receipt->fiscal_day_no,

                    'verification_code' => $receipt->verification_code,

                    'qr_code' => $receipt->qr_code,

                    'lekuka_status' => 'SUBMITTED',

                    'submitted_at' => now(),

                ]);

            } catch (Throwable $e) {

                $sale->update([

                    'submitted_to_lekuka' => false,

                    'lekuka_status' => 'FAILED',

                ]);

                throw $e;
            }

            return $sale->fresh();

        });
    }

}
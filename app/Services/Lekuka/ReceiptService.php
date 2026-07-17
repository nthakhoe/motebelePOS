<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use App\Models\LekukaReceipt;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ReceiptService
{
    public function __construct(
        protected LekukaClient $client,
        protected FiscalPeriodService $fiscal,
        protected ReceiptPayloadBuilder $builder
    ) {
    }

    public function submit(
        Sale $sale,
        LekukaDevice $device
    ): LekukaReceipt {

        return DB::transaction(function () use ($sale, $device) {

            /*
             |--------------------------------------------------------------------------
             | Correlation
             |--------------------------------------------------------------------------
             */

            $correlationId = (string) Str::uuid();

            /*
             |--------------------------------------------------------------------------
             | Ensure Fiscal Day
             |--------------------------------------------------------------------------
             */

            $day = $this->fiscal
                ->ensureOpen($device);

            /*
             |--------------------------------------------------------------------------
             | Build Payload
             |--------------------------------------------------------------------------
             */

            $payload = $this->builder
                ->build($sale, $day);

            /*
             |--------------------------------------------------------------------------
             | Submit Receipt
             |--------------------------------------------------------------------------
             */

            $response = $this->client->securePost(

                device: $device,

                endpoint: "/Device/v1/SubmitReceipt",

                payload: $payload,

                action: "SUBMIT_RECEIPT",

                correlationId: $correlationId

            );

            $data = $response->json();

            /*
             |--------------------------------------------------------------------------
             | Save Receipt
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

                'qr_code' => $receipt->qr_code

            ]);

            return $receipt;

        });
    }
}
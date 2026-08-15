<?php

namespace App\Console\Commands;

use App\Models\LekukaDevice;
use App\Models\Sale;
use App\Services\Lekuka\FiscalPeriodService;
use App\Services\Lekuka\ReceiptPayloadBuilder;
use Illuminate\Console\Command;

class TestLekukaReceiptPayload extends Command
{
    protected $signature = 'lekuka:test-payload {sale_id}';

    protected $description = 'Test Lekuka receipt payload without submitting to Lekuka';

    public function handle(
        ReceiptPayloadBuilder $builder,
        FiscalPeriodService $fiscal
    ) {
        $saleId = $this->argument('sale_id');

        $sale = Sale::find($saleId);

        if (!$sale) {
            $this->error("Sale {$saleId} was not found.");
            return self::FAILURE;
        }

        $device = LekukaDevice::query()
            ->where('company_id', $sale->company_id)
            ->where('branch_id', $sale->branch_id)
            ->where('registered', true)
            ->first();

        if (!$device) {
            $this->error('No registered Lekuka device found.');
            return self::FAILURE;
        }

        $day = $fiscal->current($device);

        if (!$day) {
            $this->error('No open fiscal day found for this device.');
            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Use the NEXT counter values for testing only.
        |--------------------------------------------------------------------------
        */

        $receiptCounter =
            ((int) $device->last_receipt_counter) + 1;

        $receiptGlobalNo =
            ((int) $device->last_global_receipt_no) + 1;

        $this->info("Sale: {$sale->id}");
        $this->info("Device: {$device->device_id}");
        $this->info("Fiscal Day: {$day->fiscal_day_no}");
        $this->info("Test Counter: {$receiptCounter}");
        $this->info("Test Global No: {$receiptGlobalNo}");

        $this->newLine();

        $receiptData = $builder->build(
            sale: $sale,
            day: $day,
            device: $device,
            receiptCounter: $receiptCounter,
            receiptGlobalNo: $receiptGlobalNo,
        );

        $payload = $receiptData['payload'];

        $this->info('PAYLOAD');

        $this->line(
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        $this->newLine();

        $this->info('SIGNATURE');

        $this->line(
            json_encode(
                $receiptData['signature'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        $this->newLine();

        $this->info('Canonical data:');

        $this->line(
            $receiptData['canonical']
        );

        $this->newLine();

        $this->info(
            'SUCCESS: Payload built successfully. Nothing was submitted to Lekuka.'
        );

        return self::SUCCESS;
    }
}
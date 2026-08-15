<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectLekukaReceipts extends Command
{
    protected $signature = 'lekuka:inspect-receipts';

    protected $description = 'Inspect Lekuka receipt and counter records';

    public function handle()
    {
        $this->info('=== LEKUKA RECEIPTS ===');

        $receipts = DB::table('Lekuka_Receipts')
            ->orderBy('ReceiptId')
            ->get();

        foreach ($receipts as $receipt) {

            $this->line(
                "ReceiptId={$receipt->ReceiptId} | " .
                "InvoiceNo={$receipt->InvoiceNo} | " .
                "Counter={$receipt->receipt_counter} | " .
                "Global={$receipt->receipt_global_no} | " .
                "FiscalDay={$receipt->fiscal_day_no} | " .
                "Device={$receipt->DeviceId}"
            );
        }

        $this->newLine();

        $this->info('=== LEKUKA GLOBAL COUNTERS ===');

        $counters = DB::table('LekukaGlobalCounters')
            ->orderBy('Id')
            ->get();

        foreach ($counters as $counter) {

            $this->line(
                "Id={$counter->Id} | " .
                "InvoiceNo={$counter->InvoiceNo} | " .
                "Counter={$counter->ReceiptCounter} | " .
                "Global={$counter->ReceiptGlobalNo} | " .
                "FiscalDay={$counter->FiscalDayNo} | " .
                "Device={$counter->DeviceId}"
            );
        }

        return self::SUCCESS;
    }
}
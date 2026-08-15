<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LekukaReceipt;

class BackfillLekukaReceiptCounters extends Command
{
    protected $signature = 'lekuka:backfill-counters';

    protected $description = 'Backfill receipt counter, global number and fiscal day number from stored request JSON';

    public function handle()
    {
        $receipts = LekukaReceipt::query()
            ->whereNull('receipt_counter')
            ->whereNotNull('request')
            ->orderBy('id')
            ->get();

        $this->info("Found {$receipts->count()} receipts to process.");

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($receipts as $receipt) {

            try {

                // Get stored request
                $request = $receipt->request;

                // Convert JSON string to array if necessary
                if (is_string($request)) {
                    $request = json_decode($request, true);
                }

                if (!is_array($request)) {
                    $this->warn(
                        "Receipt {$receipt->id}: request is not valid JSON."
                    );

                    $skipped++;
                    continue;
                }

                /*
                 * Depending on how the request was stored,
                 * the actual receipt may be under:
                 *
                 * $request['receipt']
                 *
                 * or the request itself may be the receipt.
                 */
                $data = $request['receipt'] ?? $request;

                $receiptCounter = $data['receipt_counter'] ?? null;
                $receiptGlobalNo = $data['receipt_global_no'] ?? null;
                $fiscalDayNo = $data['fiscal_day_no']
                    ?? $receipt->fiscal_day_no;

                if ($receiptCounter === null && $receiptGlobalNo === null) {

                    $this->warn(
                        "Receipt {$receipt->id}: no counters found in request."
                    );

                    $skipped++;
                    continue;
                }

                $receipt->update([
                    'receipt_counter' => $receiptCounter,
                    'receipt_global_no' => $receiptGlobalNo,
                    'fiscal_day_no' => $fiscalDayNo,
                ]);

                $this->line(
                    "Updated Receipt {$receipt->id}: " .
                    "Counter={$receiptCounter}, " .
                    "Global={$receiptGlobalNo}, " .
                    "FiscalDay={$fiscalDayNo}"
                );

                $updated++;

            } catch (\Throwable $e) {

                $this->error(
                    "Receipt {$receipt->id} failed: {$e->getMessage()}"
                );

                $failed++;
            }
        }

        $this->newLine();

        $this->info("Backfill completed.");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");
        $this->info("Failed: {$failed}");

        return self::SUCCESS;
    }
}
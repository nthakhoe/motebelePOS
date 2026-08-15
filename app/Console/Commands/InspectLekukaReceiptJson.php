<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LekukaReceipt;

class InspectLekukaReceiptJson extends Command
{
    protected $signature = 'lekuka:inspect-json';

    protected $description = 'Inspect stored Lekuka receipt request and response';

    public function handle()
    {
        $receipt = LekukaReceipt::query()
            ->whereNotNull('request')
            ->orderBy('id')
            ->first();

        if (!$receipt) {
            $this->error('No receipt found.');
            return self::FAILURE;
        }

        $this->info('LOCAL DATABASE VALUES');
        $this->line('id: ' . $receipt->id);
        $this->line('company_id: ' . $receipt->company_id);
        $this->line('branch_id: ' . $receipt->branch_id);
        $this->line('device_id: ' . $receipt->device_id);
        $this->line('sale_id: ' . $receipt->sale_id);
        $this->line('receipt_number: ' . $receipt->receipt_number);
        $this->line('receipt_counter: ' . $receipt->receipt_counter);
        $this->line('receipt_global_no: ' . $receipt->receipt_global_no);
        $this->line('fiscal_day_no: ' . $receipt->fiscal_day_no);
        $this->line('status: ' . $receipt->status);

        $this->newLine();

        $this->info('REQUEST JSON');

        $request = $receipt->request;

        if (is_string($request)) {
            $request = json_decode($request, true);
        }

        if (is_array($request)) {
            $this->line(
                json_encode(
                    $request,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            );
        } else {
            $this->warn('Request is neither an array nor a valid JSON string.');
        }

        $this->newLine();

        $this->info('RESPONSE JSON');

        $response = $receipt->response;

        if (is_string($response)) {
            $response = json_decode($response, true);
        }

        if (is_array($response)) {
            $this->line(
                json_encode(
                    $response,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            );
        } elseif ($response === null) {
            $this->warn('No response stored.');
        } else {
            $this->warn('Response is neither an array nor a valid JSON string.');
        }
        return self::SUCCESS;
    }
}
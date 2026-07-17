<?php

namespace App\Jobs;

use App\Models\LekukaReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Jobs\SubmitReceiptJob;

class RetryFailedReceiptsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LekukaReceipt::query()
            ->where('status', 'PENDING')
            ->where('processed', false)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->orderBy('next_retry_at')
            ->chunk(100, function ($receipts) {

                foreach ($receipts as $receipt) {

                    SubmitReceiptJob::dispatch(
                        $receipt->sale_id,
                        $receipt->device_id
                    )->onQueue('lekuka');
                }

            });
    }
}
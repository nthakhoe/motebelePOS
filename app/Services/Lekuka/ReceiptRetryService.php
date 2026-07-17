<?php

namespace App\Services\Lekuka;

use App\Models\LekukaReceipt;
use Carbon\Carbon;

class ReceiptRetryService
{
    public function scheduleRetry(
        LekukaReceipt $receipt
    ): void {

        $receipt->update([

            'status' => 'PENDING',

            'attempts' => $receipt->attempts + 1,

            'last_attempt_at' => now(),

            'next_retry_at' => Carbon::now()
                ->addMinutes(
                    min(
                        60,
                        $receipt->attempts * 2
                    )
                ),

        ]);
    }
}
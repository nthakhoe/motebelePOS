<?php

namespace App\Jobs;

use App\Models\LekukaDevice;
use App\Models\Sale;
use App\Services\Lekuka\ReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitReceiptJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(

        public int $saleId,

        public int $deviceId

    ) {
    }

    public function handle(
        ReceiptService $service
        ): void {

            $sale = Sale::findOrFail($this->saleId);

            $device = LekukaDevice::findOrFail($this->deviceId);

            $service->submit($sale, $device);
        }
    }
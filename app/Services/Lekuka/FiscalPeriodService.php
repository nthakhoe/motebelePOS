<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use App\Models\LekukaFiscalDay;
use Illuminate\Support\Str;

class FiscalPeriodService
{
    public function __construct(
        protected LekukaClient $client
    ) {
    }

    /**
     * Open Fiscal Day
     */
    public function open(
        LekukaDevice $device
    ): LekukaFiscalDay {

        $correlationId = (string) Str::uuid();

        $response = $this->client->securePost(

            device: $device,

            endpoint: "/Device/v1/OpenDay",

            payload: [],

            action: 'OPEN_DAY',

            correlationId: $correlationId

        );

        $data = $response->json();

        return LekukaFiscalDay::create([

            'company_id' => $device->company_id,

            'branch_id' => $device->branch_id,

            'device_id' => $device->id,

            'correlation_id' => $correlationId,

            'fiscal_day_no' => $data['fiscalDayNo'],

            'business_date' => $data['businessDate'],

            'opened_at' => now(),

            'status' => 'OPEN',

            'response' => $data

        ]);
    }

    /**
     * Close Fiscal Day
     */
    public function close(
        LekukaFiscalDay $day
    ): void {

        $this->client->securePost(

            device: $day->device,

            endpoint: "/Device/v1/CloseDay",

            payload: [],

            action: 'CLOSE_DAY',

            correlationId: $day->correlation_id

        );

        $day->update([

            'closed_at' => now(),

            'status' => 'CLOSED'
        ]);
    }

    /**
     * Returns current open fiscal day.
     */
    public function current(
        LekukaDevice $device
    ): ?LekukaFiscalDay {

        return LekukaFiscalDay::query()

            ->where('device_id',$device->id)

            ->where('status','OPEN')

            ->latest()

            ->first();
    }

    /**
     * Ensure fiscal day exists.
     */
    public function ensureOpen(
        LekukaDevice $device
    ): LekukaFiscalDay {

        return $this->current($device)

            ?? $this->open($device);
    }

    /**
     * Check if fiscal day is open.
     */
    public function isOpen(
        LekukaDevice $device
    ): bool {

        return $this->current($device) !== null;
    }
}
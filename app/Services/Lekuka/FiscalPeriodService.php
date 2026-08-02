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

        $payload = [
            'fiscalDayOpened' => now()->format('Y-m-d\TH:i:s'),
        ];

        $response = $this->client->securePost(
            device: $device,
            endpoint: "/Device/v1/{$device->device_id}/OpenDay",
            payload: $payload,
            action: 'OPEN_DAY',
            correlationId: $correlationId
        );

        $data = $response->json();

        if (
            $response->status() === 422 &&
            ($data['errorCode'] ?? null) === 'FISC01'
        ) {
            // The fiscal day is already open.
            return LekukaFiscalDay::where('device_id', $device->id)
                ->where('status', 'OPEN')
                ->latest()
                ->firstOrFail();
        }

        return LekukaFiscalDay::create([

            'company_id' => $device->company_id,

            'branch_id' => $device->branch_id,

            'device_id' => $device->id,

            'correlation_id' => $correlationId,

            'fiscal_day_no' => $data['fiscalDayNo'],

            'business_date' => $data['fiscalDayOpened'],

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

            endpoint: "/Device/v1/{$device->device_id}/CloseDay",

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
    public function ensureOpen(LekukaDevice $device): LekukaFiscalDay
    {
        // Check if we already have an open fiscal day locally
        $localDay = LekukaFiscalDay::where('device_id', $device->id)
            ->where('status', 'OPEN')
            ->latest()
            ->first();

        if ($localDay) {
            return $localDay;
        }

        try {
            // No local record, try opening a new fiscal day
            return $this->open($device);

        } catch (\Throwable $e) {

            $response = json_decode($e->getMessage(), true);

            // Lekuka says a fiscal day is already open
            if (
                is_array($response) &&
                ($response['errorCode'] ?? null) === 'FISC01'
            ) {

                /*
                 * Synchronize local database with Lekuka.
                 * We don't know the fiscalDayNo because the API
                 * doesn't return it in this error response.
                 */

                return LekukaFiscalDay::create([
                    'company_id'    => $device->company_id,
                    'branch_id'     => $device->branch_id,
                    'device_id'     => $device->id,
                    'fiscal_day_no' => null, // update later when available
                    'opened_at'     => now(),
                    'status'        => 'OPEN',
                ]);
            }

            throw $e;
        }
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
<?php

namespace App\Services\Lekuka;

use App\Models\LekukaConfiguration;
use App\Models\LekukaDevice;
use Illuminate\Support\Facades\DB;

class ConfigurationService
{
    public function __construct(
        protected LekukaClient $client
    ) {
    }

    public function refresh(
        LekukaDevice $device
    ): LekukaConfiguration {

        return DB::transaction(function () use ($device) {

            $response = $this->client->secureGet(

                device: $device,

                endpoint: "/Device/v1/GetConfig",

                action: "GET_CONFIGURATION"

            );

            $data = $response->json();

            return LekukaConfiguration::updateOrCreate(

                [
                    'device_id' => $device->id,
                ],

                [

                    'company_id' => $device->company_id,

                    'branch_id' => $device->branch_id,

                    'operation_id' => $data['operationID'] ?? null,

                    'device_serial_no' => $data['deviceSerialNo'] ?? null,

                    'device_operating_mode' => $data['deviceOperatingMode'] ?? null,

                    'taxpayer_name' => $data['taxPayerName'] ?? null,

                    'taxpayer_tin' => $data['taxPayerTIN'] ?? null,

                    'vat_number' => $data['vatNumber'] ?? null,

                    'branch_name' => $data['deviceBranchName'] ?? null,

                    'branch_address' => $data['deviceBranchAddress'] ?? [],

                    'branch_contacts' => $data['deviceBranchContacts'] ?? [],

                    'device_reporting_frequency' =>
                        $data['deviceReportingFrequencyInMinutes'] ?? 15,

                    'taxpayer_day_max_hours' =>
                        $data['taxPayerDayMaxHrs'] ?? 24,

                    'day_end_notification_hours' =>
                        $data['taxpayerDayEndNotificationHrs'] ?? 2,

                    'receipt_forms' =>
                        $data['receiptPrintForms'] ?? [],

                    'taxes' =>
                        $data['taxes'] ?? [],

                    'payment_types' =>
                        $data['moneyTypes'] ?? [],

                    'raw_response' => $data,

                    'downloaded_at' => now(),

                ]

            );

        });

    }

    public function current(
        LekukaDevice $device
    ): ?LekukaConfiguration {

        return LekukaConfiguration::where(
            'device_id',
            $device->id
        )->first();
    }

    public function ensure(
        LekukaDevice $device
    ): LekukaConfiguration {

        return $this->current($device)
            ?? $this->refresh($device);
    }
}
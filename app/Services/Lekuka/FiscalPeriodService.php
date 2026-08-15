<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use App\Models\LekukaFiscalDay;
use App\Models\LekukaReceipt;
use Illuminate\Support\Str;
use Carbon\Carbon;
use RuntimeException;
use Illuminate\Support\Facades\DB;

class FiscalPeriodService
{
    public function __construct(
        protected LekukaClient $client,
        protected FiscalDaySignatureService $fiscalSignature,
    ) {
    }

    /**
     * Return the current locally open fiscal day.
     */
    public function current(
        LekukaDevice $device
    ): ?LekukaFiscalDay {

        return LekukaFiscalDay::query()
            ->where('device_id', $device->id)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();
    }

    /**
     * Ensure that the device has a usable fiscal day.
     *
     * This method is called before submitting a receipt.
     *
     * If the current fiscal day has exceeded the configured
     * maximum duration, closure is initiated and the current
     * transaction is stopped.
     */
    public function ensureOpen(
        LekukaDevice $device
    ): LekukaFiscalDay {

        /*
        |--------------------------------------------------------------------------
        | Get authoritative fiscal-day status from Lekuka
        |--------------------------------------------------------------------------
        */

        $status = $this->status($device);

        $fiscalDayStatus = $status['fiscalDayStatus'] ?? null;

        if (! $fiscalDayStatus) {
            throw new RuntimeException(
                'Lekuka did not return a fiscal day status.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fiscal Day Open
        |--------------------------------------------------------------------------
        */

        if ($fiscalDayStatus === 'FiscalDayOpened') {

            $day = $this->sync($device, $status);

            /*
            |--------------------------------------------------------------------------
            | Check local fiscal-day expiry
            |--------------------------------------------------------------------------
            */

            if ($this->hasExpired($device, $day)) {

                /*
                |--------------------------------------------------------------------------
                | Initiate closure
                |--------------------------------------------------------------------------
                |
                | CloseDay is asynchronous. Lekuka will move through:
                |
                | FiscalDayCloseInitiated
                |          ↓
                | FiscalDayClosed
                |
                | or
                |
                | FiscalDayCloseFailed
                |
                */

                $this->close($device);

                throw new RuntimeException(
                    'Fiscal day '.$day->fiscal_day_no.
                    ' has reached its maximum duration. '.
                    'Fiscal day closure has been initiated. '.
                    'Please retry the transaction after the fiscal day is closed.'
                );
            }

            return $day;
        }

        /*
        |--------------------------------------------------------------------------
        | Fiscal Day Closed
        |--------------------------------------------------------------------------
        |
        | Lekuka allows a new fiscal period to be opened only after
        | the previous fiscal period is closed.
        |--------------------------------------------------------------------------
        */

        if ($fiscalDayStatus === 'FiscalDayClosed') {

            return $this->open($device);
        }

        /*
        |--------------------------------------------------------------------------
        | Fiscal Day Close Initiated
        |--------------------------------------------------------------------------
        |
        | No new invoices may be submitted while closure is processing.
        |--------------------------------------------------------------------------
        */

        if ($fiscalDayStatus === 'FiscalDayCloseInitiated') {

            $this->markLocalCloseInitiated($device, $status);

            throw new RuntimeException(
                'Fiscal day closure is still being processed by Lekuka. '.
                'Please retry the transaction shortly.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fiscal Day Close Failed
        |--------------------------------------------------------------------------
        */

        if ($fiscalDayStatus === 'FiscalDayCloseFailed') {

            $this->markLocalCloseFailed($device, $status);

            throw new RuntimeException(
                'The previous fiscal day failed to close. '.
                'The fiscal day must be reconciled before new receipts can be submitted.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unknown Status
        |--------------------------------------------------------------------------
        */

        throw new RuntimeException(
            'Unknown fiscal day status returned by Lekuka: '.
            $fiscalDayStatus
        );
    }

    /**
     * Determine whether the fiscal day has exceeded
     * the maximum configured duration.
     */
    protected function hasExpired(
        LekukaDevice $device,
        LekukaFiscalDay $day
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | No opened_at = cannot safely determine expiry
        |--------------------------------------------------------------------------
        */

        if (! $day->opened_at) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Read TaxPayerDayMaxHrs from local configuration.
        |--------------------------------------------------------------------------
        */

        $maxHours = \DB::table('lekuka_configurations')
            ->where('device_id', $device->id)
            ->value('TaxPayer_Day_Max_Hours');

        /*
        |--------------------------------------------------------------------------
        | Some installations use snake_case column naming.
        |--------------------------------------------------------------------------
        */

        if ($maxHours === null) {

            $maxHours = \DB::table('lekuka_configurations')
                ->where('device_id', $device->id)
                ->value('taxpayer_day_max_hours');
        }

        /*
        |--------------------------------------------------------------------------
        | If configuration is unavailable, do not automatically
        | close the fiscal day.
        |--------------------------------------------------------------------------
        */

        if ($maxHours === null || (int) $maxHours <= 0) {
            return false;
        }

        $expiryTime = Carbon::parse($day->opened_at)
            ->addHours((int) $maxHours);

        return now()->timestamp >= $expiryTime->timestamp;
    }

    /**
     * Check if fiscal day is currently open.
     */
    public function isOpen(
        LekukaDevice $device
    ): bool {

        return $this->current($device) !== null;
    }

    /**
     * Open a new fiscal day.
     */
    public function open(
        LekukaDevice $device,
    ): LekukaFiscalDay {

        $correlationId = (string) Str::uuid();

        /*
        |--------------------------------------------------------------------------
        | Do not open if a local fiscal day is already open.
        |--------------------------------------------------------------------------
        */

        $existing = LekukaFiscalDay::query()
            ->where('device_id', $device->id)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        /*
        |--------------------------------------------------------------------------
        | Open fiscal day
        |--------------------------------------------------------------------------
        */

        $openedAt = now();

        $response = $this->client->securePost(

            device: $device,

            endpoint:
                "/Device/v1/{$device->device_id}/OpenDay",

            payload: [

                'openDayRequest' => [

                    'fiscalDayOpened' =>
                        $openedAt->format('Y-m-d\TH:i:s'),

                ],

            ],

            action: 'OPEN_DAY',

            correlationId: $correlationId,

        );

        $response->throw();

        $data = $response->json();

        if (! isset($data['fiscalDayNo'])) {

            throw new RuntimeException(
                'Lekuka did not return fiscalDayNo when opening fiscal day.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Save local fiscal day
        |--------------------------------------------------------------------------
        */

        return LekukaFiscalDay::create([

            'company_id' => $device->company_id,

            'branch_id' => $device->branch_id,

            'device_id' => $device->id,

            'correlation_id' => $correlationId,

            'fiscal_day_no' => $data['fiscalDayNo'],

            'business_date' => $openedAt->toDateString(),

            'opened_at' => $openedAt,

            'closed_at' => null,

            'status' => 'OPEN',

            'response' => $data,

        ]);
    }

    /**
     * Initiate fiscal-day closure.
     */
    public function close(
        LekukaDevice $device,
    ): LekukaFiscalDay {

        $day = LekukaFiscalDay::query()
            ->where('device_id', $device->id)
            ->whereIn('status', [
                'OPEN',
                'CLOSE_FAILED',
            ])
            ->latest('id')
            ->first();

        if (! $day) {

            throw new RuntimeException(
                'There is no fiscal day available for closure.'
            );
        }

        if (! $day->fiscal_day_no) {

            throw new RuntimeException(
                'Fiscal day number is missing.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Build fiscal-day counters
        |--------------------------------------------------------------------------
        */

        $counters = $this->buildFiscalDayCounters(
            day: $day,
            device: $device,
        );

        /*
        |--------------------------------------------------------------------------
        | Build fiscal-day signature
        |--------------------------------------------------------------------------
        */

        $signature = $this->fiscalSignature->sign(

            device: $device,

            fiscalDayNo:
                (int) $day->fiscal_day_no,

            fiscalDayDate:
                $day->opened_at
                    ? $day->opened_at->format('Y-m-d')
                    : (
                        $day->business_date
                            ? $day->business_date->format('Y-m-d')
                            : now()->format('Y-m-d')
                    ),

            counters: $counters,

        );

        /*
        |--------------------------------------------------------------------------
        | Last submitted receipt counter
        |--------------------------------------------------------------------------
        */

        $lastReceiptCounter = LekukaReceipt::query()
            ->where('device_id', $device->id)
            ->where('fiscal_day_no', $day->fiscal_day_no)
            ->where('status', 'SUBMITTED')
            ->max(DB::raw('CAST(receipt_counter AS UNSIGNED)'));

        /*
        |--------------------------------------------------------------------------
        | Close Day Request
        |--------------------------------------------------------------------------
        */

        $payload = [

            'fiscalDayNo' =>
                (int) $day->fiscal_day_no,

            'fiscalDayCounters' =>
                $counters,

            'fiscalDayDeviceSignature' => [

                'hash' =>
                    $signature['hash'],

                'signature' =>
                    $signature['signature'],

            ],

            'receiptCounter' =>
                (int) ($lastReceiptCounter ?? 0),

        ];

        $correlationId = (string) Str::uuid();

        /*
        |--------------------------------------------------------------------------
        | Submit CloseDay
        |--------------------------------------------------------------------------
        */
        dd([
        'device_id' => $device->device_id,

        'fiscal_day_no' => (int) $day->fiscal_day_no,

        'fiscal_day_date' =>
            $day->opened_at
                ? $day->opened_at->format('Y-m-d')
                : $day->business_date->format('Y-m-d'),

        'last_receipt_counter' => $lastReceiptCounter,

        'counters' => $counters,

        'canonical' => $signature['canonical'],

        'hash' => $signature['hash'],

        'signature' => $signature['signature'],

        'payload' => $payload,
    ]);
        $response = $this->client->securePost(

            device: $device,

            endpoint:
                "/Device/v2/{$device->device_id}/CloseDay",

            payload: $payload,

            action: 'CLOSE_DAY',

            correlationId: $correlationId,

        );

        $response->throw();

        $data = $response->json();

        /*
        |--------------------------------------------------------------------------
        | Closure is asynchronous.
        |--------------------------------------------------------------------------
        */

        $day->update([

            'correlation_id' =>
                $correlationId,

            'status' =>
                'CLOSE_INITIATED',

            'response' =>
                $data,

        ]);

        return $day->fresh();
    }

    /**
     * Get authoritative fiscal-day status from Lekuka.
     */
    public function status(
        LekukaDevice $device
    ): array {

        $response = $this->client->secureGet(

            device: $device,

            endpoint:
                "/Device/v1/{$device->device_id}/GetStatus",

            action: 'GET_STATUS',

            correlationId:
                (string) Str::uuid(),

        );

        $response->throw();

        return $response->json();
    }

    /**
     * Synchronize the local fiscal day with Lekuka.
     */
    public function sync(
        LekukaDevice $device,
        array $status
    ): LekukaFiscalDay {

        $fiscalDayNo =
            $status['lastFiscalDayNo'] ?? null;

        if (! $fiscalDayNo) {

            throw new RuntimeException(
                'Lekuka did not return lastFiscalDayNo for the open fiscal day.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Try to find the existing local fiscal day first.
        |--------------------------------------------------------------------------
        */

        $day = LekukaFiscalDay::query()
            ->where('device_id', $device->id)
            ->where('fiscal_day_no', $fiscalDayNo)
            ->latest('id')
            ->first();

        if ($day) {

            $day->update([

                'status' =>
                    'OPEN',

                'response' =>
                    $status,

            ]);

            return $day->fresh();
        }

        /*
        |--------------------------------------------------------------------------
        | This should normally only happen if the local database missed
        | the original OpenDay record.
        |--------------------------------------------------------------------------
        */

        return LekukaFiscalDay::create([

            'company_id' =>
                $device->company_id,

            'branch_id' =>
                $device->branch_id,

            'device_id' =>
                $device->id,

            'fiscal_day_no' =>
                $fiscalDayNo,

            'business_date' =>
                now()->toDateString(),

            'opened_at' =>
                now(),

            'closed_at' =>
                null,

            'status' =>
                'OPEN',

            'response' =>
                $status,

        ]);
    }

    /**
     * Mark local fiscal day as closure initiated.
     */
    protected function markLocalCloseInitiated(
        LekukaDevice $device,
        array $status
    ): void {

        $day = $this->current($device);

        if (! $day) {
            return;
        }

        $day->update([

            'status' =>
                'CLOSE_INITIATED',

            'response' =>
                $status,

        ]);
    }

    /**
     * Mark local fiscal day as closure failed.
     */
    protected function markLocalCloseFailed(
        LekukaDevice $device,
        array $status
    ): void {

        $day = $this->current($device);

        if (! $day) {
            return;
        }

        $day->update([

            'status' =>
                'CLOSE_FAILED',

            'response' =>
                $status,

        ]);
    }

    /**
     * Build fiscal-day counters.
     */
    protected function buildFiscalDayCounters(
        LekukaFiscalDay $day,
        LekukaDevice $device,
    ): array {

        $receipts = LekukaReceipt::query()
            ->where('device_id', $device->id)
            ->where('fiscal_day_no', $day->fiscal_day_no)
            ->where('status', 'SUBMITTED')
            ->get();

        $counters = [];

        foreach ($receipts as $receipt) {

            $request = $receipt->request;

            /*
            |--------------------------------------------------------------------------
            | Request may be stored as array or JSON string.
            |--------------------------------------------------------------------------
            */

            if (is_string($request)) {

                $request = json_decode(
                    $request,
                    true
                ) ?? [];
            }

            $data =
                $request['receipt']
                ?? $request;

            /*
            |--------------------------------------------------------------------------
            | Sale / Receipt
            |--------------------------------------------------------------------------
            */

            $receiptType = strtolower(
                $data['receiptType'] ?? ''
            );

            if (
                in_array(
                    $receiptType,
                    [
                        'receipt',
                        'fiscalinvoice',
                    ],
                    true
                )
            ) {

                foreach (
                    $data['receiptTaxes'] ?? []
                    as $tax
                ) {

                    $taxId =
                        (int) ($tax['taxID'] ?? 0);

                    $taxRate =
                        isset($tax['taxRate'])
                            ? (float) $tax['taxRate']
                            : null;

                    /*
                    |--------------------------------------------------------------------------
                    | SaleByTax
                    |--------------------------------------------------------------------------
                    */

                    $key = implode('|', [

                        'SaleByTax',

                        'LSL',

                        $taxId,

                        $taxRate ?? 'EXEMPT',

                    ]);

                    if (! isset($counters[$key])) {

                        $counters[$key] = [

                            'fiscalCounterType' =>
                                'SaleByTax',

                            'fiscalCounterCurrency' =>
                                'LSL',

                            'fiscalCounterTaxID' =>
                                $taxId,

                            'fiscalCounterTaxRate' =>
                                $taxRate,

                            'fiscalCounterValue' =>
                                0,

                        ];
                    }

                    $counters[$key]['fiscalCounterValue'] +=
                        (float) (
                            $tax['salesAmountWithTax']
                            ?? 0
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | SaleTaxByTax
                    |--------------------------------------------------------------------------
                    */

                    $key = implode('|', [

                        'SaleTaxByTax',

                        'LSL',

                        $taxId,

                        $taxRate ?? 'EXEMPT',

                    ]);

                    if (! isset($counters[$key])) {

                        $counters[$key] = [

                            'fiscalCounterType' =>
                                'SaleTaxByTax',

                            'fiscalCounterCurrency' =>
                                'LSL',

                            'fiscalCounterTaxID' =>
                                $taxId,

                            'fiscalCounterTaxRate' =>
                                $taxRate,

                            'fiscalCounterValue' =>
                                0,

                        ];
                    }

                    $counters[$key]['fiscalCounterValue'] +=
                        (float) (
                            $tax['taxAmount']
                            ?? 0
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Balance By Money Type
            |--------------------------------------------------------------------------
            */

            foreach (
                $data['receiptPayments'] ?? []
                as $payment
            ) {

                $moneyType =
                    $this->mapMoneyType(
                        $payment['moneyTypeCode']
                        ?? 'Other'
                    );

                $key = implode('|', [

                    'BalanceByMoneyType',

                    'LSL',

                    $moneyType,

                ]);

                if (! isset($counters[$key])) {

                    $counters[$key] = [

                        'fiscalCounterType' =>
                            'BalanceByMoneyType',

                        'fiscalCounterCurrency' =>
                            'LSL',

                        'fiscalCounterMoneyType' =>
                            $moneyType,

                        'fiscalCounterValue' =>
                            0,

                    ];
                }

                $counters[$key]['fiscalCounterValue'] +=
                    (float) (
                        $payment['paymentAmount']
                        ?? 0
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Round and remove zero counters.
        |--------------------------------------------------------------------------
        */

        return collect($counters)

            ->map(function (
                array $counter
            ) {

                $counter['fiscalCounterValue'] =
                    round(
                        $counter['fiscalCounterValue'],
                        2
                    );

                return $counter;
            })

            ->filter(
                fn (array $counter) =>
                    $counter['fiscalCounterValue'] != 0
            )

            ->values()

            ->all();
    }

    /**
     * Map POS payment type to Lekuka money type.
     */
    protected function mapMoneyType(
        string $moneyType
    ): string {

        return match (
            strtolower($moneyType)
        ) {

            'cash' =>
                'Cash',

            'card' =>
                'Card',

            'mobilemoney',
            'mobilewallet' =>
                'MobileWallet',

            'coupon' =>
                'Coupon',

            'credit' =>
                'Credit',

            'banktransfer' =>
                'BankTransfer',

            default =>
                'Other',
        };
    }
}
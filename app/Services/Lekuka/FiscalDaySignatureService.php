<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;

class FiscalDaySignatureService
{
    public function __construct(
        protected SignatureService $signature,
    ) {
    }

    /**
     * Build the canonical fiscal-period string according to
     * Lekuka API specification v1.11 section 13.3.1.
     *
     * Canonical fields:
     *
     * deviceID
     * fiscalDayNo
     * fiscalDayDate
     * fiscalDayCounters
     */
    public function buildCanonical(
        LekukaDevice $device,
        int $fiscalDayNo,
        string $fiscalDayDate,
        array $counters,
    ): string {

        return
            (string) $device->device_id .
            (string) $fiscalDayNo .
            $fiscalDayDate .
            $this->buildCountersCanonical($counters);
    }

    /**
     * Build the fiscal counter canonical string.
     *
     * Lekuka v1.11 ordering:
     *
     * 1. fiscalCounterType - enum numeric order
     * 2. fiscalCounterCurrency - alphabetical order
     * 3. fiscalCounterTaxID - ascending
     *    OR fiscalCounterMoneyType - enum numeric order
     *
     * Only non-zero counters are included.
     */
    protected function buildCountersCanonical(array $counters): string
    {
        /*
        |--------------------------------------------------------------------------
        | Lekuka v1.11 FiscalCounterType enum order
        |--------------------------------------------------------------------------
        |
        | SaleByTax          = 0
        | SaleTaxByTax       = 1
        | CreditNoteByTax    = 2
        | CreditNoteTaxByTax = 3
        | DebitNoteByTax     = 4
        | DebitNoteTaxByTax  = 5
        | BalanceByMoneyType = 6
        | PayoutByTax        = 7
        | PayoutTaxByTax     = 8
        |
        */

        $counterTypeOrder = [
            'SaleByTax'          => 0,
            'SaleTaxByTax'       => 1,
            'CreditNoteByTax'    => 2,
            'CreditNoteTaxByTax' => 3,
            'DebitNoteByTax'     => 4,
            'DebitNoteTaxByTax'  => 5,
            'BalanceByMoneyType' => 6,
            'PayoutByTax'        => 7,
            'PayoutTaxByTax'     => 8,
        ];

        /*
        |--------------------------------------------------------------------------
        | Lekuka v1.11 MoneyType enum order
        |--------------------------------------------------------------------------
        */

        $moneyTypeOrder = [
            'Cash'         => 0,
            'Card'         => 1,
            'MobileWallet' => 2,
            'Coupon'       => 3,
            'Credit'       => 4,
            'BankTransfer' => 5,
            'Other'        => 6,
        ];

        /*
        |--------------------------------------------------------------------------
        | Remove zero counters
        |--------------------------------------------------------------------------
        */

        $counters = collect($counters)
            ->filter(function (array $counter) {

                return (float) (
                    $counter['fiscalCounterValue'] ?? 0
                ) != 0;
            })

            ->sort(function (array $a, array $b) use (
                $counterTypeOrder,
                $moneyTypeOrder
            ) {

                /*
                |--------------------------------------------------------------------------
                | 1. FiscalCounterType enum order
                |--------------------------------------------------------------------------
                */

                $typeA = $counterTypeOrder[
                    $a['fiscalCounterType'] ?? ''
                ] ?? 999;

                $typeB = $counterTypeOrder[
                    $b['fiscalCounterType'] ?? ''
                ] ?? 999;

                if ($typeA !== $typeB) {
                    return $typeA <=> $typeB;
                }

                /*
                |--------------------------------------------------------------------------
                | 2. Currency alphabetical order
                |--------------------------------------------------------------------------
                */

                $currencyA = strtoupper(
                    $a['fiscalCounterCurrency'] ?? ''
                );

                $currencyB = strtoupper(
                    $b['fiscalCounterCurrency'] ?? ''
                );

                $currencyCompare = strcmp(
                    $currencyA,
                    $currencyB
                );

                if ($currencyCompare !== 0) {
                    return $currencyCompare;
                }

                /*
                |--------------------------------------------------------------------------
                | 3. Tax ID / Money Type order
                |--------------------------------------------------------------------------
                */

                if (
                    isset($a['fiscalCounterTaxID']) ||
                    isset($b['fiscalCounterTaxID'])
                ) {

                    return (
                        (int) ($a['fiscalCounterTaxID'] ?? 0)
                    ) <=> (
                        (int) ($b['fiscalCounterTaxID'] ?? 0)
                    );
                }

                $moneyA = $moneyTypeOrder[
                    $a['fiscalCounterMoneyType'] ?? ''
                ] ?? 999;

                $moneyB = $moneyTypeOrder[
                    $b['fiscalCounterMoneyType'] ?? ''
                ] ?? 999;

                return $moneyA <=> $moneyB;
            })

            ->values();

        /*
        |--------------------------------------------------------------------------
        | Build canonical counter string
        |--------------------------------------------------------------------------
        */

        $canonical = '';

        foreach ($counters as $counter) {

            /*
            |--------------------------------------------------------------------------
            | Counter type
            |--------------------------------------------------------------------------
            */

            $canonical .= strtoupper(
                $counter['fiscalCounterType']
            );

            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */

            $canonical .= strtoupper(
                $counter['fiscalCounterCurrency']
            );

            /*
            |--------------------------------------------------------------------------
            | Tax-based counter
            |--------------------------------------------------------------------------
            */

            if (
                isset($counter['fiscalCounterTaxRate'])
            ) {

                $rate = $counter['fiscalCounterTaxRate'];

                /*
                | Exempt tax:
                | empty tax rate in canonical string.
                */

                if ($rate === null) {

                    $canonical .= '';

                } else {

                    /*
                    | Lekuka requires:
                    |
                    | 15     -> 15.00
                    | 14.5   -> 14.50
                    | 0      -> 0.00
                    */

                    $canonical .= number_format(
                        (float) $rate,
                        2,
                        '.',
                        ''
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Money-type counter
            |--------------------------------------------------------------------------
            */

            elseif (
                isset($counter['fiscalCounterMoneyType'])
            ) {

                $canonical .= strtoupper(
                    $counter['fiscalCounterMoneyType']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Counter value
            |--------------------------------------------------------------------------
            |
            | Lekuka requires amounts in cents.
            |
            */

            $canonical .= (string) intval(
                round(
                    ((float) $counter['fiscalCounterValue']) * 100
                )
            );
        }

        return $canonical;
    }

    /**
     * Generate fiscal-day SHA256 hash and device signature.
     */
    public function sign(
        LekukaDevice $device,
        int $fiscalDayNo,
        string $fiscalDayDate,
        array $counters,
    ): array {

        $canonical = $this->buildCanonical(
            device: $device,
            fiscalDayNo: $fiscalDayNo,
            fiscalDayDate: $fiscalDayDate,
            counters: $counters,
        );

        $signature = $this->signature->sign(
            device: $device,
            canonicalData: $canonical,
        );

        return [
            'canonical' => $canonical,
            'hash' => $signature['hash'],
            'signature' => $signature['signature'],
        ];
    }
}
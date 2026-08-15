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
        | Remove zero-value counters
        |--------------------------------------------------------------------------
        */

        $counters = collect($counters)
            ->filter(function (array $counter) {

                return (float) ($counter['fiscalCounterValue'] ?? 0) != 0;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Lekuka FiscalCounterType enum order
        |--------------------------------------------------------------------------
        |
        | v1.11:
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
        | Lekuka MoneyType enum order
        |--------------------------------------------------------------------------
        |
        | v1.11:
        |
        | Cash         = 0
        | Card         = 1
        | MobileWallet = 2
        | Coupon       = 3
        | Credit       = 4
        | BankTransfer = 5
        | Other        = 6
        |
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
        | Sort counters exactly according to Lekuka specification
        |--------------------------------------------------------------------------
        */

        $counters = $counters
            ->sort(function (array $a, array $b) use (
                $counterTypeOrder,
                $moneyTypeOrder
            ) {

                /*
                |----------------------------------------------------------------------
                | 1. Fiscal counter type
                |---------------------------------------------------------------------- 
                */

                $typeA = $counterTypeOrder[
                    $a['fiscalCounterType'] ?? ''
                ] ?? PHP_INT_MAX;

                $typeB = $counterTypeOrder[
                    $b['fiscalCounterType'] ?? ''
                ] ?? PHP_INT_MAX;

                if ($typeA !== $typeB) {
                    return $typeA <=> $typeB;
                }

                /*
                |----------------------------------------------------------------------
                | 2. Currency alphabetical order
                |---------------------------------------------------------------------- 
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
                |----------------------------------------------------------------------
                | 3. Tax ID for "ByTax" counters
                |---------------------------------------------------------------------- 
                */

                $taxIdA = $a['fiscalCounterTaxID'] ?? null;
                $taxIdB = $b['fiscalCounterTaxID'] ?? null;

                if (
                    $taxIdA !== null ||
                    $taxIdB !== null
                ) {

                    $taxIdA = $taxIdA !== null
                        ? (int) $taxIdA
                        : PHP_INT_MAX;

                    $taxIdB = $taxIdB !== null
                        ? (int) $taxIdB
                        : PHP_INT_MAX;

                    if ($taxIdA !== $taxIdB) {
                        return $taxIdA <=> $taxIdB;
                    }
                }

                /*
                |----------------------------------------------------------------------
                | 4. Money type for BalanceByMoneyType
                |---------------------------------------------------------------------- 
                */

                $moneyA = $moneyTypeOrder[
                    $a['fiscalCounterMoneyType'] ?? ''
                ] ?? PHP_INT_MAX;

                $moneyB = $moneyTypeOrder[
                    $b['fiscalCounterMoneyType'] ?? ''
                ] ?? PHP_INT_MAX;

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
            |----------------------------------------------------------------------
            | Fiscal counter type
            |---------------------------------------------------------------------- 
            */

            $canonical .= strtoupper(
                $counter['fiscalCounterType'] ?? ''
            );

            /*
            |----------------------------------------------------------------------
            | Currency
            |---------------------------------------------------------------------- 
            */

            $canonical .= strtoupper(
                $counter['fiscalCounterCurrency'] ?? ''
            );

            /*
            |----------------------------------------------------------------------
            | Tax rate OR money type
            |---------------------------------------------------------------------- 
            */

            if (
                array_key_exists(
                    'fiscalCounterTaxRate',
                    $counter
                )
            ) {

                $rate = $counter['fiscalCounterTaxRate'];

                /*
                | Lekuka:
                |
                | 15    -> 15.00
                | 14.5  -> 14.50
                | 0     -> 0.00
                | null  -> empty string
                */

                if ($rate === null) {

                    $canonical .= '';

                } else {

                    $canonical .= number_format(
                        (float) $rate,
                        2,
                        '.',
                        ''
                    );
                }

            } elseif (
                array_key_exists(
                    'fiscalCounterMoneyType',
                    $counter
                )
            ) {

                $canonical .= strtoupper(
                    $counter['fiscalCounterMoneyType']
                );
            }

            /*
            |----------------------------------------------------------------------
            | Counter value
            |----------------------------------------------------------------------
            |
            | Lekuka requires monetary values in cents.
            |
            | 28.75 -> 2875
            | 15.00 -> 1500
            | 100.00 -> 10000
            |
            */

            $value = (float) (
                $counter['fiscalCounterValue'] ?? 0
            );

            $canonical .= (string) (
                intdiv(
                    (int) round($value * 100),
                    1
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
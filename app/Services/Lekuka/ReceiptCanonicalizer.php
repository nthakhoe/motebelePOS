<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;

class ReceiptCanonicalizer
{
    public function build(
        array $payload,
        LekukaDevice $device,
        ?string $previousReceiptHash = null
    ): string {

        $canonical = '';

        /*
        |--------------------------------------------------------------------------
        | 1. Device ID
        |--------------------------------------------------------------------------
        */

        $canonical .= $device->device_id;

        /*
        |--------------------------------------------------------------------------
        | 2. Receipt Type
        |--------------------------------------------------------------------------
        */

        $canonical .= strtoupper($payload['receiptType']);

        /*
        |--------------------------------------------------------------------------
        | 3. Currency
        |--------------------------------------------------------------------------
        */

        $canonical .= strtoupper($payload['receiptCurrency']);

        /*
        |--------------------------------------------------------------------------
        | 4. Receipt Global Number
        |--------------------------------------------------------------------------
        */

        $canonical .= $payload['receiptGlobalNo'];

        /*
        |--------------------------------------------------------------------------
        | 5. Receipt Date
        |--------------------------------------------------------------------------
        */

        $canonical .= $payload['receiptDate'];

        /*
        |--------------------------------------------------------------------------
        | 6. Receipt Total (cents)
        |--------------------------------------------------------------------------
        */

        $canonical .= (int) round($payload['receiptTotal'] * 100);

        /*
        |--------------------------------------------------------------------------
        | 7. Taxes
        |--------------------------------------------------------------------------
        */

        $taxes = collect($payload['receiptTaxes'])
            ->sort(function ($a, $b) {

                if ($a['taxID'] === $b['taxID']) {
                    return strcmp(
                        $a['taxCode'] ?? '',
                        $b['taxCode'] ?? ''
                    );
                }

                return $a['taxID'] <=> $b['taxID'];
            });

        foreach ($taxes as $tax) {

            $canonical .= $tax['taxCode'] ?? '';

            $canonical .= array_key_exists('taxRate', $tax)
                ? number_format(
                    (float) $tax['taxRate'],
                    2,
                    '.',
                    ''
                )
                : '';

            $canonical .= (int) round(
                $tax['taxAmount'] * 100
            );

            $canonical .= (int) round(
                $tax['salesAmountWithTax'] * 100
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Previous Receipt Hash
        |--------------------------------------------------------------------------
        */

        if (! empty($previousReceiptHash)) {
            $canonical .= $previousReceiptHash;
        }

        return $canonical;
    }
}
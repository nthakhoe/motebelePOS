<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use App\Models\LekukaFiscalDay;
use App\Models\LekukaReceipt;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReceiptPayloadBuilder
{
    public function __construct(
        protected ReceiptCanonicalizer $canonicalizer,
        protected SignatureService $signature,
    ) {
    }

    /**
     * Build Lekuka receipt payload.
     *
     * Receipt counters are supplied by ReceiptService.
     * This class does NOT allocate or increment fiscal counters.
     */
    public function build(
        Sale $sale,
        LekukaFiscalDay $day,
        LekukaDevice $device,
        int $receiptCounter,
        int $receiptGlobalNo,
    ): array {

        $sale->loadMissing([
            'items.product',
            'payments.paymentMethod',
            'customer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Basic Receipt Information
        |--------------------------------------------------------------------------
        */

        $payload = [

            'receiptType' => 'Receipt',

            'receiptCurrency' => 'LSL',

            'receiptCounter' => $receiptCounter,

            'receiptGlobalNo' => $receiptGlobalNo,

            'invoiceNo' => $sale->sale_number,

            'receiptDate' => $sale->created_at
                ? $sale->created_at->format('Y-m-d\TH:i:s')
                : now()->format('Y-m-d\TH:i:s'),

            /*
             * Your POS prices are VAT inclusive.
             */
            'receiptLinesTaxInclusive' => true,

            'taxRoundingType' => 'PerReceipt',

            'receiptLines' => [],

            'receiptTaxes' => [],

            'receiptPayments' => [],

            'receiptTotal' => round(
                (float) $sale->total,
                2
            ),

            'receiptPrintForm' => 'Receipt48',
        ];

        /*
        |--------------------------------------------------------------------------
        | Buyer
        |--------------------------------------------------------------------------
        */

        if (
            $sale->customer &&
            $sale->customer->customer_code !== 'WALK-IN'
        ) {

            $buyerData = [

                'buyerRegisterName' => trim(
                    $sale->customer->first_name . ' ' .
                    $sale->customer->last_name
                ),
            ];

            /*
             * Only include TIN when one actually exists.
             */
            if (!empty($sale->customer->tin)) {
                $buyerData['buyerTIN'] = $sale->customer->tin;
            }

            $payload['buyerData'] = $buyerData;
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt Lines
        |--------------------------------------------------------------------------
        */

        foreach ($sale->items as $index => $item) {

            $taxRate = (float) ($item->tax_rate ?? 0);

            $lineTotal = round(
                (float) $item->line_total,
                2
            );

            $line = [

                'receiptLineType' => 'Sale',

                'receiptLineSubType' => 'Product',

                'receiptLineNo' => $index + 1,

                'receiptLineHSCode' =>
                    $item->product?->hs_code,

                'receiptLineName' =>
                    $item->product?->product_name
                    ?? 'Unknown Product',

                'receiptLinePrice' => round(
                    (float) $item->unit_price,
                    2
                ),

                'receiptLineQuantity' => (float) $item->quantity,

                'receiptLineTotal' => $lineTotal,

                'taxCode' => 'A',

                'taxRate' => $taxRate,

                'taxID' => 1,
            ];

            $payload['receiptLines'][] = $line;
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt Taxes
        |--------------------------------------------------------------------------
        |
        | The POS stores prices INCLUDING VAT.
        |
        | Therefore:
        |
        | VAT = inclusive amount × rate / (100 + rate)
        |
        | Example:
        |
        | M140 at 15% VAT:
        |
        | 140 × 15 / 115 = M18.26 VAT
        |
        */

        $taxGroups = [];

        foreach ($sale->items as $item) {

            $taxRate = (float) ($item->tax_rate ?? 0);

            $lineTotal = round(
                (float) $item->line_total,
                2
            );

            /*
             * Group tax by rate.
             *
             * This makes the builder safe if the POS eventually
             * supports multiple VAT rates.
             */
            $taxKey = number_format(
                $taxRate,
                4,
                '.',
                ''
            );

            if (!isset($taxGroups[$taxKey])) {

                $taxGroups[$taxKey] = [
                    'taxRate' => $taxRate,
                    'salesAmountWithTax' => 0,
                    'taxAmount' => 0,
                ];
            }

            $taxGroups[$taxKey]['salesAmountWithTax'] += $lineTotal;

            if ($taxRate > 0) {

                $taxAmount = $lineTotal
                    * $taxRate
                    / (100 + $taxRate);

                $taxGroups[$taxKey]['taxAmount'] += $taxAmount;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build Lekuka Receipt Tax Records
        |--------------------------------------------------------------------------
        */

        foreach ($taxGroups as $taxGroup) {

            $taxRate = (float) $taxGroup['taxRate'];

            $salesAmountWithTax = round(
                $taxGroup['salesAmountWithTax'],
                2
            );

            $taxAmount = round(
                $taxGroup['taxAmount'],
                2
            );

            /*
             * Standard 15% VAT.
             *
             * If a zero-rated/exempt tax rate is introduced later,
             * this structure can be extended accordingly.
             */
            $payload['receiptTaxes'][] = [

                'taxCode' => 'A',

                'taxRate' => $taxRate,

                'taxID' => 1,

                'taxType' => 'VAT',

                'taxAmount' => $taxAmount,

                'salesAmountWithTax' => $salesAmountWithTax,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt Payments
        |--------------------------------------------------------------------------
        */

        foreach ($sale->payments as $payment) {

            $methodName = strtolower(
                trim(
                    $payment->paymentMethod?->name
                    ?? ''
                )
            );

            $moneyTypeCode = match ($methodName) {

                'cash' => 'Cash',

                'card' => 'Card',

                'mpesa',
                'm-pesa',
                'mobile money',
                'mobilemoney',
                'mobile money payment' => 'MobileMoney',

                'ecocash',
                'eco cash' => 'MobileMoney',

                'coupon' => 'Coupon',

                'credit' => 'Credit',

                'bank transfer',
                'banktransfer' => 'BankTransfer',

                default => 'Other',
            };

            $payload['receiptPayments'][] = [

                'moneyTypeCode' => $moneyTypeCode,

                'paymentAmount' => round(
                    (float) $payment->amount_paid,
                    2
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Previous Receipt Hash
        |--------------------------------------------------------------------------
        |
        | The previous receipt hash is part of the receipt signing chain.
        |
        | receipt_global_no is currently a VARCHAR column in your database,
        | therefore use a numeric CAST when ordering.
        |
        */

        $previousHash = LekukaReceipt::query()

            ->where(
                'device_id',
                $device->id
            )

            ->whereNotNull('device_hash')

            ->orderByRaw(
                'CAST(receipt_global_no AS UNSIGNED) DESC'
            )

            ->value('device_hash');

        /*
        |--------------------------------------------------------------------------
        | Canonical Receipt
        |--------------------------------------------------------------------------
        */

        $canonical = $this->canonicalizer->build(

            payload: $payload,

            device: $device,

            previousReceiptHash: $previousHash,
        );

        /*
        |--------------------------------------------------------------------------
        | Device Signature
        |--------------------------------------------------------------------------
        */

        $signature = $this->signature->sign(

            device: $device,

            canonicalData: $canonical,
        );

        /*
        |--------------------------------------------------------------------------
        | Attach Device Signature
        |--------------------------------------------------------------------------
        */

        $payload['receiptDeviceSignature'] = [

            'hash' =>
                $signature['hash'],

            'signature' =>
                $signature['signature'],

            'certificateThumbprint' =>
                $signature['certificateThumbprint']
                ?? $device->thumbprint,
        ];

        /*
        |--------------------------------------------------------------------------
        | Return Complete Receipt Package
        |--------------------------------------------------------------------------
        */

        return [

            'payload' => [

                'receipt' => $payload,

            ],

            'signature' => $signature,

            'canonical' => $canonical,

        ];
    }
}
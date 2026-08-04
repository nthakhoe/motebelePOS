<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use App\Models\LekukaFiscalDay;
use App\Models\LekukaReceipt;
use App\Models\Sale;

class ReceiptPayloadBuilder
{
    public function __construct(
        protected ReceiptCanonicalizer $canonicalizer,
        protected SignatureService $signature,
    ) {
    }

    /**
     * Build Lekuka receipt payload.
     */
    public function build(
        Sale $sale,
        LekukaFiscalDay $day,
        LekukaDevice $device,
    ): array {

        $sale->loadMissing([
            'items.product',
            'payments.paymentMethod',
            'customer',
        ]);

        $payload = [

            'receiptType' => 'Receipt',

            'receiptCurrency' => 'LSL',

            'receiptCounter' => $device->last_receipt_counter + 1,

            'receiptGlobalNo' => $device->last_global_receipt_no + 1,

            // Uncomment if required by the latest SubmitReceipt specification
            // 'fiscalDayNo' => $day->fiscal_day_no,

            'invoiceNo' => $sale->sale_number,

            'receiptDate' => $sale->created_at
                ->format('Y-m-d\TH:i:s'),

            'receiptLinesTaxInclusive' => true,

            'taxRoundingType' => 'PerReceipt',

            'receiptLines' => [],

            'receiptTaxes' => [],

            'receiptPayments' => [],

            'receiptTotal' => (float) $sale->total,

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

            $payload['buyerData'] = [

                'buyerRegisterName' => trim(
                    $sale->customer->first_name . ' ' .
                    $sale->customer->last_name
                ),

                'buyerTIN' => $sale->customer->tin,

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt Lines
        |--------------------------------------------------------------------------
        */

        foreach ($sale->items as $index => $item) {

            $payload['receiptLines'][] = [

                'receiptLineType' => 'Sale',

                'receiptLineSubType' => 'Product',

                'receiptLineNo' => $index + 1,

                'receiptLineHSCode' => $item->product->hs_code,

                'receiptLineName' => $item->product->product_name,

                'receiptLinePrice' => (float) $item->unit_price,

                'receiptLineQuantity' => (float) $item->quantity,

                'receiptLineTotal' => (float) $item->line_total,

                'taxCode' => 'A',

                'taxRate' => (float) $item->tax_rate,

                'taxID' => 1,

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt Taxes
        |--------------------------------------------------------------------------
        */

        $payload['receiptTaxes'][] = [

            'taxCode' => 'A',

            'taxRate' => 15,

            'taxID' => 1,

            'taxType' => 'VAT',

            'taxAmount' => (float) $sale->vat,

            'salesAmountWithTax' => (float) $sale->total,

        ];

        /*
        |--------------------------------------------------------------------------
        | Receipt Payments
        |--------------------------------------------------------------------------
        */
        foreach ($sale->payments as $payment) {

            $payload['receiptPayments'][] = [

                'moneyTypeCode' => match (
                    strtolower($payment->paymentMethod->name)
                ) {

                    'cash' => 'Cash',

                    'card' => 'Card',

                    'mpesa' => 'MobileMoney',

                    'ecocash' => 'MobileMoney',

                    default => 'Other',
                },

                'paymentAmount' => (float) $payment->amount_paid,

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Previous Receipt Hash
        |--------------------------------------------------------------------------
        */

        $previousHash = LekukaReceipt::where(
                'device_id',
                $device->id
            )
            ->orderByDesc('receipt_counter')
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

        $payload['receiptDeviceSignature'] = $signature;

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
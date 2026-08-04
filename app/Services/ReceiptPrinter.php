<?php

namespace App\Services;

use App\Models\Sale;

class ReceiptPrinter
{
    /**
     * Prepare receipt data for printing.
     */
    public function build(Sale $sale): array
    {
        $sale->loadMissing([

            'customer',

            'cashier',

            'items.product',

            'payments.paymentMethod',

            'lekukaReceipt',

            'branch',

            'company',

        ]);

        $receipt = $sale->lekukaReceipt;

        return [

            /*
            |--------------------------------------------------------------------------
            | Header
            |--------------------------------------------------------------------------
            */

            'company' => [

                'name' => $sale->branch?->branch_name
                    ?? $sale->company?->company_name,

                'address' => $sale->branch?->address,

                'phone' => $sale->branch?->phone,

                'tin' => $sale->company?->tax_number,

            ],

            /*
            |--------------------------------------------------------------------------
            | Receipt Details
            |--------------------------------------------------------------------------
            */

            'receipt' => [

                'title' => 'FISCAL RECEIPT',

                'sale_number' => $sale->sale_number,

                'receipt_number' => $receipt?->receipt_number,

                'receipt_global_no' => $receipt?->receipt_global_no,

                'receipt_counter' => $receipt?->receipt_counter,

                'fiscal_day_no' => $receipt?->fiscal_day_no,

                'date' => $sale->created_at
                    ->format('d/m/Y H:i'),

                'cashier' => $sale->cashier?->name,

                'customer' => $sale->customer
                    ? trim(
                        $sale->customer->first_name .
                        ' ' .
                        $sale->customer->last_name
                    )
                    : 'Walk-In Customer',

            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => $sale->items->map(function ($item) {

                return [

                    'name' => $item->product->product_name,

                    'qty' => $item->quantity,

                    'price' => (float) $item->unit_price,

                    'total' => (float) $item->line_total,

                ];

            })->toArray(),

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            'totals' => [

                'subtotal' => (float) $sale->subtotal,

                'discount' => (float) $sale->discount,

                'vat' => (float) $sale->tax,

                'total' => (float) $sale->total,

            ],

            /*
            |--------------------------------------------------------------------------
            | Payments
            |--------------------------------------------------------------------------
            */

            'payments' => $sale->payments->map(function ($payment) {

                return [

                    'method' => $payment->paymentMethod->name,

                    'received' => (float) $payment->amount_received,

                    'paid' => (float) $payment->amount_paid,

                    'change' => (float) $payment->change_amount,

                ];

            })->toArray(),

            /*
            |--------------------------------------------------------------------------
            | Lekuka
            |--------------------------------------------------------------------------
            */

            'lekuka' => [

                'verification_code' => $receipt?->verification_code,

                'qr_code' => $receipt?->qr_code,

                'server_signature' => $receipt?->server_signature,

                'status' => $receipt?->status,

            ],

            /*
            |--------------------------------------------------------------------------
            | Footer
            |--------------------------------------------------------------------------
            */

            'footer' => [

                'Thank you for shopping with us.',

                'Powered by Motebele POS',

            ],

        ];
    }

    /**
     * Centre text on thermal paper.
     */
    public function center(
        string $text,
        int $width = 32
    ): string {

        return str_pad(
            $text,
            $width,
            ' ',
            STR_PAD_BOTH
        );
    }

    /**
     * Separator line.
     */
    public function line(
        int $width = 32
    ): string {

        return str_repeat('-', $width);
    }

    /**
     * Format currency.
     */
    public function money(
        float $amount
    ): string {

        return number_format($amount, 2);
    }
}
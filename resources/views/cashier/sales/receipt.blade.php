<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Receipt {{ $sale->sale_number }}
    </title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        .receipt-wrapper {
            width: 380px;
            margin: 30px auto;
        }

        .receipt {
            background: white;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
        }

        .business {
            text-align: center;
            margin-bottom: 18px;
        }

        .business-name {
            font-size: 22px;
            font-weight: 700;
        }

        .business-details {
            font-size: 12px;
            line-height: 1.5;
            color: #4b5563;
        }

        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            margin: 15px 0;
            text-transform: uppercase;
        }

        .divider {
            border-top: 1px dashed #9ca3af;
            margin: 12px 0;
        }

        .meta {
            font-size: 12px;
            line-height: 1.7;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .meta-label {
            color: #6b7280;
        }

        .meta-value {
            text-align: right;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 12px;
        }

        th {
            text-align: left;
            border-bottom: 1px solid #111827;
            padding-bottom: 6px;
        }

        td {
            padding: 7px 0;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .item-name {
            font-weight: 600;
        }

        .item-unit {
            color: #6b7280;
            font-size: 10px;
        }

        .totals {
            margin-top: 10px;
            font-size: 13px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .grand-total {
            font-size: 17px;
            font-weight: 700;
            padding-top: 8px;
            margin-top: 5px;
            border-top: 1px solid #111827;
        }

        .payment-section {
            margin-top: 14px;
            font-size: 12px;
        }

        .payment {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .status {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .refund-notice {
            text-align: center;
            border: 1px dashed #111827;
            padding: 8px;
            margin-top: 15px;
            font-weight: 700;
            font-size: 12px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #6b7280;
            line-height: 1.5;
        }

        .print-button {
            display: block;
            width: 380px;
            margin: 0 auto 20px;
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: #111827;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }

        .print-button:hover {
            background: #1f2937;
        }

        @media print {

            @page {
                size: 80mm auto;
                margin: 4mm;
            }

            html,
            body {
                background: white;
                width: 80mm;
            }

            .receipt-wrapper {
                width: 100%;
                margin: 0;
            }

            .receipt {
                width: 100%;
                padding: 0;
                box-shadow: none;
            }

            .print-button {
                display: none;
            }
        }

    </style>
</head>

<body>

<div class="receipt-wrapper">

    <button
        type="button"
        class="print-button"
        onclick="window.print()"
    >
        🖨 Print Receipt
    </button>

    <div class="receipt">

        {{-- BUSINESS --}}
        <div class="business">

            <div class="business-name">
                {{ $sale->company?->name ?? 'Motebele POS' }}
            </div>

            <div class="business-details">

                @if($sale->branch?->name)
                    {{ $sale->branch->name }}<br>
                @endif

                @if($sale->branch?->address)
                    {{ $sale->branch->address }}<br>
                @endif

                @if($sale->company?->phone)
                    Tel: {{ $sale->company->phone }}<br>
                @endif

                @if($sale->company?->email)
                    {{ $sale->company->email }}
                @endif

            </div>

        </div>

        <div class="receipt-title">
            Sales Receipt
        </div>

        <div class="divider"></div>

        {{-- SALE INFORMATION --}}
        <div class="meta">

            <div class="meta-row">
                <span class="meta-label">
                    Receipt
                </span>

                <span class="meta-value">
                    {{ $sale->sale_number }}
                </span>
            </div>

            <div class="meta-row">
                <span class="meta-label">
                    Date
                </span>

                <span class="meta-value">
                    {{ $sale->created_at?->format('d M Y H:i') }}
                </span>
            </div>

            @if($sale->cashier)
                <div class="meta-row">
                    <span class="meta-label">
                        Cashier
                    </span>

                    <span class="meta-value">
                        {{ $sale->cashier->name }}
                    </span>
                </div>
            @endif

            @if($sale->customer)
                <div class="meta-row">
                    <span class="meta-label">
                        Customer
                    </span>

                    <span class="meta-value">
                        {{ $sale->customer->first_name }}
                        {{ $sale->customer->last_name ?? '' }}
                    </span>
                </div>
            @endif

        </div>

        <div class="divider"></div>

        {{-- ITEMS --}}
        <table>

            <thead>
                <tr>
                    <th>
                        Item
                    </th>

                    <th class="text-right">
                        Qty
                    </th>

                    <th class="text-right">
                        Amount
                    </th>
                </tr>
            </thead>

            <tbody>

                @foreach($sale->items as $item)

                    <tr>

                        <td>

                            <div class="item-name">
                                {{ $item->product?->product_name ?? 'Product' }}
                            </div>

                            @if($item->unit)
                                <div class="item-unit">
                                    {{ $item->unit->name }}
                                </div>
                            @endif

                        </td>

                        <td class="text-right">
                            {{ number_format((float) $item->quantity, 3) }}
                        </td>

                        <td class="text-right">
                            M{{ number_format((float) $item->line_total, 2) }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

        <div class="divider"></div>

        {{-- TOTALS --}}
        <div class="totals">

            <div class="total-row">

                <span>
                    Subtotal
                </span>

                <span>
                    M{{ number_format((float) $sale->subtotal, 2) }}
                </span>

            </div>

            @if((float) $sale->discount > 0)

                <div class="total-row">

                    <span>
                        Discount
                    </span>

                    <span>
                        -M{{ number_format((float) $sale->discount, 2) }}
                    </span>

                </div>

            @endif

            @if((float) $sale->tax > 0)

                <div class="total-row">

                    <span>
                        Tax
                    </span>

                    <span>
                        M{{ number_format((float) $sale->tax, 2) }}
                    </span>

                </div>

            @endif

            <div class="total-row grand-total">

                <span>
                    TOTAL
                </span>

                <span>
                    M{{ number_format((float) $sale->total, 2) }}
                </span>

            </div>

        </div>

        {{-- PAYMENTS --}}
        @if($sale->payments->count())

            <div class="payment-section">

                <div class="divider"></div>

                <strong>
                    Payment
                </strong>

                @foreach($sale->payments as $payment)

                    <div class="payment">

                        <span>
                            {{ $payment->paymentMethod?->name
                                ?? ucfirst($sale->sale_type ?? 'Payment') }}
                        </span>

                        <span>
                            M{{ number_format((float) $payment->amount_paid, 2) }}
                        </span>

                    </div>

                @endforeach

                @if((float) $sale->change > 0)

                    <div class="payment">

                        <span>
                            Change
                        </span>

                        <span>
                            M{{ number_format((float) $sale->change, 2) }}
                        </span>

                    </div>

                @endif

            </div>

        @endif

        {{-- REFUND STATUS --}}
        @if($sale->status === 'refunded')

            <div class="refund-notice">
                REFUNDED
            </div>

        @elseif($sale->status === 'partially_refunded')

            <div class="refund-notice">
                PARTIALLY REFUNDED
            </div>

        @endif

        {{-- STATUS --}}
        <div class="status">

            {{ strtoupper(str_replace('_', ' ', $sale->status)) }}

        </div>

        {{-- FOOTER --}}
        <div class="footer">

            Thank you for your business.

            <br>

            Powered by Motebele POS

        </div>

    </div>

</div>

<script>
    window.addEventListener('load', function () {
        // Uncomment this if you want the print dialog
        // to open automatically when Reprint is selected.
        //
        // window.print();
    });
</script>

</body>
</html>
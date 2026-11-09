<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Preliminary Bill - {{ $order->order_number }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="{{ asset('css/preliminary-bill.css') }}">
</head>

<body>

<div class="bill-container">

    {{-- COMPANY INFORMATION --}}
    <header class="bill-header">

        <h1>
            {{ $company->trading_name ?: $company->company_name }}
        </h1>

        <p>{{ $branch->branch_name }}</p>

        @if($branch->address)
            <p>{{ $branch->address }}</p>
        @endif

        @if($branch->phone)
            <p>Tel: {{ $branch->phone }}</p>
        @endif

    </header>


    <div class="bill-title">
        PRELIMINARY BILL
    </div>

    <div class="bill-warning">
        NOT A FISCAL RECEIPT
    </div>


    {{-- ORDER INFORMATION --}}
    <section class="bill-information">

        <div>
            <span>Order Number</span>
            <strong>{{ $order->order_number }}</strong>
        </div>

        <div>
            <span>Table</span>
            <strong>{{ $order->table?->table_number ?? 'N/A' }}</strong>
        </div>

        <div>
            <span>Dining Area</span>
            <strong>{{ $order->table?->area?->name ?? 'N/A' }}</strong>
        </div>

        <div>
            <span>Waiter</span>
            <strong>{{ $order->waiter?->name ?? 'N/A' }}</strong>
        </div>

        <div>
            <span>Guests</span>
            <strong>{{ $order->guest_count }}</strong>
        </div>

        <div>
            <span>Date</span>
            <strong>{{ now()->format('d/m/Y H:i') }}</strong>
        </div>

    </section>


    {{-- ITEMS --}}
    <table class="bill-items">

        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>

            @foreach($order->items as $item)

                <tr>
                    <td>
                        {{ $item->product_name }}

                        @if($item->special_instructions)
                            <small>
                                {{ $item->special_instructions }}
                            </small>
                        @endif
                    </td>

                    <td>{{ number_format($item->quantity, 2) }}</td>

                    <td>{{ number_format($item->unit_price, 2) }}</td>

                    <td>{{ number_format($item->total_amount, 2) }}</td>
                </tr>

            @endforeach

        </tbody>

    </table>


    {{-- TOTALS --}}
    <section class="bill-totals">

        <div>
            <span>Subtotal</span>
            <strong>{{ number_format($order->subtotal, 2) }}</strong>
        </div>

        <div>
            <span>Tax Included</span>
            <strong>{{ number_format($order->tax_amount, 2) }}</strong>
        </div>

        <div>
            <span>Discount</span>
            <strong>{{ number_format($order->discount_amount, 2) }}</strong>
        </div>

        <div class="bill-grand-total">
            <span>TOTAL PAYABLE</span>
            <strong>{{ number_format($order->total_amount, 2) }}</strong>
        </div>

    </section>


    @if($order->notes)

        <div class="bill-notes">
            <strong>Order Notes:</strong>
            <p>{{ $order->notes }}</p>
        </div>

    @endif


    <footer class="bill-footer">

        <p>Thank you for dining with us.</p>

        <strong>PRELIMINARY BILL – NOT A FISCAL RECEIPT</strong>

        <p>
            Payment must be processed through the cashier.
        </p>

    </footer>


    <div class="bill-actions no-print">

        <button onclick="window.print()">
            Print Preliminary Bill
        </button>

        <button onclick="window.close()" class="secondary">
            Close
        </button>

    </div>

</div>

</body>
</html>
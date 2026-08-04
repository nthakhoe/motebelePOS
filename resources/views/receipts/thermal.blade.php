<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>Fiscal Receipt</title>

    <style>

        @page{
            margin:0;
            size:58mm auto;
        }

        body{

            width:58mm;

            margin:0 auto;

            padding:4mm;

            font-family: "Courier New", monospace;

            font-size:11px;

            color:#000;

            line-height:1.25;

        }

        .center{
            text-align:center;
        }

        .right{
            text-align:right;
        }

        .bold{
            font-weight:bold;
        }

        .line{

            border-top:1px dashed #000;

            margin:6px 0;

        }

        table{

            width:100%;

            border-collapse:collapse;

        }

        td{

            padding:1px 0;

            vertical-align:top;

        }

        .item-name{

            width:100%;

        }

        .qty{

            width:12%;

        }

        .price{

            width:22%;

            text-align:right;

        }

        .total{

            width:22%;

            text-align:right;

        }

        img{

            max-width:140px;

            display:block;

            margin:auto;

        }

    </style>

</head>

<body>

{{-- ========================================================= --}}
{{-- Header --}}
{{-- ========================================================= --}}

<div class="center bold">

    {{ strtoupper($receipt['company']['name']) }}

</div>

@if($receipt['company']['address'])

<div class="center">

    {{ $receipt['company']['address'] }}

</div>

@endif

@if($receipt['company']['phone'])

<div class="center">

    Tel: {{ $receipt['company']['phone'] }}

</div>

@endif

@if($receipt['company']['tin'])

<div class="center">

    TIN: {{ $receipt['company']['tin'] }}

</div>

@endif

<div class="line"></div>

<div class="center bold">

    {{ $receipt['receipt']['title'] }}

</div>

<div class="line"></div>

<table>

<tr>

<td>Receipt No</td>

<td class="right">

{{ $receipt['receipt']['receipt_number'] }}

</td>

</tr>

<tr>

<td>Invoice</td>

<td class="right">

{{ $receipt['receipt']['sale_number'] }}

</td>

</tr>

<tr>

<td>Global No</td>

<td class="right">

{{ $receipt['receipt']['receipt_global_no'] }}

</td>

</tr>

<tr>

<td>Counter</td>

<td class="right">

{{ $receipt['receipt']['receipt_counter'] }}

</td>

</tr>

<tr>

<td>Fiscal Day</td>

<td class="right">

{{ $receipt['receipt']['fiscal_day_no'] }}

</td>

</tr>

<tr>

<td>Date</td>

<td class="right">

{{ $receipt['receipt']['date'] }}

</td>

</tr>

<tr>

<td>Cashier</td>

<td class="right">

{{ $receipt['receipt']['cashier'] }}

</td>

</tr>

<tr>

<td>Customer</td>

<td class="right">

{{ $receipt['receipt']['customer'] }}

</td>

</tr>

</table>

<div class="line"></div>

<table>

<tr class="bold">

<td class="qty">Qty</td>

<td class="item-name">Item</td>

<td class="price">Price</td>

<td class="total">Total</td>

</tr>

</table>

<div class="line"></div>

@foreach($receipt['items'] as $item)

<table>

<tr>

<td class="qty">

{{ number_format($item['qty'],0) }}

</td>

<td class="item-name">

{{ \Illuminate\Support\Str::limit($item['name'],18) }}

</td>

<td class="price">

{{ number_format($item['price'],2) }}

</td>

<td class="total">

{{ number_format($item['total'],2) }}

</td>

</tr>

</table>

@endforeach

<div class="line"></div>

<table>

<tr>

<td>Subtotal</td>

<td class="right">

{{ number_format($receipt['totals']['subtotal'],2) }}

</td>

</tr>

@if($receipt['totals']['discount']>0)

<tr>

<td>Discount</td>

<td class="right">

-{{ number_format($receipt['totals']['discount'],2) }}

</td>

</tr>

@endif

<tr>

<td>VAT</td>

<td class="right">

{{ number_format($receipt['totals']['vat'],2) }}

</td>

</tr>

<tr class="bold">

<td>TOTAL</td>

<td class="right">

{{ number_format($receipt['totals']['total'],2) }}

</td>

</tr>

</table>

<div class="line"></div>

<div class="bold">

PAYMENT

</div>

@foreach($receipt['payments'] as $payment)

<table>

<tr>

<td>

{{ $payment['method'] }}

</td>

<td class="right">

{{ number_format($payment['paid'],2) }}

</td>

</tr>

@if($payment['change']>0)

<tr>

<td>Change</td>

<td class="right">

{{ number_format($payment['change'],2) }}

</td>

</tr>

@endif

</table>

@endforeach

<div class="line"></div>

@if($receipt['lekuka']['verification_code'])

<div class="center">

Verification Code

</div>

<div class="center bold">

{{ $receipt['lekuka']['verification_code'] }}

</div>

@endif

@if($receipt['lekuka']['qr_code'])

<br>

<div class="center">

<img src="{{ $receipt['lekuka']['qr_code'] }}" alt="QR Code">

</div>

@endif

<div class="line"></div>

@foreach($receipt['footer'] as $line)

<div class="center">

{{ $line }}

</div>

@endforeach

<br>

<div class="center">

Powered by Motebele POS

</div>

<script>

window.onload = function(){

    window.print();

};

</script>

</body>

</html>
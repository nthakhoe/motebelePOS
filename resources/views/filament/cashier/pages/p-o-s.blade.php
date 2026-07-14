
<x-filament-panels::page>
    
    <link rel="stylesheet" href="{{ asset('css/pos.css') }}">

    <div class="pos-wrapper">

        @include('pos.header')

        <div class="pos-body">

            @include('pos.categories')

            @include('pos.products')

            @include('pos.cart')

        </div>

        @include('pos.footer')

    </div>
<x-filament-actions::modals />
</x-filament-panels::page>
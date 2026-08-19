<x-filament-widgets::widget>

    <div class="company-top-products">

        {{-- =====================================================
             HEADER
             ===================================================== --}}
        <div class="company-top-products-header">

            <div>

                <h2 class="company-top-products-title">
                    Top Selling Products
                </h2>

                <p class="company-top-products-description">
                    Best-selling products today
                </p>

            </div>

            <div class="company-top-products-period">
                Today
            </div>

        </div>


        {{-- =====================================================
             PRODUCTS
             ===================================================== --}}
        @if(count($products))

            <div class="company-top-products-list">

                @foreach($products as $index => $product)

                    <div class="company-top-product-row">

                        {{-- Rank --}}
                        <div class="company-top-product-rank">

                            {{ $index + 1 }}

                        </div>


                        {{-- Product --}}
                        <div class="company-top-product-info">

                            <div class="company-top-product-name">
                                {{ $product['name'] }}
                            </div>

                            <div class="company-top-product-sales">

                                M{{ number_format(
                                    $product['sales'],
                                    2
                                ) }}

                            </div>

                        </div>


                        {{-- Quantity --}}
                        <div class="company-top-product-quantity">

                            <span class="company-top-product-quantity-value">

                                {{ rtrim(
                                    rtrim(
                                        number_format(
                                            $product['quantity'],
                                            3,
                                            '.',
                                            ''
                                        ),
                                        '0'
                                    ),
                                    '.'
                                ) }}

                            </span>

                            <span class="company-top-product-quantity-label">
                                sold
                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            {{-- =================================================
                 EMPTY STATE
                 ================================================= --}}
            <div class="company-top-products-empty">

                <div class="company-top-products-empty-icon">

                    <x-heroicon-o-cube />

                </div>

                <div class="company-top-products-empty-title">
                    No products sold
                </div>

                <div class="company-top-products-empty-description">
                    Completed product sales will appear here.
                </div>

            </div>

        @endif

    </div>

</x-filament-widgets::widget>
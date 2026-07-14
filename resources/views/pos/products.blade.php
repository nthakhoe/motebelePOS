<div class="products-panel">

<div class="product-grid">

    @foreach($products as $product)

        <div

            class="product-card"

            wire:click="addToCart({{ $product->id }})"

        >

            <div class="product-image">

                📦                

            </div>

        <div class="product-details">

            <div class="product-name">

                {{ $product->product_name }}

            </div>

            <div class="product-price">

                M {{ number_format($product->selling_price,2) }}

            </div>
        </div>

        </div>

    @endforeach

</div>

</div>
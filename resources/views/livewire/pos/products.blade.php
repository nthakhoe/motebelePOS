<div class="products-panel">

    <div class="products-header">

        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search product or barcode..."
            class="search-input"
        >

    </div>
    <div class="product-grid">

        <div class="barcode-search">

            <input
                type="text"
                wire:model.live="barcode"
                wire:keydown.enter="scanBarcode"
                class="barcode-input"
                placeholder="Scan barcode or type barcode then press Enter"
                autofocus
            >

        </div>

        @forelse($products as $product)

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

                    <div class="product-category">
                        {{ $product->category?->category_name }}
                    </div>

                    <div class="product-price">

                        {{ number_format($product->selling_price,2) }}

                    </div>

                </div>

            </div>

        @empty

            <div class="empty-products">

                <i class="fas fa-box-open fa-3x"></i>

                <p>No products found.</p>

            </div>

        @endforelse

    </div>

</div>
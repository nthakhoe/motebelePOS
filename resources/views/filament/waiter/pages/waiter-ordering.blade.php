<x-filament-panels::page>

<link rel="stylesheet" href="{{ asset('css/waiter-ordering.css') }}">

<div class="waiter-ordering">

    {{-- LEFT: TABLE SELECTION --}}
    <aside class="ordering-sidebar">

        <div class="ordering-panel-heading">
            <h3>Restaurant Floor</h3>
            <span>Select a table</span>
        </div>

        @if($branches->count() > 1)
            <select wire:model.live="selectedBranchId" class="ordering-input">
                @foreach($branches as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        @endif

        @foreach($areas as $area)

            <div class="ordering-area">

                <button
                    type="button"
                    class="ordering-area-title"
                    wire:click="selectArea({{ $area->id }})"
                >
                    {{ $area->name }}
                </button>

                <div class="ordering-table-list">

                    @foreach($area->tables as $table)

                        <button
                            type="button"
                            wire:click="selectTable({{ $table->id }})"
                            @disabled(!in_array($table->status, ['available', 'occupied']))
                            class="ordering-table-button
                                {{ $table->status }}
                                {{ $selectedTableId === $table->id ? 'selected' : '' }}"
                        >
                            <strong>{{ $table->table_number }}</strong>
                            <small>{{ $table->capacity }} seats</small>
                            <span>{{ ucfirst($table->status) }}</span>
                        </button>

                    @endforeach

                </div>

            </div>

        @endforeach

    </aside>


    {{-- CENTRE: PRODUCT CATALOGUE --}}
    <main class="ordering-products">

        <div class="ordering-products-header">
            <div>
                <h2>Product Catalogue</h2>
                <p>Select products to add to the order.</p>
            </div>

            <input
                type="search"
                wire:model.live.debounce.350ms="search"
                placeholder="Search products..."
                class="ordering-search"
            >
        </div>

        <div class="ordering-categories">

            <button
                type="button"
                wire:click="selectCategory(null)"
                class="{{ !$selectedCategoryId ? 'active' : '' }}"
            >
                All Products
            </button>

            @foreach($categories as $category)

                <button
                    type="button"
                    wire:click="selectCategory({{ $category->id }})"
                    class="{{ $selectedCategoryId === $category->id ? 'active' : '' }}"
                >
                    {{ $category->category_name }}
                </button>

            @endforeach

        </div>

        <div class="ordering-product-grid">

            @forelse($products as $product)

                <button
                    type="button"
                    wire:click="addProduct({{ $product->id }})"
                    class="ordering-product-card"
                >

                    <div class="product-placeholder">
                        @if($product->image)
                            <img
                                src="{{ asset('storage/' . $product->image) }}"
                                alt="{{ $product->product_name }}"
                            >
                        @else
                            <span>＋</span>
                        @endif
                    </div>

                    <div class="product-card-details">
                        <strong>{{ $product->product_name }}</strong>
                        <span>{{ $product->sku }}</span>
                        <b>
                            {{ number_format($product->selling_price, 2) }}
                        </b>
                    </div>

                </button>

            @empty

                <div class="ordering-no-products">
                    No products found.
                </div>

            @endforelse

        </div>

    </main>


    {{-- RIGHT: CURRENT ORDER --}}
    <aside class="ordering-cart">

        <div class="cart-heading">
            <div>
                <h3>{{ $editingOrderId ? 'Add Items to Order' : 'New Restaurant Order' }}</h3>
                <span>{{ Auth::user()->name }}</span>
            </div>

            <button
                type="button"
                wire:click="startNewOrder"
                class="cart-clear-button"
            >
                Clear
            </button>
        </div>

        <div class="cart-table-selection">

            @if($selectedTable)

                <strong>{{ $selectedTable->table_number }}</strong>

                <span>{{ $selectedTable->area->name }}</span>

            @else

                <strong>No table selected</strong>
                <span>Select an available table first.</span>

            @endif

            @if($existingOrder)

                <div class="existing-order-summary">

                    <h4>Previously Submitted Items</h4>

                    @foreach($existingOrder->items as $existingItem)

                        <div class="existing-order-item">

                            <div>
                                <strong>{{ $existingItem->product_name }}</strong>

                                <small>
                                    {{ number_format($existingItem->quantity, 3) }}
                                    ×
                                    {{ number_format($existingItem->unit_price, 2) }}
                                </small>
                            </div>

                            <strong>
                                {{ number_format($existingItem->total_amount, 2) }}
                            </strong>

                        </div>

                    @endforeach

                    <div class="existing-order-total">
                        Existing Order Total:
                        {{ number_format($existingOrder->total_amount, 2) }}
                    </div>

                    <p class="additional-items-label">
                        Add additional products below.
                    </p>

                </div>

            @endif

        </div>

        <div class="guest-selector">
            <label>Number of Guests</label>

            <div>
                <button
                    type="button"
                    wire:click="$set('guestCount', max(1, guestCount - 1))"
                >−</button>

                <input
                    type="number"
                    min="1"
                    max="100"
                    wire:model="guestCount"
                >

                <button
                    type="button"
                    wire:click="$set('guestCount', min(100, guestCount + 1))"
                >+</button>
            </div>
        </div>

        <div class="cart-items">

            @forelse($cart as $item)

                <div class="cart-item">

                    <div class="cart-item-details">

                        <strong>{{ $item['product_name'] }}</strong>

                        <span>
                            {{ number_format($item['unit_price'], 2) }}
                            × {{ $item['quantity'] }}
                        </span>

                    </div>

                    <div class="cart-item-total">

                        <strong>
                            {{ number_format($item['unit_price'] * $item['quantity'], 2) }}
                        </strong>

                        <div class="quantity-controls">

                            <button
                                type="button"
                                wire:click="updateQuantity({{ $item['product_id'] }}, -1)"
                            >−</button>

                            <span>{{ $item['quantity'] }}</span>

                            <button
                                type="button"
                                wire:click="updateQuantity({{ $item['product_id'] }}, 1)"
                            >+</button>

                        </div>

                    </div>

                    <textarea
                        placeholder="Special instructions..."
                        wire:change="updateInstructions({{ $item['product_id'] }}, $event.target.value)"
                    >{{ $item['special_instructions'] }}</textarea>

                    <button
                        type="button"
                        class="remove-cart-item"
                        wire:click="removeProduct({{ $item['product_id'] }})"
                    >
                        Remove
                    </button>

                </div>

            @empty

                <div class="cart-empty">
                    <span>🛒</span>
                    <p>Your order is empty.</p>
                    <small>Select products from the catalogue.</small>
                </div>

            @endforelse

        </div>

        <div class="cart-notes">
            <label>Order Notes</label>

            <textarea
                wire:model="orderNotes"
                placeholder="Additional instructions for this order..."
            ></textarea>
        </div>

        <div class="cart-summary">

            <div>
                <span>Items</span>
                <strong>{{ count($cart) }}</strong>
            </div>

            <div class="cart-total">
                <span>Order Total</span>
                <strong>{{ number_format($this->subtotal, 2) }}</strong>
            </div>

        </div>

        <button
            type="button"
            wire:click="submitOrder"
            wire:loading.attr="disabled"
            @disabled(!$selectedTable || empty($cart))
            class="submit-restaurant-order"
        >
            <span wire:loading.remove wire:target="submitOrder">
                {{ $editingOrderId ? 'Submit Additional Items' : 'Submit Restaurant Order' }}
            </span>

            <span wire:loading wire:target="submitOrder">
                Submitting...
            </span>
        </button>

    </aside>

</div>




</x-filament-panels::page>
<div class="cart-panel">

<div class="customer-card">

    <div>

        <small>Customer</small>

        <h3>

            👤 {{ $customer?->first_name }}

        </h3>

        @if($customer?->phone)

            <span>{{ $customer->phone }}</span>

        @endif

    </div>

    {{ $this->customerAction }}

</div>

        <div class="summary-row total">

        <span>Total</span>

        <strong>

            M{{ number_format($total,2) }}

        </strong>

    </div>

    <div class="cart-items">

        @foreach($cart as $item)

            <div class="cart-item">

                <div>{{ $item['name'] }}</div>

                <button
                    class="remove-item"
                    wire:click="removeItem({{ $item['id'] }})"
                >
                    ✕
                </button>

                <div>M{{ number_format($item['price'], 2) }}</div>


                 <button
                    class="qty-btn"
                    wire:click="decreaseQty({{ $item['id'] }})"
                >
                    −
                </button>

                <div>Qty: {{ $item['quantity'] }}</div>

                <button
                    class="qty-btn"
                    wire:click="increaseQty({{ $item['id'] }})"
                >
                    +
                </button>

            </div>

        @endforeach

    </div>

    <div class="cart-footer">

        <div >

            <div class="summary-row">

                <span>Total Items</span>

                <strong>{{ collect($cart)->sum('quantity') }}</strong>

            </div>

            <div class="summary-row">

                <span>Subtotal</span>

                <strong>M{{ number_format($subtotal,2) }}</strong>

            </div>

            <div class="summary-row">

                <span>Discount</span>

                <strong>M{{ number_format($discount,2) }}</strong>

            </div>

            <div class="summary-row">

                <span>VAT</span>

                <strong>M{{ number_format($vat,2) }}</strong>

            </div>

        </div>

        <div class="btn-group">

            {{ $this->checkoutAction }}

            <button class="btn-clear" wire:click="clearCart">
                <i class="fas fa-trash"></i> Clear
            </button>
        </div>

    </div>

</div>
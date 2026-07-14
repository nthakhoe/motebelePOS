<div class="cart-panel">

    <div class="customer-card">

        <div>

            <small>Customer</small>

            <h3>👤 Walk-in Customer</h3>

        </div>

        <button class="customer-btn">

            Change

        </button>

    </div>

    <div class="cart-items">

        @foreach($cart as $item)

            <div class="cart-item">

                <div>{{ $item['name'] }}</div>

                <div>M{{ number_format($item['price'], 2) }}</div>

                <div>Qty: {{ $item['quantity'] }}</div>

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

            <div class="summary-row total">

                <span>Total</span>

                <strong>

                    M{{ number_format($total,2) }}

                </strong>

            </div>

        </div>

        <div class="btn-group">

            <button class="checkout" wire:click="checkout" @disabled(empty($cart))>
                <i class="fas fa-credit-card"></i> Checkout
            </button>

            <button class="btn-clear" wire:click="clearCart">
                <i class="fas fa-trash"></i> Clear
            </button>
        </div>

    </div>

</div>
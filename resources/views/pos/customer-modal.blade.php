@if($showCustomerModal)

<div class="customer-modal">

    <div class="customer-modal-content">

        <h2>Select Customer</h2>

        <input
            type="text"
            wire:model.live.debounce.300ms="customerSearch"
            placeholder="Search customer..."
            class="customer-search"
        >

        @foreach($customerResults as $customer)

            <button
                wire:click="selectCustomer({{ $customer->id }})"
                class="customer-result"
            >

                <strong>{{ $customer->first_name }}</strong>

                <br>

                {{ $customer->phone }}

            </button>

        @endforeach

        <button
            wire:click="closeCustomerSearch"
        >

            Close

        </button>

    </div>

</div>

@endif
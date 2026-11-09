<x-filament-panels::page>

    @push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/kitchen-order-board.css') }}"
    >
    @endpush

    <div class="kitchen-board" wire:poll.10s>

        {{-- HEADER --}}
        <div class="kitchen-header">

            <div>
                <h1>Kitchen Order Board</h1>

                <p>
                    Manage incoming restaurant orders and preparation.
                </p>
            </div>

            <div class="kitchen-live-indicator">
                <span></span>
                Live Order Monitoring
            </div>

        </div>


        {{-- SUMMARY --}}
        <div class="kitchen-summary">

            <div class="kitchen-summary-card new">
                <span>New Orders</span>
                <strong>{{ $newOrders->count() }}</strong>
            </div>

            <div class="kitchen-summary-card preparing">
                <span>Preparing</span>
                <strong>{{ $preparingOrders->count() }}</strong>
            </div>

            <div class="kitchen-summary-card ready">
                <span>Ready</span>
                <strong>{{ $readyOrders->count() }}</strong>
            </div>

            <div class="kitchen-summary-card total">
                <span>Total Active</span>
                <strong>{{ $totalOrders }}</strong>
            </div>

        </div>


        {{-- KANBAN BOARD --}}
        <div class="kitchen-columns">


            {{-- NEW ORDERS --}}
            <div class="kitchen-column">

                <div class="kitchen-column-header new">

                    <div>
                        <span class="column-indicator"></span>
                        <h3>New Orders</h3>
                    </div>

                    <span class="column-count">
                        {{ $newOrders->count() }}
                    </span>

                </div>


                <div class="kitchen-order-list">

                    @forelse($newOrders as $order)

                        <div class="kitchen-order-card"
                             wire:key="new-order-{{ $order->id }}">

                            <div class="kitchen-order-heading">

                                <strong>
                                    {{ $order->order_number }}
                                </strong>

                                <span class="kitchen-status new">
                                    NEW
                                </span>

                            </div>


                            <div class="kitchen-table-info">

                                <strong>
                                    {{ $order->table?->table_number ?? 'N/A' }}
                                </strong>

                                <span>
                                    {{ $order->table?->area?->name ?? 'N/A' }}
                                </span>

                            </div>


                            <div class="kitchen-order-meta">

                                <span>
                                    Waiter:
                                    {{ $order->waiter?->name ?? 'Unassigned' }}
                                </span>

                                <span>
                                    Guests: {{ $order->guest_count }}
                                </span>

                                <span>
                                    {{ $order->opened_at?->format('H:i') }}
                                </span>

                            </div>


                            <div class="kitchen-items">

                                @foreach($order->items as $item)

                                    <div class="kitchen-item">

                                        <strong>
                                            {{ number_format($item->quantity, 2) }}
                                            ×
                                        </strong>

                                        <div>
                                            {{ $item->product_name }}

                                            @if($item->special_instructions)

                                                <small class="kitchen-instruction">
                                                    Note:
                                                    {{ $item->special_instructions }}
                                                </small>

                                            @endif
                                        </div>

                                    </div>

                                @endforeach

                            </div>


                            @if($order->notes)

                                <div class="kitchen-order-notes">
                                    <strong>Order Notes:</strong>
                                    {{ $order->notes }}
                                </div>

                            @endif


                            <button
                                type="button"
                                wire:click="startPreparing({{ $order->id }})"
                                wire:loading.attr="disabled"
                                class="kitchen-action-button new"
                            >
                                Start Preparing
                            </button>

                        </div>

                    @empty

                        <div class="kitchen-empty">
                            No new orders.
                        </div>

                    @endforelse

                </div>

            </div>


            {{-- PREPARING --}}
            <div class="kitchen-column">

                <div class="kitchen-column-header preparing">

                    <div>
                        <span class="column-indicator"></span>
                        <h3>Preparing</h3>
                    </div>

                    <span class="column-count">
                        {{ $preparingOrders->count() }}
                    </span>

                </div>


                <div class="kitchen-order-list">

                    @forelse($preparingOrders as $order)

                        <div class="kitchen-order-card"
                             wire:key="preparing-order-{{ $order->id }}">

                            <div class="kitchen-order-heading">

                                <strong>
                                    {{ $order->order_number }}
                                </strong>

                                <span class="kitchen-status preparing">
                                    PREPARING
                                </span>

                            </div>


                            <div class="kitchen-table-info">

                                <strong>
                                    {{ $order->table?->table_number ?? 'N/A' }}
                                </strong>

                                <span>
                                    {{ $order->table?->area?->name ?? 'N/A' }}
                                </span>

                            </div>


                            <div class="kitchen-order-meta">

                                <span>
                                    Waiter:
                                    {{ $order->waiter?->name ?? 'Unassigned' }}
                                </span>

                                <span>
                                    Guests: {{ $order->guest_count }}
                                </span>

                                <span>
                                    {{ $order->opened_at?->format('H:i') }}
                                </span>

                            </div>


                            <div class="kitchen-items">

                                @foreach($order->items as $item)

                                    <div class="kitchen-item">

                                        <strong>
                                            {{ number_format($item->quantity, 2) }}
                                            ×
                                        </strong>

                                        <div>
                                            {{ $item->product_name }}

                                            @if($item->special_instructions)

                                                <small class="kitchen-instruction">
                                                    Note:
                                                    {{ $item->special_instructions }}
                                                </small>

                                            @endif
                                        </div>

                                    </div>

                                @endforeach

                            </div>


                            @if($order->notes)

                                <div class="kitchen-order-notes">
                                    <strong>Order Notes:</strong>
                                    {{ $order->notes }}
                                </div>

                            @endif


                            <button
                                type="button"
                                wire:click="markReady({{ $order->id }})"
                                wire:loading.attr="disabled"
                                class="kitchen-action-button preparing"
                            >
                                Mark as Ready
                            </button>

                        </div>

                    @empty

                        <div class="kitchen-empty">
                            No orders being prepared.
                        </div>

                    @endforelse

                </div>

            </div>


            {{-- READY --}}
            <div class="kitchen-column">

                <div class="kitchen-column-header ready">

                    <div>
                        <span class="column-indicator"></span>
                        <h3>Ready for Collection</h3>
                    </div>

                    <span class="column-count">
                        {{ $readyOrders->count() }}
                    </span>

                </div>


                <div class="kitchen-order-list">

                    @forelse($readyOrders as $order)

                        <div class="kitchen-order-card"
                             wire:key="ready-order-{{ $order->id }}">

                            <div class="kitchen-order-heading">

                                <strong>
                                    {{ $order->order_number }}
                                </strong>

                                <span class="kitchen-status ready">
                                    READY
                                </span>

                            </div>


                            <div class="kitchen-table-info">

                                <strong>
                                    {{ $order->table?->table_number ?? 'N/A' }}
                                </strong>

                                <span>
                                    {{ $order->table?->area?->name ?? 'N/A' }}
                                </span>

                            </div>


                            <div class="kitchen-order-meta">

                                <span>
                                    Waiter:
                                    {{ $order->waiter?->name ?? 'Unassigned' }}
                                </span>

                                <span>
                                    Guests: {{ $order->guest_count }}
                                </span>

                            </div>


                            <div class="kitchen-items">

                                @foreach($order->items as $item)

                                    <div class="kitchen-item">

                                        <strong>
                                            {{ number_format($item->quantity, 2) }}
                                            ×
                                        </strong>

                                        <div>
                                            {{ $item->product_name }}

                                            @if($item->special_instructions)

                                                <small class="kitchen-instruction">
                                                    Note:
                                                    {{ $item->special_instructions }}
                                                </small>

                                            @endif
                                        </div>

                                    </div>

                                @endforeach

                            </div>


                            <button
                                type="button"
                                wire:click="markCollected({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="markCollected({{ $order->id }})"
                                class="kitchen-action-button collected"
                            >
                                <span wire:loading.remove wire:target="markCollected({{ $order->id }})">
                                    ✓ Confirm Order Collected
                                </span>

                                <span wire:loading wire:target="markCollected({{ $order->id }})">
                                    Processing...
                                </span>
                            </button>

                        </div>

                    @empty

                        <div class="kitchen-empty">
                            No orders ready for collection.
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

</x-filament-panels::page>
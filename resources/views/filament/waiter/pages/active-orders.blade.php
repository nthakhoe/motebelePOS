<x-filament-panels::page>

    <link rel="stylesheet" href="{{ asset('css/active-orders.css') }}">

    <div class="active-orders-page" wire:poll.15s>

        {{-- PAGE HEADER --}}
        <div class="active-orders-header">

            <div>
                <h1>Restaurant Orders</h1>
                <p>
                    Monitor submitted orders and continue serving customers.
                </p>
            </div>

            <a
                href="{{ \App\Filament\Waiter\Pages\WaiterOrdering::getUrl() }}"
                class="new-order-button"
            >
                <x-filament::icon
                    icon="heroicon-o-plus"
                    class="w-5 h-5"
                />
                New Order
            </a>

        </div>


        {{-- SUMMARY CARDS --}}
        <div class="order-summary-grid">

            <div class="order-summary-card total">
                <span>Total Active Orders</span>
                <strong>{{ $totalOrders }}</strong>
            </div>

            <div class="order-summary-card open">
                <span>Open</span>
                <strong>{{ $openOrders }}</strong>
            </div>

            <div class="order-summary-card submitted">
                <span>Submitted</span>
                <strong>{{ $submittedOrders }}</strong>
            </div>

            <div class="order-summary-card preparing">
                <span>Preparing</span>
                <strong>{{ $preparingOrders }}</strong>
            </div>

            <div class="order-summary-card ready">
                <span>Ready</span>
                <strong>{{ $readyOrders }}</strong>
            </div>

            <div class="order-summary-card bill">
                <span>Bill Requested</span>
                <strong>{{ $billRequestedOrders }}</strong>
            </div>

        </div>


        {{-- FILTERS --}}
        <div class="orders-toolbar">

            <div class="order-status-filters">

                <button
                    wire:click="setStatus('all')"
                    class="{{ $statusFilter === 'all' ? 'active' : '' }}"
                >
                    All
                    <span>{{ $totalOrders }}</span>
                </button>

                <button
                    wire:click="setStatus('open')"
                    class="{{ $statusFilter === 'open' ? 'active' : '' }}"
                >
                    Open
                </button>

                <button
                    wire:click="setStatus('submitted')"
                    class="{{ $statusFilter === 'submitted' ? 'active' : '' }}"
                >
                    Submitted
                </button>

                <button
                    wire:click="setStatus('preparing')"
                    class="{{ $statusFilter === 'preparing' ? 'active' : '' }}"
                >
                    Preparing
                </button>

                <button
                    wire:click="setStatus('ready')"
                    class="{{ $statusFilter === 'ready' ? 'active' : '' }}"
                >
                    Ready
                </button>

                <button
                    wire:click="setStatus('served')"
                    class="{{ $statusFilter === 'served' ? 'active' : '' }}"
                >
                    Served
                </button>

                <button
                    wire:click="setStatus('bill_requested')"
                    class="{{ $statusFilter === 'bill_requested' ? 'active' : '' }}"
                >
                    Bill Requested
                </button>

            </div>

            <div class="orders-search">
                <x-filament::icon
                    icon="heroicon-o-magnifying-glass"
                    class="w-5 h-5"
                />

                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search order, table or waiter..."
                >
            </div>

        </div>


        {{-- ORDERS TABLE --}}
        <div class="orders-table-wrapper">

            <table class="orders-table">

                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Table</th>
                        <th>Dining Area</th>
                        <th>Waiter</th>
                        <th>Guests</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($orders as $order)

                        <tr wire:key="restaurant-order-{{ $order->id }}">

                            <td>
                                <strong>{{ $order->order_number }}</strong>
                            </td>

                            <td>
                                <strong>
                                    {{ $order->table?->table_number ?? 'N/A' }}
                                </strong>
                            </td>

                            <td>
                                {{ $order->table?->area?->name ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $order->waiter?->name ?? 'Unassigned' }}
                            </td>

                            <td>
                                {{ $order->guest_count }}
                            </td>

                            <td>
                                {{ $order->items->count() }}
                            </td>

                            <td>
                                <strong>
                                    {{ number_format($order->total_amount, 2) }}
                                </strong>
                            </td>

                            <td>
                                {{ $order->opened_at?->format('H:i') ?? 'N/A' }}
                            </td>

                            <td>
                                <span class="order-status {{ $order->status }}">
                                    {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>

                            <td>

                                @if($order->status !== 'bill_requested')

                                    <button
                                        type="button"
                                        wire:click="openOrder({{ $order->id }})"
                                        class="open-order-action"
                                    >
                                        Open Order
                                    </button>

                                @else

                                    <span class="locked-order">
                                        Awaiting Payment
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="10" class="empty-orders">
                                <x-filament::icon
                                    icon="heroicon-o-clipboard-document-list"
                                    class="w-10 h-10"
                                />

                                <strong>No restaurant orders found</strong>

                                <span>
                                    There are no orders matching your current filters.
                                </span>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="orders-footer">
            Showing {{ $orders->count() }} active orders.
            The list refreshes automatically every 15 seconds.
        </div>

    </div>

</x-filament-panels::page>
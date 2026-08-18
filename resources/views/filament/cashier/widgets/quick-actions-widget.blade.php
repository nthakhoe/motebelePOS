<x-filament-widgets::widget>
    <x-filament::section>

        <x-slot name="heading">
            Quick Actions
        </x-slot>

        <x-slot name="description">
            Common cashier operations
        </x-slot>

        <div class="cashier-quick-actions-grid">

            {{-- New Sale --}}
            <a
                href="{{ $this->posUrl() }}"
                class="cashier-quick-action"
            >
                <div class="cashier-quick-action-content">

                    <div class="cashier-dashboard-icon bg-primary-100">
                        <x-heroicon-o-shopping-cart
                            class="text-primary-600"
                        />
                    </div>

                    <div>
                        <div class="cashier-quick-action-title">
                            New Sale
                        </div>

                        <div class="cashier-quick-action-description">
                            Start transaction
                        </div>
                    </div>

                </div>
            </a>


            {{-- Sales History --}}
            <a
                href="{{ $this->salesUrl() }}"
                class="cashier-quick-action"
            >
                <div class="cashier-quick-action-content">

                    <div class="cashier-dashboard-icon bg-gray-100">
                        <x-heroicon-o-clipboard-document-list
                            class="text-gray-600"
                        />
                    </div>

                    <div>
                        <div class="cashier-quick-action-title">
                            Sales History
                        </div>

                        <div class="cashier-quick-action-description">
                            View transactions
                        </div>
                    </div>

                </div>
            </a>


            {{-- Refund --}}
            <a
                href="{{ $this->salesUrl() }}"
                class="cashier-quick-action"
            >
                <div class="cashier-quick-action-content">

                    <div class="cashier-dashboard-icon bg-warning-100">
                        <x-heroicon-o-arrow-uturn-left
                            class="text-warning-600"
                        />
                    </div>

                    <div>
                        <div class="cashier-quick-action-title">
                            Refund
                        </div>

                        <div class="cashier-quick-action-description">
                            Find a sale to refund
                        </div>
                    </div>

                </div>
            </a>


            {{-- Shift --}}
            <a
                href="{{ $this->shiftUrl() }}"
                class="cashier-quick-action"
            >
                <div class="cashier-dashboard-icon bg-success-100">
                    <x-heroicon-o-clock
                        class="text-success-600"
                    />
                </div>

                <div>
                    <div class="cashier-quick-action-title">
                        Shift
                    </div>

                    <div class="cashier-quick-action-description">
                        Manage current shift
                    </div>
                </div>
            </a>

        </div>

    </x-filament::section>
</x-filament-widgets::widget>
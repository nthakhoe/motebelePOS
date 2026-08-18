<x-filament-widgets::widget>
    <x-filament::section>

        <div class="flex items-center justify-between">

            <div>
                <p class="text-sm font-medium text-gray-500">
                    Refund Activity
                </p>

                <p class="mt-1 text-3xl font-bold">
                    {{ $this->getRefundCount() }}
                </p>

                <p class="mt-1 text-sm text-gray-500">
                    Refunds today
                </p>
            </div>

            <div class="cashier-dashboard-icon bg-warning-100">
                <x-heroicon-o-arrow-uturn-left class="text-warning-600" />
            </div>

        </div>

        <div class="mt-4 flex items-center justify-between border-t pt-4">

            <span class="text-sm text-gray-500">
                Current shift
            </span>

            <span class="font-semibold">
                {{ $this->getSessionRefundCount() }}
            </span>

        </div>

        <div class="mt-4">

            <x-filament::button
                tag="a"
                :href="\App\Filament\Cashier\Resources\Sales\SaleResource::getUrl('index')"
                color="gray"
                icon="heroicon-o-arrow-uturn-left"
                outlined
                class="w-full"
            >
                View Sales & Refunds
            </x-filament::button>

        </div>

    </x-filament::section>
</x-filament-widgets::widget>
<x-filament-widgets::widget>
    <x-filament::section>

        @php
            $session = $this->getSession();
        @endphp

        @if($session)

            <div class="cashier-shift-card">

                <div class="cashier-shift-header">

                    <div class="cashier-dashboard-icon bg-success-100">
                        <x-heroicon-o-lock-open class="text-success-600" />
                    </div>

                    <div>
                        <div class="cashier-shift-title">
                            Shift Open
                        </div>

                        <div class="cashier-shift-session">
                            {{ $session->session_number }}
                        </div>
                    </div>

                    <span class="cashier-shift-badge">
                        ACTIVE
                    </span>

                </div>


                <div class="cashier-shift-details">

                    <div>
                        <span>Terminal</span>
                        <strong>
                            {{ $session->terminal?->name ?? 'Terminal' }}
                        </strong>
                    </div>

                    <div>
                        <span>Opened</span>
                        <strong>
                            {{ $session->opened_at?->format('H:i') }}
                        </strong>
                    </div>

                </div>


                <div class="cashier-shift-action">

                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Cashier\Pages\ShiftManagement::getUrl()"
                        color="gray"
                        size="sm"
                        icon="heroicon-o-cog-6-tooth"
                    >
                        Manage Shift
                    </x-filament::button>

                </div>

            </div>

        @else

            <div class="cashier-shift-card">

                <div class="cashier-shift-header">

                    <div class="cashier-dashboard-icon bg-warning-100">
                        <x-heroicon-o-lock-closed class="text-warning-600" />
                    </div>

                    <div>
                        <div class="cashier-shift-title">
                            No Active Shift
                        </div>

                        <div class="cashier-shift-session">
                            Open a shift before processing sales.
                        </div>
                    </div>

                </div>

                <div class="cashier-shift-action">

                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Cashier\Pages\ShiftManagement::getUrl()"
                        color="success"
                        size="sm"
                        icon="heroicon-o-lock-open"
                    >
                        Open Shift
                    </x-filament::button>

                </div>

            </div>

        @endif

    </x-filament::section>
</x-filament-widgets::widget>
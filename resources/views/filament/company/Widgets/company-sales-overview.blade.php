<x-filament-widgets::widget>
    <div class="company-sales-overview">

        {{-- =====================================================
             HEADER
             ===================================================== --}}
        <div class="company-dashboard-widget-header">

            <div>
                <h2 class="company-dashboard-widget-title">
                    Business Performance
                </h2>

                <p class="company-dashboard-widget-description">
                    Today's sales performance across the company
                </p>
            </div>

            <div class="company-dashboard-widget-period">
                Today
            </div>

        </div>


        {{-- =====================================================
             KPI CARDS
             ===================================================== --}}
        <div class="company-sales-kpi-grid">

            {{-- Today's Sales --}}
            <div class="company-sales-kpi-card">

                <div class="company-sales-kpi-top">

                    <div class="company-sales-kpi-icon sales">
                        <x-heroicon-o-banknotes />
                    </div>

                    <span class="company-sales-kpi-label">
                        Today's Sales
                    </span>

                </div>

                <div class="company-sales-kpi-value">
                    M{{ number_format($todaySales ?? 0, 2) }}
                </div>

                <div class="company-sales-kpi-description">
                    Completed sales today
                </div>

            </div>


            {{-- Net Sales --}}
            <div class="company-sales-kpi-card">

                <div class="company-sales-kpi-top">

                    <div class="company-sales-kpi-icon net-sales">
                        <x-heroicon-o-chart-bar />
                    </div>

                    <span class="company-sales-kpi-label">
                        Net Sales
                    </span>

                </div>

                <div class="company-sales-kpi-value">
                    M{{ number_format($todayNetSales ?? 0, 2) }}
                </div>

                <div class="company-sales-kpi-description">
                    Sales less refunds
                </div>

            </div>


            {{-- Refunds --}}
            <div class="company-sales-kpi-card">

                <div class="company-sales-kpi-top">

                    <div class="company-sales-kpi-icon refunds">
                        <x-heroicon-o-arrow-uturn-left />
                    </div>

                    <span class="company-sales-kpi-label">
                        Refunds
                    </span>

                </div>

                <div class="company-sales-kpi-value">
                    M{{ number_format($todayRefunds ?? 0, 2) }}
                </div>

                <div class="company-sales-kpi-description">
                    Refunded sales today
                </div>

            </div>


            {{-- Transactions --}}
            <div class="company-sales-kpi-card">

                <div class="company-sales-kpi-top">

                    <div class="company-sales-kpi-icon transactions">
                        <x-heroicon-o-receipt-percent />
                    </div>

                    <span class="company-sales-kpi-label">
                        Transactions
                    </span>

                </div>

                <div class="company-sales-kpi-value">
                    {{ number_format($todayTransactions ?? 0) }}
                </div>

                <div class="company-sales-kpi-description">
                    Completed transactions today
                </div>

            </div>

        </div>

    </div>
</x-filament-widgets::widget>
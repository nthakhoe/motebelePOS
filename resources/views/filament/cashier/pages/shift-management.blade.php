<x-filament-panels::page>

    @if($this->session)

        <div class="cashier-shift-management">

            {{-- =====================================================
                 CURRENT SHIFT
                 ===================================================== --}}
            <div class="cashier-shift-section">

                <div class="cashier-shift-section-header">

                    <div class="cashier-shift-section-icon bg-success-100">
                        <x-heroicon-o-clock class="text-success-600" />
                    </div>

                    <div>
                        <div class="cashier-shift-section-title">
                            Current Shift
                        </div>

                        <div class="cashier-shift-section-description">
                            {{ $this->session->session_number }}
                        </div>
                    </div>

                    <div style="margin-left: auto;">
                        <span class="cashier-shift-status">
                            <span class="cashier-shift-status-dot"></span>
                            OPEN
                        </span>
                    </div>

                </div>


                <div class="cashier-shift-section-body">

                    <div class="cashier-current-shift">

                        {{-- Opened --}}
                        <div>
                            <span class="cashier-shift-info-label">
                                Opened
                            </span>

                            <span class="cashier-shift-info-value">
                                {{ $this->session->opened_at?->format('d M Y H:i') }}
                            </span>
                        </div>


                        {{-- Opening Cash --}}
                        <div>
                            <span class="cashier-shift-info-label">
                                Opening Cash
                            </span>

                            <span class="cashier-shift-info-value">
                                M{{ number_format(
                                    (float) $this->session->opening_float,
                                    2
                                ) }}
                            </span>
                        </div>


                        {{-- Session --}}
                        <div>
                            <span class="cashier-shift-info-label">
                                Session
                            </span>

                            <span class="cashier-shift-info-value">
                                {{ $this->session->session_number }}
                            </span>
                        </div>


                        {{-- Cashier --}}
                        <div>
                            <span class="cashier-shift-info-label">
                                Cashier
                            </span>

                            <span class="cashier-shift-info-value">
                                {{ auth()->user()->name }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 SALES SUMMARY
                 ===================================================== --}}
            <div class="cashier-shift-section">

                <div class="cashier-shift-section-header">

                    <div class="cashier-shift-section-icon bg-primary-100">
                        <x-heroicon-o-chart-bar class="text-primary-600" />
                    </div>

                    <div>
                        <div class="cashier-shift-section-title">
                            Sales Summary
                        </div>

                        <div class="cashier-shift-section-description">
                            Sales recorded during this shift
                        </div>
                    </div>

                </div>


                <div class="cashier-shift-section-body">

                    <div class="cashier-shift-summary-grid">

                        {{-- Cash Sales --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Cash Sales
                            </div>

                            <div class="cashier-shift-summary-value">
                                M{{ number_format(
                                    (float) $this->session->cash_sales,
                                    2
                                ) }}
                            </div>

                        </div>


                        {{-- Gross Sales --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Gross Sales
                            </div>

                            <div class="cashier-shift-summary-value">
                                M{{ number_format(
                                    (float) $this->session->gross_sales,
                                    2
                                ) }}
                            </div>

                        </div>


                        {{-- Refunds --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Refunds
                            </div>

                            <div class="cashier-shift-summary-value">
                                M{{ number_format(
                                    (float) $this->session->refund_total,
                                    2
                                ) }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 SHIFT STATISTICS
                 ===================================================== --}}
            <div class="cashier-shift-section">

                <div class="cashier-shift-section-header">

                    <div class="cashier-shift-section-icon bg-gray-100">
                        <x-heroicon-o-chart-pie class="text-gray-600" />
                    </div>

                    <div>
                        <div class="cashier-shift-section-title">
                            Shift Statistics
                        </div>

                        <div class="cashier-shift-section-description">
                            Transaction activity for the current shift
                        </div>
                    </div>

                </div>


                <div class="cashier-shift-section-body">

                    <div class="cashier-shift-summary-grid">

                        {{-- Transactions --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Transactions
                            </div>

                            <div class="cashier-shift-summary-value">
                                {{ $this->session->transaction_count }}
                            </div>

                        </div>


                        {{-- Receipts --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Receipts
                            </div>

                            <div class="cashier-shift-summary-value">
                                {{ $this->session->receipt_count }}
                            </div>

                        </div>


                        {{-- Refunds --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Refunds
                            </div>

                            <div class="cashier-shift-summary-value">
                                {{ $this->session->refund_count }}
                            </div>

                        </div>


                        {{-- Net Sales --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Net Sales
                            </div>

                            <div class="cashier-shift-summary-value">
                                M{{ number_format(
                                    (float) $this->session->net_sales,
                                    2
                                ) }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 CASH POSITION
                 ===================================================== --}}
            <div class="cashier-shift-section">

                <div class="cashier-shift-section-header">

                    <div class="cashier-shift-section-icon bg-warning-100">
                        <x-heroicon-o-banknotes class="text-warning-600" />
                    </div>

                    <div>
                        <div class="cashier-shift-section-title">
                            Cash Position
                        </div>

                        <div class="cashier-shift-section-description">
                            Current expected cash position
                        </div>
                    </div>

                </div>


                <div class="cashier-shift-section-body">

                    <div class="cashier-shift-summary-grid">

                        {{-- Opening Float --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Opening Float
                            </div>

                            <div class="cashier-shift-summary-value">
                                M{{ number_format(
                                    (float) $this->session->opening_float,
                                    2
                                ) }}
                            </div>

                        </div>


                        {{-- Expected Cash --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Expected Cash
                            </div>

                            <div class="cashier-shift-summary-value">
                                M{{ number_format(
                                    (float) (
                                        $this->session->opening_float
                                        + $this->session->cash_sales
                                        - $this->session->refund_total
                                    ),
                                    2
                                ) }}
                            </div>

                        </div>

                        {{-- Expected Non Cash --}}
                        <div class="cashier-shift-summary-card">

                            <div class="cashier-shift-summary-label">
                                Expected Non Cash
                            </div>

                            <div class="cashier-shift-summary-value">
                                M{{ number_format(
                                    (float) ($this->session->bank_sales
                                    ),
                                    2
                                ) }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 SHIFT ACTIONS
                 ===================================================== --}}
            <div class="cashier-shift-section">

                <div class="cashier-shift-section-header">

                    <div class="cashier-shift-section-icon bg-danger-100">
                        <x-heroicon-o-cog-6-tooth class="text-danger-600" />
                    </div>

                    <div>
                        <div class="cashier-shift-section-title">
                            Shift Management
                        </div>

                        <div class="cashier-shift-section-description">
                            Manage and close the current register session
                        </div>
                    </div>

                </div>


                <div class="cashier-shift-section-body">

                    <div class="cashier-shift-actions">

                        {{-- Existing Livewire / Filament actions should remain here --}}

                        {{ $this->openShiftAction ?? '' }}

                        {{ $this->closeShiftAction ?? '' }}

                    </div>

                </div>

            </div>

        </div>


    @else

        {{-- =====================================================
             NO ACTIVE SHIFT
             ===================================================== --}}
        <div class="cashier-shift-section">

            <div class="cashier-shift-section-body">

                <div class="py-10 text-center">

                    <div class="cashier-dashboard-icon bg-warning-100 mx-auto">
                        <x-heroicon-o-lock-closed class="text-warning-600" />
                    </div>

                    <div class="mt-4 text-xl font-bold">
                        No Active Shift
                    </div>

                    <div class="mt-2 text-sm text-gray-500">
                        Open a shift before processing cashier transactions.
                    </div>

                </div>

            </div>

        </div>

    @endif

</x-filament-panels::page>
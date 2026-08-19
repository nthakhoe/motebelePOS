<x-filament-widgets::widget>

    <div class="company-payment-breakdown">

        {{-- =====================================================
             HEADER
             ===================================================== --}}
        <div class="company-payment-header">

            <div>
                <h2 class="company-payment-title">
                    Payment Methods
                </h2>

                <p class="company-payment-description">
                    Today's completed payments
                </p>
            </div>

            <div class="company-payment-total">
                M{{ number_format($totalPayments, 2) }}
            </div>

        </div>


        {{-- =====================================================
             CONTENT
             ===================================================== --}}
        @if(count($paymentMethods))

            <div class="company-payment-content">

                {{-- Doughnut --}}
                <div class="company-payment-chart-wrapper">

                    @php

                        $radius = 70;

                        $circumference =
                            2 * pi() * $radius;

                        $offset = 0;

                        $paymentColors = [
                            '#16A34A',
                            '#2563EB',
                            '#F59E0B',
                            '#8B5CF6',
                            '#64748B',
                            '#EF4444',
                        ];

                    @endphp

                    <svg
                        class="company-payment-chart"
                        viewBox="0 0 180 180"
                        role="img"
                        aria-label="Payment method breakdown"
                    >

                        {{-- Background ring --}}
                        <circle
                            cx="90"
                            cy="90"
                            r="{{ $radius }}"
                            fill="none"
                            stroke="#E2E8F0"
                            stroke-width="24"
                        />

                        @foreach($paymentMethods as $index => $payment)

                            @php

                                $percentage =
                                    $payment['percentage'];

                                $dash =
                                    (
                                        $percentage / 100
                                    ) * $circumference;

                                $color =
                                    $paymentColors[
                                        $index %
                                        count($paymentColors)
                                    ];

                            @endphp

                            <circle
                                cx="90"
                                cy="90"
                                r="{{ $radius }}"
                                fill="none"
                                stroke="{{ $color }}"
                                stroke-width="24"
                                stroke-dasharray="{{ $dash }} {{ $circumference - $dash }}"
                                stroke-dashoffset="{{ -$offset }}"
                                transform="rotate(-90 90 90)"
                                class="company-payment-segment"
                            />

                            @php
                                $offset += $dash;
                            @endphp

                        @endforeach


                        {{-- Centre --}}
                        <text
                            x="90"
                            y="84"
                            text-anchor="middle"
                            class="company-payment-chart-label"
                        >
                            {{ count($paymentMethods) }}
                        </text>

                        <text
                            x="90"
                            y="103"
                            text-anchor="middle"
                            class="company-payment-chart-subtitle"
                        >
                            Methods
                        </text>

                    </svg>

                </div>


                {{-- Payment List --}}
                <div class="company-payment-list">

                    @foreach($paymentMethods as $index => $payment)

                        @php

                            $color =
                                $paymentColors[
                                    $index %
                                    count($paymentColors)
                                ];

                        @endphp

                        <div class="company-payment-item">

                            <div class="company-payment-item-left">

                                <span
                                    class="company-payment-indicator"
                                    style="background: {{ $color }}"
                                ></span>

                                <div>

                                    <div class="company-payment-method-name">
                                        {{ $payment['name'] }}
                                    </div>

                                    <div class="company-payment-count">
                                        {{ $payment['count'] }}
                                        {{ $payment['count'] === 1 ? 'payment' : 'payments' }}
                                    </div>

                                </div>

                            </div>


                            <div class="company-payment-item-right">

                                <div class="company-payment-amount">
                                    M{{ number_format($payment['amount'], 2) }}
                                </div>

                                <div class="company-payment-percentage">
                                    {{ number_format($payment['percentage'], 1) }}%
                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        @else

            <div class="company-payment-empty">

                <div class="company-payment-empty-icon">
                    <x-heroicon-o-credit-card />
                </div>

                <div class="company-payment-empty-title">
                    No payments recorded
                </div>

                <div class="company-payment-empty-description">
                    There are no completed payments for today.
                </div>

            </div>

        @endif

    </div>

</x-filament-widgets::widget>
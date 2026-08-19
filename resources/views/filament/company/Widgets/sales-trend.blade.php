<x-filament-widgets::widget>

    <div class="company-sales-trend">

        {{-- =====================================================
             HEADER
             ===================================================== --}}
        <div class="company-sales-trend-header">

            <div>
                <h2 class="company-sales-trend-title">
                    Sales Trend
                </h2>

                <p class="company-sales-trend-description">
                    Completed sales for the last 7 days
                </p>
            </div>

            <div class="company-sales-trend-summary">

                <span class="company-sales-trend-summary-label">
                    7 Day Sales
                </span>

                <span class="company-sales-trend-summary-value">
                    M{{ number_format($totalSales, 2) }}
                </span>

            </div>

        </div>


        {{-- =====================================================
             CHART
             ===================================================== --}}
        <div class="company-sales-chart">

            @php

                $maxValue = collect($salesData)->max('amount');

                $maxValue = max($maxValue, 1);

                $chartHeight = 220;

                $chartWidth = 900;

                $leftPadding = 60;

                $rightPadding = 20;

                $topPadding = 20;

                $bottomPadding = 45;

                $plotWidth =
                    $chartWidth
                    - $leftPadding
                    - $rightPadding;

                $plotHeight =
                    $chartHeight
                    - $topPadding
                    - $bottomPadding;

                $points = [];

                foreach ($salesData as $index => $item) {

                    $x = $leftPadding +
                        (
                            $index /
                            max(count($salesData) - 1, 1)
                        ) *
                        $plotWidth;

                    $y = $topPadding +
                        $plotHeight -
                        (
                            $item['amount'] /
                            $maxValue
                        ) *
                        $plotHeight;

                    $points[] = [
                        'x' => $x,
                        'y' => $y,
                        'amount' => $item['amount'],
                        'label' => $item['label'],
                        'short_date' => $item['short_date'],
                    ];
                }

                $linePoints = collect($points)
                    ->map(fn ($point) =>
                        $point['x'] . ',' . $point['y']
                    )
                    ->implode(' ');

                $areaPoints =
                    $leftPadding . ',' . ($topPadding + $plotHeight)
                    . ' '
                    . $linePoints
                    . ' '
                    . (
                        $leftPadding + $plotWidth
                    ) . ',' . (
                        $topPadding + $plotHeight
                    );

            @endphp


            <div class="company-sales-chart-container">

                <svg
                    class="company-sales-chart-svg"
                    viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}"
                    preserveAspectRatio="none"
                    role="img"
                    aria-label="Sales trend for the last seven days"
                >

                    {{-- Grid lines --}}
                    @for($i = 0; $i <= 4; $i++)

                        @php
                            $gridY =
                                $topPadding +
                                (
                                    $plotHeight / 4
                                ) * $i;

                            $gridValue =
                                $maxValue -
                                (
                                    $maxValue / 4
                                ) * $i;
                        @endphp

                        <line
                            x1="{{ $leftPadding }}"
                            y1="{{ $gridY }}"
                            x2="{{ $chartWidth - $rightPadding }}"
                            y2="{{ $gridY }}"
                            class="company-sales-chart-grid-line"
                        />

                        <text
                            x="{{ $leftPadding - 10 }}"
                            y="{{ $gridY + 4 }}"
                            text-anchor="end"
                            class="company-sales-chart-y-label"
                        >
                            M{{ number_format($gridValue, 0) }}
                        </text>

                    @endfor


                    {{-- Area --}}
                    <polygon
                        points="{{ $areaPoints }}"
                        class="company-sales-chart-area"
                    />


                    {{-- Sales line --}}
                    <polyline
                        points="{{ $linePoints }}"
                        class="company-sales-chart-line"
                    />


                    {{-- Data points --}}
                    @foreach($points as $point)

                        <circle
                            cx="{{ $point['x'] }}"
                            cy="{{ $point['y'] }}"
                            r="5"
                            class="company-sales-chart-point"
                        />

                        <text
                            x="{{ $point['x'] }}"
                            y="{{ $chartHeight - 15 }}"
                            text-anchor="middle"
                            class="company-sales-chart-x-label"
                        >
                            {{ $point['label'] }}
                        </text>

                    @endforeach

                </svg>

            </div>


            {{-- =================================================
                 DAILY VALUES
                 ================================================= --}}
            <div class="company-sales-daily-values">

                @foreach($salesData as $day)

                    <div class="company-sales-day">

                        <span class="company-sales-day-date">
                            {{ $day['short_date'] }}
                        </span>

                        <span class="company-sales-day-value">
                            M{{ number_format($day['amount'], 2) }}
                        </span>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

</x-filament-widgets::widget>
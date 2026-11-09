<x-filament-panels::page>

    <div class="restaurant-floor">

        {{-- Branch Selector --}}
        @if($branches->count() > 1)
            <div class="floor-toolbar">
                <div>
                    <h2>Dining Floor Overview</h2>
                    <p>Manage your restaurant tables.</p>
                </div>

                <div class="floor-branch-selector">
                    <label>Branch</label>

                    <select wire:model.live="selectedBranchId">
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        {{-- Statistics --}}
        <div class="floor-statistics">

            <div class="floor-stat-card">
                <div class="stat-icon total">▦</div>
                <div>
                    <span>Total Tables</span>
                    <strong>{{ $statistics['total'] }}</strong>
                </div>
            </div>

            <div class="floor-stat-card">
                <div class="stat-icon available">✓</div>
                <div>
                    <span>Available</span>
                    <strong>{{ $statistics['available'] }}</strong>
                </div>
            </div>

            <div class="floor-stat-card">
                <div class="stat-icon occupied">●</div>
                <div>
                    <span>Occupied</span>
                    <strong>{{ $statistics['occupied'] }}</strong>
                </div>
            </div>

            <div class="floor-stat-card">
                <div class="stat-icon reserved">◷</div>
                <div>
                    <span>Reserved</span>
                    <strong>{{ $statistics['reserved'] }}</strong>
                </div>
            </div>

            <div class="floor-stat-card">
                <div class="stat-icon cleaning">✦</div>
                <div>
                    <span>Cleaning</span>
                    <strong>{{ $statistics['cleaning'] }}</strong>
                </div>
            </div>

        </div>

        {{-- Legend --}}
        <div class="floor-legend">
            <span><i class="legend-dot available"></i> Available</span>
            <span><i class="legend-dot occupied"></i> Occupied</span>
            <span><i class="legend-dot reserved"></i> Reserved</span>
            <span><i class="legend-dot cleaning"></i> Cleaning</span>
        </div>

        {{-- Dining Areas --}}
        @forelse($areas as $area)

            <section class="dining-area-section">

                <div class="dining-area-heading">

                    <div>
                        <h2>{{ $area->name }}</h2>

                        @if($area->description)
                            <p>{{ $area->description }}</p>
                        @endif
                    </div>

                    <div class="area-table-count">
                        {{ $area->tables->count() }}
                        {{ Str::plural('Table', $area->tables->count()) }}
                    </div>

                </div>

                <div class="restaurant-table-grid">

                    @forelse($area->tables as $table)

                        <div class="restaurant-table-card {{ $table->status }}">

                            <div class="table-card-top">

                                <div class="table-symbol">
                                    <svg viewBox="0 0 64 64" fill="none">
                                        <rect x="13" y="13" width="38" height="38"
                                            rx="8" stroke="currentColor"
                                            stroke-width="3"/>
                                        <path d="M32 13V51M13 32H51"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            opacity=".25"/>
                                    </svg>
                                </div>

                                <span class="table-status">
                                    {{ ucfirst($table->status) }}
                                </span>

                            </div>

                            <h3>{{ $table->table_number }}</h3>

                            <div class="table-capacity">
                                <span>♙</span>
                                {{ $table->capacity }}
                                {{ Str::plural('Seat', $table->capacity) }}
                            </div>

                            @php
                                $activeOrder = $table->activeOrder->first();
                            @endphp

                            @if($activeOrder)
                                <div class="table-order-info">
                                    Order #{{ $activeOrder->order_number }}
                                    @if($activeOrder->waiter)
                                        <br>
                                        Waiter: {{ $activeOrder->waiter->name }}
                                    @endif
                                </div>
                            @endif

                        </div>

                    @empty

                        <div class="empty-area">
                            No tables have been added to this dining area.
                        </div>

                    @endforelse

                </div>

            </section>

        @empty

            <div class="floor-empty-state">
                <div class="empty-icon">▦</div>

                <h3>No Dining Areas Available</h3>

                <p>
                    Create dining areas and add restaurant tables
                    to start managing your restaurant floor.
                </p>
            </div>

        @endforelse

    </div>

    <style>
        .restaurant-floor {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .floor-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .floor-toolbar h2,
        .dining-area-heading h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-950);
        }

        .floor-toolbar p,
        .dining-area-heading p {
            color: var(--gray-500);
            font-size: 13px;
            margin-top: 4px;
        }

        .floor-branch-selector {
            min-width: 220px;
        }

        .floor-branch-selector label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .floor-branch-selector select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray-300);
            border-radius: 9px;
            background: var(--gray-50);
            color: var(--gray-950);
        }

        .floor-statistics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
        }

        .floor-stat-card {
            display: flex;
            align-items: center;
            gap: 13px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 13px;
            padding: 18px;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            font-size: 21px;
            font-weight: 700;
        }

        .stat-icon.total {
            background: #e0e7ff;
            color: #4338ca;
        }

        .stat-icon.available {
            background: #dcfce7;
            color: #15803d;
        }

        .stat-icon.occupied {
            background: #fee2e2;
            color: #dc2626;
        }

        .stat-icon.reserved {
            background: #fef3c7;
            color: #b45309;
        }

        .stat-icon.cleaning {
            background: #e5e7eb;
            color: #4b5563;
        }

        .floor-stat-card span {
            display: block;
            color: var(--gray-500);
            font-size: 12px;
        }

        .floor-stat-card strong {
            display: block;
            font-size: 23px;
            font-weight: 750;
            margin-top: 3px;
            color: var(--gray-950);
        }

        .floor-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            padding: 12px 16px;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            background: var(--gray-50);
        }

        .floor-legend span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--gray-600);
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .legend-dot.available { background: #16a34a; }
        .legend-dot.occupied { background: #dc2626; }
        .legend-dot.reserved { background: #d97706; }
        .legend-dot.cleaning { background: #6b7280; }

        .dining-area-section {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 15px;
            padding: 22px;
        }

        .dining-area-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .area-table-count {
            padding: 6px 12px;
            border-radius: 20px;
            background: var(--gray-200);
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .restaurant-table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 16px;
        }

        .restaurant-table-card {
            position: relative;
            border: 1px solid var(--gray-200);
            border-radius: 13px;
            padding: 17px;
            background: white;
            min-height: 180px;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .restaurant-table-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 18px rgba(0,0,0,.07);
        }

        .restaurant-table-card.available {
            border-top: 4px solid #16a34a;
        }

        .restaurant-table-card.occupied {
            border-top: 4px solid #dc2626;
        }

        .restaurant-table-card.reserved {
            border-top: 4px solid #d97706;
        }

        .restaurant-table-card.cleaning {
            border-top: 4px solid #6b7280;
        }

        .table-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-symbol {
            width: 42px;
            height: 42px;
            color: var(--gray-500);
        }

        .table-symbol svg {
            width: 100%;
            height: 100%;
        }

        .table-status {
            font-size: 10px;
            font-weight: 700;
            padding: 5px 8px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .available .table-status {
            background: #dcfce7;
            color: #15803d;
        }

        .occupied .table-status {
            background: #fee2e2;
            color: #b91c1c;
        }

        .reserved .table-status {
            background: #fef3c7;
            color: #92400e;
        }

        .cleaning .table-status {
            background: #e5e7eb;
            color: #374151;
        }

        .restaurant-table-card h3 {
            font-size: 17px;
            font-weight: 750;
            margin-top: 12px;
            color: var(--gray-950);
        }

        .table-capacity {
            margin-top: 5px;
            font-size: 12px;
            color: var(--gray-500);
        }

        .table-order-info {
            margin-top: 12px;
            padding-top: 9px;
            border-top: 1px solid var(--gray-200);
            font-size: 11px;
            color: var(--gray-600);
            line-height: 1.6;
        }

        .empty-area {
            grid-column: 1 / -1;
            padding: 25px;
            text-align: center;
            color: var(--gray-500);
            font-size: 13px;
        }

        .floor-empty-state {
            text-align: center;
            padding: 65px 20px;
            background: var(--gray-50);
            border: 1px dashed var(--gray-300);
            border-radius: 15px;
        }

        .floor-empty-state h3 {
            font-size: 19px;
            font-weight: 700;
            margin: 15px 0 7px;
        }

        .floor-empty-state p {
            color: var(--gray-500);
            font-size: 13px;
        }

        .empty-icon {
            font-size: 45px;
            color: var(--gray-400);
        }

        @media(max-width: 900px) {
            .floor-statistics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media(max-width: 600px) {
            .floor-statistics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .floor-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .floor-branch-selector {
                width: 100%;
            }

            .dining-area-section {
                padding: 15px;
            }

            .restaurant-table-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

</x-filament-panels::page>
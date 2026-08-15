<x-filament-panels::page>

    @if($this->session)

        <div class="grid gap-6">

            {{-- Current Shift --}}
            <x-filament::section>

                <x-slot name="heading">
                    Current Shift
                </x-slot>

                <x-slot name="description">
                    {{ $this->session->session_number }}
                </x-slot>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    <div>
                        <div class="text-sm text-gray-500">
                            Opened
                        </div>

                        <div class="font-semibold">
                            {{ $this->session->opened_at?->format('d M Y H:i') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">
                            Opening Cash
                        </div>

                        <div class="font-semibold">
                            M{{ number_format(
                                (float) $this->session->opening_float,
                                2
                            ) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">
                            Status
                        </div>

                        <div class="font-semibold text-success-600">
                            OPEN
                        </div>
                    </div>

                </div>

            </x-filament::section>


            {{-- Sales Summary --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <x-filament::section>

                    <div class="text-sm text-gray-500">
                        Cash Sales
                    </div>

                    <div class="mt-1 text-2xl font-bold">
                        M{{ number_format(
                            (float) $this->session->cash_sales,
                            2
                        ) }}
                    </div>

                </x-filament::section>


                <x-filament::section>

                    <div class="text-sm text-gray-500">
                        Gross Sales
                    </div>

                    <div class="mt-1 text-2xl font-bold">
                        M{{ number_format(
                            (float) $this->session->gross_sales,
                            2
                        ) }}
                    </div>

                </x-filament::section>


                <x-filament::section>

                    <div class="text-sm text-gray-500">
                        Refunds
                    </div>

                    <div class="mt-1 text-2xl font-bold">
                        M{{ number_format(
                            (float) $this->session->refund_total,
                            2
                        ) }}
                    </div>

                </x-filament::section>

            </div>


            {{-- Transaction Statistics --}}
            <x-filament::section>

                <x-slot name="heading">
                    Shift Statistics
                </x-slot>

                <div class="grid grid-cols-2 gap-6 md:grid-cols-4">

                    <div>
                        <div class="text-sm text-gray-500">
                            Transactions
                        </div>

                        <div class="text-xl font-bold">
                            {{ $this->session->transaction_count }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">
                            Receipts
                        </div>

                        <div class="text-xl font-bold">
                            {{ $this->session->receipt_count }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">
                            Refunds
                        </div>

                        <div class="text-xl font-bold">
                            {{ $this->session->refund_count }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">
                            Net Sales
                        </div>

                        <div class="text-xl font-bold">
                            M{{ number_format(
                                (float) $this->session->net_sales,
                                2
                            ) }}
                        </div>
                    </div>

                </div>

            </x-filament::section>


            {{-- Expected Cash --}}
            <x-filament::section>

                <x-slot name="heading">
                    Cash Position
                </x-slot>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                    <div>

                        <div class="text-sm text-gray-500">
                            Opening Float
                        </div>

                        <div class="text-xl font-bold">
                            M{{ number_format(
                                (float) $this->session->opening_float,
                                2
                            ) }}
                        </div>

                    </div>

                    <div>

                        <div class="text-sm text-gray-500">
                            Expected Cash
                        </div>

                        <div class="text-xl font-bold">
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

                </div>

            </x-filament::section>

        </div>

    @else

        <x-filament::section>

            <div class="py-10 text-center">

                <div class="text-2xl font-bold">
                    No Active Shift
                </div>

                <div class="mt-2 text-gray-500">
                    Open a shift before processing cashier transactions.
                </div>

            </div>

        </x-filament::section>

    @endif

</x-filament-panels::page>
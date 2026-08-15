<?php

namespace App\Filament\Cashier\Resources\Sales\Pages;

use App\Filament\Cashier\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use App\Services\RefundService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    /*
    |--------------------------------------------------------------------------
    | Header Actions
    |--------------------------------------------------------------------------
    */

    protected function getHeaderActions(): array
    {
        return [

            Action::make('reprint')
                ->label('Reprint Receipt')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Sale $record): string =>
                    route('cashier.sales.receipt', [
                        'sale' => $record,
                    ])
                )
                ->openUrlInNewTab(),

            Action::make('refund')
    ->label('Refund')
    ->icon('heroicon-o-arrow-uturn-left')
    ->color('warning')

    ->visible(function (Sale $record): bool {
        return in_array($record->status, [
            'completed',
            'partially_refunded',
        ]);
    })

    ->modalHeading(fn (Sale $record): string =>
        'Refund Sale ' . $record->sale_number
    )

    ->modalDescription(function (Sale $record): string {

        $refunded = (float) $record->refunds()
            ->where('status', 'completed')
            ->sum('total_amount');

        $remaining = max(
            0,
            (float) $record->total - $refunded
        );

        return 'Select the items and quantities to refund. ' .
            'Maximum remaining refundable amount: M' .
            number_format($remaining, 2);
    })

    ->modalSubmitActionLabel('Process Refund')

    ->requiresConfirmation()

    ->schema(function (Sale $record): array {

        $record->loadMissing([
            'items.product',
            'items.unit',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Calculate quantities already refunded
        |--------------------------------------------------------------------------
        */

        $refundedQuantities = [];

        foreach ($record->items as $saleItem) {

            $refundedQuantities[$saleItem->id] =
                (float) \DB::table('refund_items')
                    ->join(
                        'refunds',
                        'refunds.id',
                        '=',
                        'refund_items.refund_id'
                    )
                    ->where(
                        'refund_items.sale_item_id',
                        $saleItem->id
                    )
                    ->where(
                        'refunds.status',
                        'completed'
                    )
                    ->sum('refund_items.quantity');
        }

        /*
        |--------------------------------------------------------------------------
        | Only show items that still have refundable quantity
        |--------------------------------------------------------------------------
        */

        $itemOptions = [];

        foreach ($record->items as $saleItem) {

            $alreadyRefunded =
                $refundedQuantities[$saleItem->id] ?? 0;

            $remaining =
                (float) $saleItem->quantity - $alreadyRefunded;

            if ($remaining <= 0) {
                continue;
            }

            $productName =
                $saleItem->product?->product_name
                ?? 'Product #' . $saleItem->product_id;

            $unitName =
                $saleItem->unit?->name
                ?? '';

            $itemOptions[$saleItem->id] =
                $productName .
                ($unitName ? " ({$unitName})" : '') .
                ' — ' .
                number_format($remaining, 3) .
                ' available @ M' .
                number_format(
                    (float) $saleItem->unit_price,
                    2
                );
        }

        return [

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            Repeater::make('items')
                ->label('Items to Refund')
                ->schema([

                    Select::make('sale_item_id')
                        ->label('Product')
                        ->options($itemOptions)
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->distinct(),

                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(0.001)
                        ->step(0.001)
                        ->rules([
                            function () use ($record) {
                                return function (
                                    string $attribute,
                                    $value,
                                    \Closure $fail
                                ) use ($record) {

                                    /*
                                     * Extract the repeater index.
                                     *
                                     * Example:
                                     * items.0.quantity
                                     */
                                    $parts = explode('.', $attribute);

                                    $index = $parts[1] ?? null;

                                    if ($index === null) {
                                        return;
                                    }

                                    /*
                                     * We cannot directly retrieve the
                                     * complete repeater state here,
                                     * therefore the final authoritative
                                     * validation remains in RefundService.
                                     */
                                };
                            },
                        ]),

                ])
                ->columns(2)
                ->defaultItems(1)
                ->minItems(1)
                ->addActionLabel('Add Another Item')
                ->reorderable(false)
                ->collapsible(),

            /*
            |--------------------------------------------------------------------------
            | Refund Method
            |--------------------------------------------------------------------------
            */

            Select::make('refund_method')
                ->label('Refund Method')
                ->options([
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'mobile_wallet' => 'Mobile Wallet',
                    'bank_transfer' => 'Bank Transfer',
                    'credit' => 'Store Credit',
                    'other' => 'Other',
                ])
                ->required()
                ->native(false),

            /*
            |--------------------------------------------------------------------------
            | Reference
            |--------------------------------------------------------------------------
            */

            TextInput::make('reference_number')
                ->label('Refund Reference')
                ->placeholder('Optional transaction/reference number')
                ->maxLength(255),

            /*
            |--------------------------------------------------------------------------
            | Reason
            |--------------------------------------------------------------------------
            */

            TextInput::make('reason')
                ->label('Refund Reason')
                ->placeholder('e.g. Customer returned damaged goods')
                ->required()
                ->maxLength(255),

            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */

            Textarea::make('remarks')
                ->label('Remarks')
                ->placeholder('Additional notes about this refund')
                ->rows(3)
                ->maxLength(1000),

        ];
    })

    ->action(function (
        Sale $record,
        array $data
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Process refund through RefundService
        |--------------------------------------------------------------------------
        */

        app(RefundService::class)->process(
            sale: $record,
            user: auth()->user(),
            items: $data['items'] ?? [],
            refundMethod: $data['refund_method'],
            referenceNumber: $data['reference_number'] ?? null,
            reason: $data['reason'] ?? null,
            remarks: $data['remarks'] ?? null,
        );

        /*
        |--------------------------------------------------------------------------
        | Success notification
        |--------------------------------------------------------------------------
        */

        \Filament\Notifications\Notification::make()
            ->title('Refund processed successfully')
            ->body(
                'Refund has been recorded and the sale audit trail has been updated.'
            )
            ->success()
            ->send();
    }),

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Page Title
    |--------------------------------------------------------------------------
    */

    public function getTitle(): string
    {
        return 'Sale ' . (
            $this->record->sale_number
            ?? '#' . $this->record->id
        );
    }

    public function getSubheading(): ?string
    {
        return 'Sales History';
    }

    /*
    |--------------------------------------------------------------------------
    | Infolist
    |--------------------------------------------------------------------------
    */

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | Sale Information
                |--------------------------------------------------------------------------
                */

                Section::make('Sale Information')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                TextEntry::make('sale_number')
                                    ->label('Receipt / Reference')
                                    ->weight('bold'),

                                TextEntry::make('created_at')
                                    ->label('Date / Time')
                                    ->dateTime('d M Y H:i'),

                                TextEntry::make('cashier.name')
                                    ->label('Cashier')
                                    ->default('—'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(
                                        fn (?string $state): string => match ($state) {
                                            'completed' => 'Completed',
                                            'draft' => 'Draft',
                                            'parked' => 'Parked',
                                            'held' => 'Held',
                                            'cancelled' => 'Cancelled',
                                            'refunded' => 'Refunded',
                                            'partially_refunded' => 'Partially Refunded',
                                            default => ucfirst($state ?? 'Unknown'),
                                        }
                                    )
                                    ->color(
                                        fn (?string $state): string => match ($state) {
                                            'completed' => 'success',
                                            'draft',
                                            'parked',
                                            'held' => 'warning',
                                            'cancelled' => 'danger',
                                            'refunded',
                                            'partially_refunded' => 'info',
                                            default => 'gray',
                                        }
                                    ),

                            ]),

                        Grid::make(3)
                            ->schema([

                                TextEntry::make('customer_name')
                                    ->label('Customer')
                                    ->state(function (Sale $record): string {

                                        if (! $record->customer) {
                                            return 'Walk-in Customer';
                                        }

                                        return trim(
                                            $record->customer->first_name . ' ' .
                                            ($record->customer->last_name ?? '')
                                        );
                                    }),

                                TextEntry::make('sale_type')
                                    ->label('Sale Type')
                                    ->badge()
                                    ->default('—'),

                                TextEntry::make('completed_at')
                                    ->label('Completed At')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('Not completed'),

                            ]),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Items
                |--------------------------------------------------------------------------
                */

                Section::make('Items')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([

                        TextEntry::make('items_list')
                            ->label('Products')
                            ->state(function (Sale $record): string {

                                if ($record->items->isEmpty()) {
                                    return 'No items found.';
                                }

                                return $record->items
                                    ->map(function ($item): string {

                                        /*
                                         * Try to get product name safely.
                                         */
                                        $productName = 'Product';

                                        if (method_exists($item, 'product')) {
                                            $productName =
                                                $item->product?->product_name
                                                ?? 'Product #' . ($item->product_id ?? '');
                                        }

                                        /*
                                         * Fallback to description if available.
                                         */
                                        if (
                                            $productName === 'Product'
                                            && filled($item->description ?? null)
                                        ) {
                                            $productName = $item->description;
                                        }

                                        $quantity = (float) (
                                            $item->quantity ?? 0
                                        );

                                        $unitPrice = (float) (
                                            $item->unit_price ?? 0
                                        );

                                        $lineTotal = (float) (
                                            $item->total
                                            ?? (
                                                $quantity * $unitPrice
                                            )
                                        );

                                        return sprintf(
                                            '%s   × %s   @ M%s   = M%s',
                                            $productName,
                                            rtrim(
                                                rtrim(
                                                    number_format(
                                                        $quantity,
                                                        2
                                                    ),
                                                    '0'
                                                ),
                                                '.'
                                            ),
                                            number_format(
                                                $unitPrice,
                                                2
                                            ),
                                            number_format(
                                                $lineTotal,
                                                2
                                            )
                                        );
                                    })
                                    ->implode("\n");
                            })
                            ->extraAttributes([
                                'style' => 'white-space: pre-line;',
                            ]),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Totals
                |--------------------------------------------------------------------------
                */

                Section::make('Totals')
                    ->icon('heroicon-o-calculator')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('LSL'),

                                TextEntry::make('discount')
                                    ->label('Discount')
                                    ->money('LSL'),

                                TextEntry::make('tax')
                                    ->label('Tax')
                                    ->money('LSL'),

                                TextEntry::make('total')
                                    ->label('Total')
                                    ->money('LSL')
                                    ->weight('bold')
                                    ->size('lg'),

                            ]),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Payments
                |--------------------------------------------------------------------------
                */

                Section::make('Payments')
                    ->icon('heroicon-o-banknotes')
                    ->schema([

                        TextEntry::make('payments_list')
                            ->label('Payment Details')
                            ->state(function (Sale $record): string {

                                if ($record->payments->isEmpty()) {
                                    return 'No payment recorded.';
                                }

                                return $record->payments
                                    ->map(function ($payment): string {

                                        $method =
                                            $payment->paymentMethod?->name
                                            ?? 'Unknown Payment';

                                        $amount = number_format(
                                            (float) (
                                                $payment->amount_paid ?? 0
                                            ),
                                            2
                                        );

                                        $received = number_format(
                                            (float) (
                                                $payment->amount_received ?? 0
                                            ),
                                            2
                                        );

                                        $change = number_format(
                                            (float) (
                                                $payment->change_amount ?? 0
                                            ),
                                            2
                                        );

                                        $result =
                                            "{$method}   Paid: M{$amount}";

                                        if (
                                            (float) (
                                                $payment->amount_received ?? 0
                                            ) > 0
                                        ) {
                                            $result .=
                                                "   Received: M{$received}";
                                        }

                                        if (
                                            (float) (
                                                $payment->change_amount ?? 0
                                            ) > 0
                                        ) {
                                            $result .=
                                                "   Change: M{$change}";
                                        }

                                        if (
                                            filled(
                                                $payment->reference_number
                                            )
                                        ) {
                                            $result .=
                                                "\nReference: " .
                                                $payment->reference_number;
                                        }

                                        if (
                                            filled(
                                                $payment->authorization_code
                                            )
                                        ) {
                                            $result .=
                                                "\nAuthorization: " .
                                                $payment->authorization_code;
                                        }

                                        return $result;
                                    })
                                    ->implode("\n\n");
                            })
                            ->extraAttributes([
                                'style' => 'white-space: pre-line;',
                            ]),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Lekuka
                |--------------------------------------------------------------------------
                |
                | Informational only for now.
                |--------------------------------------------------------------------------
                */

                Section::make('Lekuka')
                    ->icon('heroicon-o-document-check')
                    ->collapsed()
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextEntry::make('lekuka_submission')
                                    ->label('Submission')
                                    ->state(function (Sale $record): string {

                                        if ($record->submitted_to_lekuka) {
                                            return 'Submitted';
                                        }

                                        if (! $record->lekukaReceipt) {
                                            return 'Not Created';
                                        }

                                        return $record->lekukaReceipt->status
                                            ? ucfirst(
                                                strtolower(
                                                    $record->lekukaReceipt->status
                                                )
                                            )
                                            : 'Pending';
                                    })
                                    ->badge()
                                    ->color(
                                        function (string $state): string {

                                            return match (
                                                strtolower($state)
                                            ) {
                                                'submitted' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'gray',
                                            };
                                        }
                                    ),

                                TextEntry::make('lekuka_receipt_id')
                                    ->label('Lekuka Receipt ID')
                                    ->default('—'),

                                TextEntry::make('qr_code')
                                    ->label('QR Code')
                                    ->default('Not available'),

                            ]),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Remarks
                |--------------------------------------------------------------------------
                */

                Section::make('Remarks')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([

                        TextEntry::make('remarks')
                            ->label('Remarks')
                            ->default('No remarks.'),

                    ])
                    ->visible(
                        fn (Sale $record): bool =>
                            filled($record->remarks)
                    )
                    ->columnSpanFull(),

            ]);
    }
}
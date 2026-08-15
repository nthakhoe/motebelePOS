<?php

namespace App\Filament\Cashier\Resources\Sales;

use App\Filament\Cashier\Resources\Sales\Pages\ListSales;
use App\Filament\Cashier\Resources\Sales\Schemas\SaleForm;
use App\Filament\Cashier\Resources\Sales\Tables\SalesTable;
use App\Filament\Cashier\Resources\Sales\Pages\ViewSale;
use App\Models\Sale;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Models\Category;
use App\Models\Unit;
use App\Models\InventoryTransaction;
use App\Services\ProductService;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

use Filament\Tables\Table;
use Filament\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Sales History';

    protected static UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query
                    ->with([
                        'customer',
                        'cashier',
                        'items',
                        'payments',
                        'lekukaReceipt',
                    ])
                    ->withCount('items')
                    ->latest('created_at');
            })

            ->columns([

                /*
                |--------------------------------------------------------------------------
                | Receipt / Reference
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Receipt / Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                /*
                |--------------------------------------------------------------------------
                | Date
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date / Time')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                /*
                |--------------------------------------------------------------------------
                | Customer
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->default('Walk-in Customer')
                    ->searchable()
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | Cashier
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('cashier.name')
                    ->label('Cashier')
                    ->searchable()
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | Items
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->alignCenter(),

                /*
                |--------------------------------------------------------------------------
                | Payment Type
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('sale_type')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string =>
                        match ($state) {
                            'cash' => 'Cash',
                            'card' => 'Card',
                            'mobile_wallet' => 'Mobile Wallet',
                            'bank_transfer' => 'Bank Transfer',
                            'credit' => 'Credit',
                            'other' => 'Other',
                            default => ucfirst($state ?? 'Unknown'),
                        }
                    )
                    ->color(fn (?string $state): string =>
                        match ($state) {
                            'cash' => 'success',
                            'card' => 'info',
                            'mobile_wallet' => 'warning',
                            'bank_transfer' => 'gray',
                            'credit' => 'danger',
                            default => 'gray',
                        }
                    )
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | Total
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('LSL')
                    ->sortable()
                    ->weight('bold'),

                /*
                |--------------------------------------------------------------------------
                | Sale Status
                |--------------------------------------------------------------------------
                */

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string =>
                        match ($state) {
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
                    ->color(fn (?string $state): string =>
                        match ($state) {
                            'completed' => 'success',
                            'parked', 'held', 'draft' => 'warning',
                            'cancelled' => 'danger',
                            'refunded', 'partially_refunded' => 'info',
                            default => 'gray',
                        }
                    ),

                /*
                |--------------------------------------------------------------------------
                | Lekuka
                |--------------------------------------------------------------------------
                |
                | submitted_to_lekuka is boolean in Sale::$casts.
                | Therefore we should NOT treat it as SUBMITTED/PENDING/FAILED.
                |
                */

                Tables\Columns\TextColumn::make('lekuka_status')
                    ->label('Lekuka')
                    ->state(function (Sale $record): string {

                        if (! $record->lekukaReceipt) {
                            return 'Not Created';
                        }

                        if ($record->submitted_to_lekuka) {
                            return 'Submitted';
                        }

                        return $record->lekukaReceipt->status
                            ? ucfirst(strtolower($record->lekukaReceipt->status))
                            : 'Pending';
                    })
                    ->badge()
                    ->color(function (string $state): string {

                        return match (strtolower($state)) {
                            'submitted' => 'success',
                            'pending' => 'warning',
                            'failed' => 'danger',
                            'not created' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                /*
                |--------------------------------------------------------------------------
                | Today's Sales
                |--------------------------------------------------------------------------
                */

                Tables\Filters\Filter::make('today')
                    ->label("Today's Sales")
                    ->default()
                    ->query(fn (Builder $query) =>
                        $query->whereDate('created_at', today())
                    ),

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                Tables\Filters\SelectFilter::make('status')
                    ->label('Sale Status')
                    ->options([
                        'completed' => 'Completed',
                        'draft' => 'Draft',
                        'parked' => 'Parked',
                        'held' => 'Held',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                        'partially_refunded' => 'Partially Refunded',
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Payment
                |--------------------------------------------------------------------------
                */

                Tables\Filters\SelectFilter::make('sale_type')
                    ->label('Payment')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'mobile_wallet' => 'Mobile Wallet',
                        'bank_transfer' => 'Bank Transfer',
                        'credit' => 'Credit',
                        'other' => 'Other',
                    ]),

            ])

            ->actions([

                /*
                |--------------------------------------------------------------------------
                | View
                |--------------------------------------------------------------------------
                */

                ViewAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Sale $record): string =>
                        static::getUrl('view', [
                            'record' => $record,
                        ])
                    ),

                /*
                |--------------------------------------------------------------------------
                | Reprint
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Refund
                |--------------------------------------------------------------------------
                */

                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Sale $record): bool =>
                        $record->status === 'completed'
                    )
                    ->url(fn (Sale $record): string =>
                        static::getUrl('view', [
                            'record' => $record,
                        ])
                    ),

            ])

            ->bulkActions([])

            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
            'view' => ViewSale::route('/{record}'),
        ];
    }
}

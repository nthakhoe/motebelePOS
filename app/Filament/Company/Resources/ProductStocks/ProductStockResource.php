<?php

namespace App\Filament\Company\Resources\ProductStocks;

use App\Filament\Company\Resources\ProductStocks\Pages\CreateProductStock;
use App\Filament\Company\Resources\ProductStocks\Pages\EditProductStock;
use App\Filament\Company\Resources\ProductStocks\Pages\ListProductStocks;
use App\Filament\Company\Resources\ProductStocks\Schemas\ProductStockForm;
use App\Filament\Company\Resources\ProductStocks\Tables\ProductStocksTable;
use App\Filament\Company\Resources\InventoryTransactions\InventoryTransactionResource;
use App\Models\ProductStock;
use App\Models\InventoryTransaction;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use App\Models\StockAdjustment;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ProductStockResource extends Resource
{
    protected static ?string $model = ProductStock::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Product Stock';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Product')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                Select::make('product_id')
                                    ->relationship('product','product_name'),

                                Select::make('branch_id')
                                    ->relationship('branch','branch_name')
                                    ->disabled(),

                            ])

                    ]),

                Section::make('Current Stock')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('quantity_on_hand')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('quantity_reserved')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('quantity_available')
                                    ->numeric()
                                    ->disabled(),

                            ])

                    ]),

                Section::make('Costing')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('average_cost')
                                    ->prefix('M')
                                    ->disabled(),

                                TextInput::make('last_cost')
                                    ->prefix('M')
                                    ->disabled(),

                            ])

                    ]),

                Section::make('Replenishment')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('minimum_stock')
                                    ->numeric(),

                                TextInput::make('maximum_stock')
                                    ->numeric(),

                                TextInput::make('reorder_level')
                                    ->numeric(),

                            ])

                    ]),

                Section::make('Activity')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                Placeholder::make('last_received_at')
                                    ->content(fn ($record) =>
                                        $record?->last_received_at?->format('d M Y H:i') ?? 'Never'),

                                Placeholder::make('last_sold_at')
                                    ->content(fn ($record) =>
                                        $record?->last_sold_at?->format('d M Y H:i') ?? 'Never'),

                            ])

                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->defaultSort('product.product_name')

            ->columns([

                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('product.product_name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('branch.branch_name')
                    ->sortable(),

                TextColumn::make('quantity_on_hand')
                    ->numeric(3)
                    ->alignEnd()
                    ->badge()
                    ->color(fn ($record) => $record->quantity_on_hand <= 0 ? 'danger' : 'success'),

                TextColumn::make('quantity_reserved')
                    ->numeric(3)
                    ->alignEnd(),

                TextColumn::make('quantity_available')
                    ->numeric(3)
                    ->alignEnd(),

                TextColumn::make('average_cost')
                    ->money('LSL')
                    ->alignEnd(),

                IconColumn::make('isLowStock')
                    ->label('Low')
                    ->boolean(),

                TextColumn::make('last_received_at')
                    ->date(),

                TextColumn::make('last_sold_at')
                    ->date(),

            ])

            ->filters([

                SelectFilter::make('branch')
                    ->relationship('branch','branch_name'),

                Filter::make('low_stock')
                    ->query(fn ($query) =>
                        $query->whereColumn(
                            'quantity_on_hand',
                            '<=',
                            'reorder_level'
                        )),

                Filter::make('out_of_stock')
                    ->query(fn ($query) =>
                        $query->where('quantity_on_hand',0))

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                Action::make('history')
                    ->label('History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn ($record) =>
                        InventoryTransactionResource::getUrl('index',[
                            'tableFilters'=>[
                                'product'=>[
                                    'value'=>$record->product_id
                                ]
                            ]
                        ])),

                Action::make('adjust')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-scale')
                    ->color('warning')
                    ->modalHeading('Adjust Stock')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\Radio::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->options([
                                'increase' => 'Increase Stock',
                                'decrease' => 'Decrease Stock',
                            ])
                            ->inline()
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required(),

                        Forms\Components\TextInput::make('reference_no')
                            ->maxLength(100),

                        Forms\Components\Textarea::make('reason')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (array $data, ProductStock $record) {

                        DB::transaction(function () use ($record, $data) {

                            $stockBefore = $record->quantity;

                            if ($data['adjustment_type'] === 'increase') {
                                $stockAfter = $stockBefore + $data['quantity'];
                            } else {

                                if ($data['quantity'] > $stockBefore) {
                                    throw new \Exception('Adjustment quantity exceeds available stock.');
                                }

                                $stockAfter = $stockBefore - $data['quantity'];
                            }

                            // Update Product Stock
                            $record->update([
                                'quantity_on_hand' => $stockAfter,
                            ]);

                            // Save Adjustment
                            StockAdjustment::create([
                                'company_id'       => $record->company_id,
                                'product_id'       => $record->product_id,
                                'user_id'          => Auth::id(),
                                'adjustment_type'  => $data['adjustment_type'],
                                'quantity'         => $data['quantity'],
                                'stock_before'     => $stockBefore,
                                'stock_after'      => $stockAfter,
                                'reference_no'     => $data['reference_no'],
                                'reason'           => $data['reason'],
                                'adjustment_date'  => now(),
                            ]);

                            // Create Stock Ledger Entry
                            InventoryTransaction::create([
                                'company_id'       => $record->company_id,
                                'product_id'       => $record->product_id,
                                'transaction_type' => 'adjustment',
                                'reference_no'     => null,
                                'quantity'         => $data['adjustment_type'] === 'increase'
                                    ? $data['quantity']
                                    : -$data['quantity'],
                                'balance_after'          => $stockAfter,
                                'remarks'          => $data['reason'],
                                'created_by'       => Auth::id(),
                            ]);

                        });

                        Notification::make()
                            ->title('Stock adjusted successfully.')
                            ->success()
                            ->send();

                    })

            ])

            ->bulkActions([
                
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()

            ->where('company_id', auth()->user()->company_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductStocks::route('/'),
            'create' => CreateProductStock::route('/create'),
            'edit' => EditProductStock::route('/{record}/edit'),
        ];
    }
}

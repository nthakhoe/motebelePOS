<?php

namespace App\Filament\Company\Resources\InventoryTransactions;

use App\Filament\Company\Resources\InventoryTransactions\Pages\CreateInventoryTransaction;
use App\Filament\Company\Resources\InventoryTransactions\Pages\EditInventoryTransaction;
use App\Filament\Company\Resources\InventoryTransactions\Pages\ListInventoryTransactions;
use App\Filament\Company\Resources\InventoryTransactions\Schemas\InventoryTransactionForm;
use App\Filament\Company\Resources\InventoryTransactions\Tables\InventoryTransactionsTable;
use App\Models\InventoryTransaction;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;


class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Ledger';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Transaction Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('reference_no')
                                    ->disabled(),

                                Select::make('transaction_type')
                                    ->options([
                                        'Opening' => 'Opening',
                                        'Purchase' => 'Purchase',
                                        'Sale' => 'Sale',
                                        'Sale Return' => 'Sale Return',
                                        'Purchase Return' => 'Purchase Return',
                                        'Adjustment' => 'Adjustment',
                                        'Transfer In' => 'Transfer In',
                                        'Transfer Out' => 'Transfer Out',
                                        'Damage' => 'Damage',
                                        'Expired' => 'Expired',
                                        'Production' => 'Production',
                                    ])
                                    ->disabled(),

                                TextInput::make('movement')
                                    ->disabled(),

                                Select::make('product_id')
                                    ->relationship('product', 'product_name')
                                    ->disabled(),

                            ]),

                    ]),

                Section::make('Quantities')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('unit_cost')
                                    ->prefix('M')
                                    ->disabled(),

                                TextInput::make('unit_price')
                                    ->prefix('M')
                                    ->disabled(),

                                TextInput::make('balance_after')
                                    ->disabled(),

                            ])

                    ]),

                Section::make('Remarks')
                    ->schema([

                        Textarea::make('remarks')
                            ->disabled()

                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->defaultSort('created_at', 'desc')

            ->columns([

                BadgeColumn::make('transaction_type')
                    ->colors([
                        'primary' => 'Opening',
                        'success' => 'Purchase',
                        'warning' => 'Sale',
                        'info' => 'Sale Return',
                        'danger' => 'Damage',
                        'gray' => 'Adjustment',
                    ]),

                BadgeColumn::make('movement')
                    ->colors([
                        'success' => 'IN',
                        'danger' => 'OUT',
                    ]),

                TextColumn::make('product.product_name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('quantity')
                    ->numeric(3)
                    ->alignEnd(),

                TextColumn::make('balance_after')
                    ->label('Balance')
                    ->numeric(3)
                    ->alignEnd(),

                TextColumn::make('creator.name')
                    ->label('User')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

            ])

            ->filters([

                SelectFilter::make('transaction_type')
                    ->options([
                        'Opening' => 'Opening',
                        'Purchase' => 'Purchase',
                        'Sale' => 'Sale',
                        'Sale Return' => 'Sale Return',
                        'Purchase Return' => 'Purchase Return',
                        'Adjustment' => 'Adjustment',
                        'Transfer In' => 'Transfer In',
                        'Transfer Out' => 'Transfer Out',
                        'Damage' => 'Damage',
                        'Expired' => 'Expired',
                        'Production' => 'Production',
                    ]),

                SelectFilter::make('movement')
                    ->options([
                        'IN' => 'Stock In',
                        'OUT' => 'Stock Out',
                    ]),

                SelectFilter::make('product')
                    ->relationship('product', 'product_name'),

                Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),

            ])

            ->actions([

                ViewAction::make(),

            ])

            ->bulkActions([

                ExportBulkAction::make(),

            ]);
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
            'index' => ListInventoryTransactions::route('/'),
            'create' => CreateInventoryTransaction::route('/create'),
            'edit' => EditInventoryTransaction::route('/{record}/edit'),
        ];
    }
}

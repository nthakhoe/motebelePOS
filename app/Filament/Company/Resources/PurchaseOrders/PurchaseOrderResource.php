<?php

namespace App\Filament\Company\Resources\PurchaseOrders;

use App\Filament\Company\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Company\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Company\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Company\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Company\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Purchase Order')
                    ->columns(3)
                    ->schema([

                        Select::make('supplier_id')
                            ->relationship('supplier', 'business_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('order_date')
                            ->required()
                            ->default(now()),

                        DatePicker::make('expected_delivery_date'),

                        TextInput::make('purchase_order_no')
                            ->required()
                            ->disabledOn('edit'),

                        Select::make('status')
                            ->options([
                                'Draft' => 'Draft',
                                'Submitted' => 'Submitted',
                                'Approved' => 'Approved',
                                'Partially Received' => 'Partially Received',
                                'Received' => 'Received',
                                'Cancelled' => 'Cancelled',
                                'Closed' => 'Closed',
                            ])
                            ->disabled(),

                    ]),

                Section::make('Totals')
                    ->columns(4)
                    ->schema([

                        TextInput::make('subtotal')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('discount')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('tax')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('total')
                            ->numeric()
                            ->disabled(),

                    ]),

                Section::make('Remarks')
                    ->schema([

                        Textarea::make('remarks')
                            ->rows(3)

                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

        ->columns([

            TextColumn::make('purchase_order_no')
                ->searchable()
                ->sortable(),

            TextColumn::make('supplier.business_name')
                ->searchable()
                ->sortable(),

            TextColumn::make('order_date')
                ->date(),

            TextColumn::make('expected_delivery_date')
                ->date(),

            TextColumn::make('status')
                ->badge(),

            TextColumn::make('total')
                ->money('LSL'),

            TextColumn::make('creator.name')
                ->label('Created By'),

        ])
        ->filters([

            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'Draft'=>'Draft',
                    'Submitted'=>'Submitted',
                    'Approved'=>'Approved',
                    'Received'=>'Received',
                    'Closed'=>'Closed',
                ]),

            Tables\Filters\Filter::make('order_date')
                ->form([
                    DatePicker::make('from'),
                    DatePicker::make('until'),
                ])

        ]);
    }

    public static function getRelations(): array
    {
        return [

            RelationManagers\PurchaseOrderItemsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}

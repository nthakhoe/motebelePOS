<?php

namespace App\Filament\Company\Resources\PurchaseOrders\RelationManagers;

use App\Models\Product;
use App\Services\Procurement\PurchaseOrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
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
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;


class PurchaseOrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'product.product_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'product_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                TextInput::make('ordered_quantity')
                    ->numeric()
                    ->required()
                    ->live(),

                TextInput::make('unit_cost')
                    ->numeric()
                    ->required()
                    ->live(),

                TextInput::make('discount')
                    ->numeric()
                    ->default(0)
                    ->live(),

                TextInput::make('tax')
                    ->numeric()
                    ->default(0)
                    ->live(),

                TextInput::make('line_total')
                    ->disabled()
                    ->dehydrated()
                    ->formatStateUsing(function ($state, Get $get) {

                        return
                            ($get('ordered_quantity') * $get('unit_cost'))
                            - $get('discount')
                            + $get('tax');

                    }),

                Textarea::make('remarks')
                    ->columnSpanFull(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table

            ->columns([

                Tables\Columns\TextColumn::make('product.product_name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('ordered_quantity')
                    ->numeric(),

                Tables\Columns\TextColumn::make('received_quantity')
                    ->numeric(),

                Tables\Columns\TextColumn::make('remaining_quantity')
                    ->numeric(),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->money('LSL'),

                Tables\Columns\TextColumn::make('line_total')
                    ->money('LSL'),

                Tables\Columns\BadgeColumn::make('status'),

            ])

            ->headerActions([

                CreateAction::make()

                    ->visible(fn () =>
                        $this->ownerRecord->status === 'Draft'
                    )

                    ->mutateFormDataUsing(function (array $data) {

                        $data['remaining_quantity'] =
                            $data['ordered_quantity'];

                        $data['line_total'] =
                            ($data['ordered_quantity'] * $data['unit_cost'])
                            - $data['discount']
                            + $data['tax'];

                        return $data;
                    })

                    ->after(function () {

                        app(PurchaseOrderService::class)
                            ->refreshTotals($this->ownerRecord);

                    }),

            ])

            ->actions([

                EditAction::make()

                    ->visible(fn () =>
                        $this->ownerRecord->status === 'Draft'
                    )

                    ->mutateFormDataUsing(function (array $data) {

                        $data['remaining_quantity'] =
                            $data['ordered_quantity']
                            - $data['received_quantity'];

                        $data['line_total'] =
                            ($data['ordered_quantity'] * $data['unit_cost'])
                            - $data['discount']
                            + $data['tax'];

                        return $data;
                    })

                    ->after(function () {

                        app(PurchaseOrderService::class)
                            ->refreshTotals($this->ownerRecord);

                    }),

                DeleteAction::make()

                    ->visible(fn () =>
                        $this->ownerRecord->status === 'Draft'
                    )

                    ->after(function () {

                        app(PurchaseOrderService::class)
                            ->refreshTotals($this->ownerRecord);

                    }),

            ])

            ->bulkActions([

                DeleteBulkAction::make()

                    ->after(function () {

                        app(PurchaseOrderService::class)
                            ->refreshTotals($this->ownerRecord);

                    }),

            ]);
    }
}
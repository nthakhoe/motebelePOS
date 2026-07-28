<?php

namespace App\Filament\Company\Resources\StockCounts\RelationManagers;

use App\Services\Inventory\StockCountService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;

class StockCountItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Count Items';

    protected static ?string $recordTitleAttribute = 'product.name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                TextInput::make('system_quantity')
                    ->label('System Quantity')
                    ->disabled(),

                TextInput::make('counted_quantity')
                    ->label('Physical Count')
                    ->numeric()
                    ->required(),

                TextInput::make('variance_quantity')
                    ->label('Variance')
                    ->disabled(),

                Textarea::make('remarks')
                    ->columnSpanFull(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.product_name')

            ->columns([

                Tables\Columns\TextColumn::make('product.product_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('system_quantity')
                    ->label('System')
                    ->numeric(2),

                Tables\Columns\TextColumn::make('counted_quantity')
                    ->label('Counted')
                    ->numeric(2),

                Tables\Columns\TextColumn::make('variance_quantity')
                    ->label('Variance')
                    ->badge()
                    ->color(function ($state) {

                        if ($state > 0) {
                            return 'success';
                        }

                        if ($state < 0) {
                            return 'danger';
                        }

                        return 'gray';
                    }),

                Tables\Columns\BadgeColumn::make('status'),

            ])

            ->headerActions([])

            ->actions([

                EditAction::make()
                    ->after(function ($record) {

                        app(StockCountService::class)
                            ->refreshProgress($record->stockCount);

                    }),

            ])

            ->bulkActions([]);
    }
}
<?php

namespace App\Filament\Company\Resources\StockCounts;

use App\Filament\Company\Resources\StockCounts\Pages\CreateStockCount;
use App\Filament\Company\Resources\StockCounts\Pages\EditStockCount;
use App\Filament\Company\Resources\StockCounts\Pages\ListStockCounts;
use App\Filament\Company\Resources\StockCounts\Schemas\StockCountForm;
use App\Filament\Company\Resources\StockCounts\Tables\StockCountsTable;
use App\Models\StockCount;
use App\Models\Company;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
use Filament\Forms\Components\DatePicker;

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
use Filament\Actions\ExportBulkAction;

class StockCountResource extends Resource
{
    protected static ?string $model = StockCount::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Count';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Stock Count')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(2)
                    ->schema([

                        TextInput::make('reference')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'SC-'.date('YmdHis'))
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('count_date')
                            ->default(now())
                            ->required(),

                        Select::make('company_id')
                            ->relationship('company', 'company_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('branch_id')
                            ->relationship(
                                'branch',
                                'branch_name',
                                fn ($query, callable $get) =>
                                    $query->where('company_id', $get('company_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('count_type')
                            ->options([
                                'Full' => 'Full Stock Count',
                                'Partial' => 'Partial Count',
                                'Cycle' => 'Cycle Count',
                            ])
                            ->default('Full')
                            ->required(),

                        Select::make('status')
                            ->options([
                                'Draft' => 'Draft',
                                'In Progress' => 'In Progress',
                                'Completed' => 'Completed',
                                'Approved' => 'Approved',
                                'Cancelled' => 'Cancelled',
                            ])
                            ->default('Draft')
                            ->required(),

                        Textarea::make('remarks')
                            ->columnSpanFull(),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.company_name')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('branch.branch_name')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('count_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('count_date')
                    ->date(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'Draft',
                        'warning' => 'In Progress',
                        'success' => 'Completed',
                        'primary' => 'Approved',
                        'danger' => 'Cancelled',
                    ]),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),

            ])
            ->actions([

                ViewAction::make(),

                EditAction::make()
                    ->visible(fn ($record) => $record->status == 'Draft'),

                Action::make('Count Items')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary'),

                Action::make('Start Count')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status == 'Draft'),

                Action::make('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status == 'In Progress'),

                Action::make('Approve')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status == 'Completed'),

            ])
            ->filters([

                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'company_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'branch_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'In Progress' => 'In Progress',
                        'Completed' => 'Completed',
                        'Approved' => 'Approved',
                        'Cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('count_type')
                    ->options([
                        'Full' => 'Full',
                        'Partial' => 'Partial',
                        'Cycle' => 'Cycle',
                    ]),

            ])
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
            'index' => ListStockCounts::route('/'),
            'create' => CreateStockCount::route('/create'),
            'edit' => EditStockCount::route('/{record}/edit'),
        ];
    }
}

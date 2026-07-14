<?php

namespace App\Filament\Company\Resources\Products;

use App\Filament\Company\Resources\Products\Pages\CreateProduct;
use App\Filament\Company\Resources\Products\Pages\EditProduct;
use App\Filament\Company\Resources\Products\Pages\ListProducts;
use App\Filament\Company\Resources\Products\Schemas\ProductForm;
use App\Filament\Company\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Tabs::make('Product')

                    ->tabs([

                        /*
                        |--------------------------------------------------------------------------
                        | GENERAL
                        |--------------------------------------------------------------------------
                        */

                        Tab::make('General')

                            ->icon('heroicon-o-cube')

                            ->schema([

                                Section::make()

                                    ->schema([

                                        Grid::make(2)

                                            ->schema([

                                                TextInput::make('sku')
                                                    ->required()
                                                    ->maxLength(50),

                                                TextInput::make('barcode')
                                                    ->maxLength(100),

                                                TextInput::make('product_name')
                                                    ->required()
                                                    ->columnSpanFull(),

                                                TextInput::make('short_name'),

                                                Select::make('category_id')
                                                    ->relationship(
                                                        'category',
                                                        'category_name',
                                                        fn ($query) => $query
                                                            ->where('company_id', auth()->user()->company_id)
                                                    )
                                                    ->searchable()
                                                    ->preload(),

                                                Select::make('unit_id')
                                                    ->relationship(
                                                        'unit',
                                                        'name',
                                                        fn ($query) => $query
                                                            ->where('company_id', auth()->user()->company_id)
                                                    )
                                                    ->searchable()
                                                    ->preload(),

                                            ]),

                                        Textarea::make('description')
                                            ->rows(4)

                                    ])

                            ]),

                        /*
                        |--------------------------------------------------------------------------
                        | PRICING
                        |--------------------------------------------------------------------------
                        */

                        Tab::make('Pricing')

                            ->icon('heroicon-o-banknotes')

                            ->schema([

                                Grid::make(3)

                                    ->schema([

                                        TextInput::make('cost_price')
                                            ->numeric()
                                            ->prefix('M'),

                                        TextInput::make('selling_price')
                                            ->numeric()
                                            ->prefix('M'),

                                        TextInput::make('minimum_price')
                                            ->numeric()
                                            ->prefix('M'),

                                        TextInput::make('tax_rate')
                                            ->numeric()
                                            ->suffix('%')

                                    ])

                            ]),

                        /*
                        |--------------------------------------------------------------------------
                        | INVENTORY
                        |--------------------------------------------------------------------------
                        */

                        Tab::make('Inventory')

                            ->icon('heroicon-o-archive-box')

                            ->schema([

                                Grid::make(3)

                                    ->schema([

                                        Toggle::make('track_inventory')
                                            ->default(true),

                                        Toggle::make('allow_negative_stock')
                                            ->default(false),

                                        Toggle::make('is_service')
                                            ->live(),

                                        TextInput::make('minimum_stock')
                                            ->numeric()
                                            ->disabled(fn ($get) => $get('is_service')),

                                        TextInput::make('maximum_stock')
                                            ->numeric()
                                            ->disabled(fn ($get) => $get('is_service')),

                                        TextInput::make('reorder_level')
                                            ->numeric()
                                            ->disabled(fn ($get) => $get('is_service')),

                                        Toggle::make('is_active')
                                            ->default(true)

                                    ])

                            ]),

                        /*
                        |--------------------------------------------------------------------------
                        | MEDIA
                        |--------------------------------------------------------------------------
                        */

                        Tab::make('Media')

                            ->icon('heroicon-o-photo')

                            ->schema([

                                FileUpload::make('image')

                                    ->image()

                                    ->imageEditor()

                                    ->directory('products')

                                    ->columnSpanFull()

                            ])

                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->defaultSort('product_name')

            ->columns([

                ImageColumn::make('image')
                    ->square(),

                TextColumn::make('sku')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('barcode')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('product_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('category.category_name')
                    ->sortable(),

                TextColumn::make('selling_price')
                    ->money('LSL')
                    ->sortable(),

                TextColumn::make('minimum_stock')
                    ->label('Min'),

                IconColumn::make('track_inventory')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->date('d M Y')

            ])

            ->filters([

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'category_name'),

                Tables\Filters\TernaryFilter::make('is_active'),

                Tables\Filters\TernaryFilter::make('track_inventory')

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()

            ])

            ->bulkActions([

                DeleteBulkAction::make(),

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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}

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
use App\Models\LekukaDevice;
use App\Models\LekukaConfiguration;
use Filament\Forms\Components\Hidden;
use Filament\Support\Enums\Width;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    /**
     * Retrieve the Lekuka configuration belonging to
     * the current company's registered device.
     */


    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected static function getLekukaConfiguration(): ?LekukaConfiguration
    {
        $user = auth()->user();

        if (! $user || ! $user->company_id) {
            return null;
        }

        $query = LekukaDevice::query()
            ->where('company_id', $user->company_id)
            ->where('registered', true);

        // If the logged-in user is assigned to a branch,
        // retrieve that branch's device.
        if (! empty($user->branch_id)) {
            $query->where('branch_id', $user->branch_id);
        }

        $devices = $query->get();

        // Do not arbitrarily select a device if multiple
        // registered devices match the current context.
        if ($devices->count() !== 1) {
            return null;
        }

        return LekukaConfiguration::where(
            'device_id',
            $devices->first()->id
        )->first();
    }


    /**
     * Retrieve applicable taxes from the saved configuration.
     */
    protected static function getLekukaTaxes(): array
    {
        $configuration = static::getLekukaConfiguration();

        if (! $configuration) {
            return [];
        }

        return collect($configuration->taxes ?? [])
            ->filter(fn ($tax) => isset($tax['taxID'], $tax['taxName'], $tax['taxType']))
            ->values()
            ->all();
    }


    /**
     * Generate dropdown options from Lekuka tax allocations.
     */
    protected static function getLekukaTaxOptions(): array
    {
        return collect(static::getLekukaTaxes())
            ->mapWithKeys(function ($tax) {

                $rate = isset($tax['taxRate'])
                    ? ' - Rate/Value: ' . $tax['taxRate']
                    : '';

                $label = $tax['taxName']
                    . ' (' . $tax['taxType'] . ')'
                    . $rate;

                return [
                    $tax['taxID'] => $label,
                ];
            })
            ->all();
    }

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

                                Section::make('Product Pricing')
                                    ->description('Configure cost price, margin and final customer selling price.')
                                    ->schema([

                                        Grid::make(3)
                                            ->schema([

                                                TextInput::make('cost_price')
                                                    ->label('Cost Price')
                                                    ->numeric()
                                                    ->prefix('M')
                                                    ->required()
                                                    ->minValue(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {

                                                        $cost = (float) ($state ?? 0);
                                                        $margin = (float) ($get('margin_percentage') ?? 30);

                                                        if ($cost < 0) {
                                                            return;
                                                        }

                                                        $sellingPrice = round(
                                                            $cost * (1 + ($margin / 100)),
                                                            2
                                                        );

                                                        $set('selling_price', number_format($sellingPrice, 2, '.', ''));
                                                    }),

                                                TextInput::make('margin_percentage')
                                                    ->label('Margin Percentage')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->default(30)
                                                    ->required()
                                                    ->minValue(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {

                                                        $cost = (float) ($get('cost_price') ?? 0);
                                                        $margin = (float) ($state ?? 0);

                                                        if ($cost < 0 || $margin < 0) {
                                                            return;
                                                        }

                                                        $sellingPrice = round(
                                                            $cost * (1 + ($margin / 100)),
                                                            2
                                                        );

                                                        $set('selling_price', number_format($sellingPrice, 2, '.', ''));
                                                    }),

                                                TextInput::make('selling_price')
                                                    ->label('Selling Price (Final Customer Price)')
                                                    ->numeric()
                                                    ->prefix('M')
                                                    ->required()
                                                    ->minValue(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {

                                                        $cost = (float) ($get('cost_price') ?? 0);
                                                        $sellingPrice = (float) ($state ?? 0);

                                                        if ($cost <= 0 || $sellingPrice < 0) {
                                                            $set('margin_percentage', 0);
                                                            return;
                                                        }

                                                        $margin = (($sellingPrice - $cost) / $cost) * 100;

                                                        $set(
                                                            'margin_percentage',
                                                            number_format(round($margin, 2), 2, '.', '')
                                                        );
                                                    }),

                                                TextInput::make('minimum_price')
                                                    ->label('Minimum Selling Price')
                                                    ->numeric()
                                                    ->prefix('M')
                                                    ->minValue(0),

                                            ]),

                                        Section::make('Lekuka Tax Allocations')
                                            ->description('Select the main tax and, where applicable, an additional levy.')
                                            ->schema([

                                                Grid::make(2)
                                                    ->schema([

                                                        Select::make('lekuka_tax_id')
                                                            ->label('Main Lekuka Tax Allocation')
                                                            ->options(fn () => static::getLekukaTaxOptions())
                                                            ->searchable()
                                                            ->preload()
                                                            ->live()
                                                            ->required()
                                                            ->placeholder('Select main tax allocation')
                                                            ->helperText(function () {

                                                                if (empty(static::getLekukaTaxOptions())) {
                                                                    return 'No Lekuka tax allocations available. Check the registered device and downloaded configuration.';
                                                                }

                                                                return 'Select the main tax classification applicable to this product.';
                                                            })
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {

                                                                $tax = collect(static::getLekukaTaxes())
                                                                    ->firstWhere('taxID', (int) $state);

                                                                if (! $tax) {
                                                                    $set('lekuka_tax_type', null);
                                                                    $set('lekuka_tax_code', null);
                                                                    $set('tax_rate', 0);

                                                                    return;
                                                                }

                                                                $set('lekuka_tax_type', $tax['taxType']);
                                                                $set('lekuka_tax_code', null);
                                                                $set('tax_rate', $tax['taxRate'] ?? 0);
                                                            }),

                                                        Select::make('lekuka_additional_tax_id')
                                                            ->label('Additional Lekuka Tax / Levy (Optional)')
                                                            ->options(fn () => static::getLekukaTaxOptions())
                                                            ->searchable()
                                                            ->preload()
                                                            ->live()
                                                            ->placeholder('No additional tax')
                                                            ->rules([
                                                                'different:lekuka_tax_id',
                                                            ])
                                                            ->helperText('Select an additional levy only when applicable.')
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {

                                                                if (! $state) {
                                                                    $set('lekuka_additional_tax_type', null);
                                                                    $set('lekuka_additional_tax_code', null);
                                                                    $set('lekuka_additional_tax_rate', null);

                                                                    return;
                                                                }

                                                                $tax = collect(static::getLekukaTaxes())
                                                                    ->firstWhere('taxID', (int) $state);

                                                                if (! $tax) {
                                                                    $set('lekuka_additional_tax_type', null);
                                                                    $set('lekuka_additional_tax_code', null);
                                                                    $set('lekuka_additional_tax_rate', null);

                                                                    return;
                                                                }

                                                                $set('lekuka_additional_tax_type', $tax['taxType']);
                                                                $set('lekuka_additional_tax_code', null);
                                                                $set('lekuka_additional_tax_rate', $tax['taxRate'] ?? 0);
                                                            }),

                                                        TextInput::make('tax_rate')
                                                            ->label('Main Tax Rate / Value')
                                                            ->numeric()
                                                            ->readOnly(),

                                                        TextInput::make('lekuka_additional_tax_rate')
                                                            ->label('Additional Tax Rate / Value')
                                                            ->numeric()
                                                            ->readOnly(),

                                                        Hidden::make('lekuka_tax_type'),

                                                        Hidden::make('lekuka_tax_code'),

                                                        Hidden::make('lekuka_additional_tax_type'),

                                                        Hidden::make('lekuka_additional_tax_code'),

                                                    ]),

                                            ]),

                                    ]),

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
                                    ->extraAttributes([
                                        'class' => 'product-full-width-tabs',
                                    ]),

                            ])

                    ])

            ])
            ->columns(1);
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

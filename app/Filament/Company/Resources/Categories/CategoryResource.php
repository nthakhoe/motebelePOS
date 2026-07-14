<?php

namespace App\Filament\Company\Resources\Categories;

use App\Filament\Company\Resources\Categories\Pages\CreateCategory;
use App\Filament\Company\Resources\Categories\Pages\EditCategory;
use App\Filament\Company\Resources\Categories\Pages\ListCategories;
use App\Filament\Company\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Company\Resources\Categories\Tables\CategoriesTable;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use App\Models\Category;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'category_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Category Information')
                    ->icon('heroicon-o-tag')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('category_code')
                                    ->label('Category Code')
                                    ->required()
                                    ->dehydrated(),

                                TextInput::make('category_name')
                                    ->required()
                                    ->maxLength(100)
                                    ->live(onBlur: true),

                            ]),

                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship(
                                'parent',
                                'category_name',
                                fn ($query) => $query
                                    ->where('company_id', auth()->user()->company_id)
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Top Level Category'),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('image')
                            ->directory('categories')
                            ->image()
                            ->imageEditor()
                            ->columnSpanFull(),

                    ]),

                Section::make('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(1),

                                Toggle::make('is_active')
                                    ->default(true),

                            ])

                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->defaultSort('category_name')

            ->columns([

                ImageColumn::make('image')
                    ->circular()
                    ->defaultImageUrl(asset('images/no-image.png')),

                TextColumn::make('category_code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('category_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('parent.category_name')
                    ->label('Parent'),

                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->badge(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable(),

            ])

            ->filters([

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

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

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}

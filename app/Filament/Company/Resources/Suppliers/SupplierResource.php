<?php

namespace App\Filament\Company\Resources\Suppliers;

use App\Filament\Company\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Company\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Company\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Company\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Company\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Suppliers;
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

class SupplierResource extends Resource
{
    protected static ?string $model = Suppliers::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static UnitEnum|string|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Suppliers';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Supplier Information')
                    ->columns(3)
                    ->schema([

                        TextInput::make('supplier_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        TextInput::make('business_name')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->default(true),

                        TextInput::make('contact_person')
                            ->maxLength(255),

                        TextInput::make('tin_number')
                            ->label('TIN Number'),

                        TextInput::make('vat_number')
                            ->label('VAT Number'),

                    ]),

                Section::make('Contact Information')
                    ->columns(2)
                    ->schema([

                        TextInput::make('phone')
                            ->tel(),

                        TextInput::make('alternative_phone')
                            ->tel(),

                        TextInput::make('email')
                            ->email(),

                        TextInput::make('website')
                            ->url(),

                    ]),

                Section::make('Address')
                    ->columns(2)
                    ->schema([

                        TextInput::make('address_line1'),

                        TextInput::make('address_line2'),

                        TextInput::make('city'),

                        TextInput::make('district'),

                        TextInput::make('country')
                            ->default('Lesotho'),

                    ]),

                Section::make('Banking Details')
                    ->columns(2)
                    ->schema([

                        TextInput::make('bank_name'),

                        TextInput::make('branch_name'),

                        TextInput::make('account_name'),

                        TextInput::make('account_number'),

                    ]),

                Section::make('Credit Information')
                    ->columns(3)
                    ->schema([

                        TextInput::make('credit_days')
                            ->numeric()
                            ->default(30)
                            ->suffix('Days'),

                        TextInput::make('opening_balance')
                            ->numeric()
                            ->prefix('M')
                            ->default(0),

                        TextInput::make('current_balance')
                            ->numeric()
                            ->prefix('M')
                            ->disabled(),

                    ]),

                Section::make('Notes')
                    ->schema([

                        Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->defaultSort('business_name')

            ->columns([

                Tables\Columns\TextColumn::make('supplier_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('credit_days')
                    ->label('Credit')
                    ->suffix(' Days')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_balance')
                    ->money('LSL')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable(),

            ])

            ->filters([

                Tables\Filters\TernaryFilter::make('is_active'),

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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}

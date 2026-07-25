<?php

namespace App\Filament\Admin\Resources\Companies;

use App\Filament\Admin\Resources\Companies\Pages\CreateCompany;
use App\Filament\Admin\Resources\Companies\Pages\EditCompany;
use App\Filament\Admin\Resources\Companies\Pages\ListCompanies;
use App\Filament\Admin\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Admin\Resources\Companies\Tables\CompaniesTable;
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

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Company';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Business Information')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([

                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('trading_name')
                            ->maxLength(255),

                        TextInput::make('registration_number')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('tax_number')
                            ->label('TIN')
                            ->required()
                            ->maxLength(100),

                        Select::make('industry')
                            ->options([
                                'Retail' => 'Retail',
                                'Wholesale' => 'Wholesale',
                                'Restaurant' => 'Restaurant',
                                'Pharmacy' => 'Pharmacy',
                                'Supermarket' => 'Supermarket',
                                'Other' => 'Other',
                            ])
                            ->searchable(),

                        Toggle::make('active')
                            ->default(true),

                    ]),

                Section::make('Contact Information')
                    ->icon('heroicon-o-phone')
                    ->columns(2)
                    ->schema([

                        TextInput::make('email')
                            ->email(),

                        TextInput::make('phone'),

                        TextInput::make('website'),

                        Textarea::make('address')
                            ->columnSpanFull(),

                        TextInput::make('city'),

                        TextInput::make('district'),

                        TextInput::make('country')
                            ->default('Lesotho'),

                    ]),

                Section::make('Business Owner')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([

                        TextInput::make('owner_name'),

                        TextInput::make('owner_phone'),

                        TextInput::make('owner_email')
                            ->email(),

                    ]),

                Section::make('Fiscal Information')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([

                        Select::make('vat_registered')
                            ->options([
                                1 => 'Yes',
                                0 => 'No',
                            ])
                            ->default(1),

                        TextInput::make('vat_number'),

                        TextInput::make('currency')
                            ->default('LSL'),

                        Select::make('timezone')
                            ->default('Africa/Maseru')
                            ->options([
                                'Africa/Maseru' => 'Africa/Maseru',
                            ]),

                    ]),

                Section::make('System Configuration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->columns(2)
                    ->schema([

                        Toggle::make('multi_branch')
                            ->default(true),

                        Toggle::make('inventory_enabled')
                            ->default(true),

                        Toggle::make('e_invoicing_enabled')
                            ->default(true),

                        Toggle::make('customer_management')
                            ->default(true),

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(asset('images/company-placeholder.png')),

                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trading_name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('registration_number')
                    ->label('Registration'),

                Tables\Columns\TextColumn::make('tax_number')
                    ->label('TIN'),

                Tables\Columns\TextColumn::make('phone'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\IconColumn::make('vat_registered')
                    ->boolean(),

                Tables\Columns\TextColumn::make('branches_count')
                    ->counts('branches')
                    ->label('Branches')
                    ->badge()
                    ->color('info'),

                //Tables\Columns\TextColumn::make('devices_count')
                //    ->counts('devices')
                //    ->label('Devices')
                //    ->badge()
                //    ->color('warning'),

                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y'),

            ])
            ->filters([

                Tables\Filters\TernaryFilter::make('active'),

                Tables\Filters\SelectFilter::make('industry')
                    ->options([
                        'Retail' => 'Retail',
                        'Restaurant' => 'Restaurant',
                        'Wholesale' => 'Wholesale',
                        'Supermarket' => 'Supermarket',
                    ]),

            ])
            ->actions([

                ViewAction::make(),

                EditAction::make(),

                Action::make('branches')
                    ->icon('heroicon-o-building-storefront')
                    ->color('success'),

                //Action::make('devices')
                //    ->icon('heroicon-o-computer-desktop')
                //    ->color('warning'),

                Action::make('users')
                    ->icon('heroicon-o-users')
                    ->color('info'),

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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}

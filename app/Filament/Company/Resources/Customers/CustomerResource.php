<?php

namespace App\Filament\Company\Resources\Customers;

use App\Filament\Company\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Company\Resources\Customers\Pages\EditCustomer;
use App\Filament\Company\Resources\Customers\Pages\ListCustomers;
use App\Filament\Company\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Company\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static UnitEnum|string|null $navigationGroup = 'Customer Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
            return $schema
                ->schema([

                    Section::make('Customer Information')
                        ->columns(3)
                        ->schema([

                            Select::make('customer_type')
                                ->options([
                                    'walk_in' => 'Walk In',
                                    'individual' => 'Individual',
                                    'business' => 'Business',
                                ])
                                ->required()
                                ->live(),

                            TextInput::make('customer_code')
                                ->required()
                                ->maxLength(50)
                                ->unique(ignoreRecord: true),

                            Toggle::make('is_active')
                                ->default(true),

                            TextInput::make('first_name')
                                ->visible(fn (Get $get) => $get('customer_type') !== 'business')
                                ->maxLength(100),

                            TextInput::make('last_name')
                                ->visible(fn (Get $get) => $get('customer_type') !== 'business')
                                ->maxLength(100),

                            TextInput::make('business_name')
                                ->visible(fn (Get $get) => $get('customer_type') === 'business')
                                ->required(fn (Get $get) => $get('customer_type') === 'business')
                                ->maxLength(255),

                            TextInput::make('tin_number')
                                ->label('TIN Number'),

                        ]),

                    Section::make('Contact Information')
                        ->columns(3)
                        ->schema([

                            TextInput::make('phone')
                                ->tel(),

                            TextInput::make('alternative_phone')
                                ->tel(),

                            TextInput::make('email')
                                ->email(),

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

                    Section::make('Credit')
                        ->columns(3)
                        ->schema([

                            TextInput::make('credit_limit')
                                ->numeric()
                                ->prefix('M')
                                ->default(0),

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

                        ])

                ]);
        }

        public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('customer_code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Customer')
                    ->searchable([
                        'first_name',
                        'last_name',
                        'business_name',
                    ])
                    ->formatStateUsing(function (Customer $record) {

                        return $record->customer_type === 'business'
                            ? $record->business_name
                            : trim($record->first_name . ' ' . $record->last_name);

                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('credit_limit')
                    ->money('LSL'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->money('LSL'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date(),

            ])

            ->filters([

                Tables\Filters\SelectFilter::make('customer_type')
                    ->options([
                        'walk_in' => 'Walk In',
                        'individual' => 'Individual',
                        'business' => 'Business',
                    ]),

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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

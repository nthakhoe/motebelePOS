<?php

namespace App\Filament\Admin\Resources\Branches;

use App\Filament\Admin\Resources\Branches\Pages\CreateBranch;
use App\Filament\Admin\Resources\Branches\Pages\EditBranch;
use App\Filament\Admin\Resources\Branches\Pages\ListBranches;
use App\Filament\Admin\Resources\Branches\Schemas\BranchForm;
use App\Filament\Admin\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
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

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Branch';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Branch Information')
                    ->icon('heroicon-o-building-storefront')
                    ->columns(2)
                    ->schema([

                        Select::make('company_id')
                            ->relationship('company', 'company_name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('branch_name')
                            ->label('Branch Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('branch_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique branch code e.g. MST001'),

                        Toggle::make('active')
                            ->default(true),

                    ]),

                Section::make('Contact Information')
                    ->icon('heroicon-o-phone')
                    ->columns(2)
                    ->schema([

                        TextInput::make('phone')
                            ->tel(),

                        TextInput::make('email')
                            ->email(),

                        TextInput::make('manager'),

                        TextInput::make('manager_phone')
                            ->tel(),

                    ]),

                Section::make('Location')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([

                        Textarea::make('address')
                            ->columnSpanFull(),

                        TextInput::make('city'),

                        TextInput::make('district'),

                        TextInput::make('country')
                            ->default('Lesotho'),

                    ]),

                Section::make('Business Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->columns(2)
                    ->schema([

                        TextInput::make('currency')
                            ->default('LSL'),

                        Select::make('timezone')
                            ->options([
                                'Africa/Maseru' => 'Africa/Maseru',
                            ])
                            ->default('Africa/Maseru'),

                        Toggle::make('allow_returns')
                            ->default(true),

                        Toggle::make('inventory_enabled')
                            ->default(true),

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('company.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch_code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('manager')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('city')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('devices_count')
                    ->counts('devices')
                    ->label('Devices')
                    ->badge()
                    ->color('warning'),

                //Tables\Columns\TextColumn::make('registers_count')
                //    ->counts('registers')
                //    ->label('Registers')
                //    ->badge()
                //    ->color('success'),

                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Employees')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable(),

            ])
            ->defaultSort('branch_name');
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
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}

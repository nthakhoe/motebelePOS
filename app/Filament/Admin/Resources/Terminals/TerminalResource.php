<?php

namespace App\Filament\Admin\Resources\Terminals;

use App\Filament\Admin\Resources\Terminals\Pages\CreateTerminal;
use App\Filament\Admin\Resources\Terminals\Pages\EditTerminal;
use App\Filament\Admin\Resources\Terminals\Pages\ListTerminals;
use App\Filament\Admin\Resources\Terminals\Schemas\TerminalForm;
use App\Filament\Admin\Resources\Terminals\Tables\TerminalsTable;
use App\Models\Terminal;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
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

class TerminalResource extends Resource
{
    protected static ?string $model = Terminal::class;

    protected static UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Terminal Information')
                    ->icon('heroicon-o-computer-desktop')
                    ->columns(2)
                    ->schema([

                        Select::make('company_id')
                            ->relationship('company', 'company_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('branch_id')
                            ->relationship(
                                'branch',
                                'branch_name',
                                fn ($query, callable $get) =>
                                    $query->where('company_id', $get('company_id'))
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('terminal_name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Main Counter'),

                        TextInput::make('terminal_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Example: TM001'),

                        Toggle::make('default_terminal')
                            ->label('Default Terminal')
                            ->default(false),

                        Toggle::make('is_active')
                            ->default(true),

                    ]),

                Section::make('Hardware Information')
                    ->icon('heroicon-o-cpu-chip')
                    ->columns(2)
                    ->schema([

                        TextInput::make('serial_number'),

                        TextInput::make('computer_name')
                            ->placeholder('POS-COUNTER-01'),

                        TextInput::make('ip_address')
                            ->ip(),

                        TextInput::make('mac_address')
                            ->placeholder('00:1A:2B:3C:4D:5E'),

                    ]),

                Section::make('Status')
                    ->icon('heroicon-o-signal')
                    ->columns(2)
                    ->schema([

                        Select::make('status')
                            ->options([
                                'Available' => 'Available',
                                'Busy' => 'Busy',
                                'Offline' => 'Offline',
                                'Maintenance' => 'Maintenance',
                                'Disabled' => 'Disabled',
                            ])
                            ->default('Available')
                            ->required(),

                        Textarea::make('description')
                            ->columnSpanFull(),

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('company.company_name')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('branch.branch_name')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('terminal_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('terminal_code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('computer_name')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('serial_number')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Available',
                        'warning' => 'Busy',
                        'danger' => 'Offline',
                        'info' => 'Maintenance',
                        'gray' => 'Disabled',
                    ]),

                Tables\Columns\IconColumn::make('default_terminal')
                    ->label('Default')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable(),

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
                        'Available' => 'Available',
                        'Busy' => 'Busy',
                        'Offline' => 'Offline',
                        'Maintenance' => 'Maintenance',
                        'Disabled' => 'Disabled',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active'),

            ])
            ->actions([

                ViewAction::make(),

                EditAction::make(),

                Action::make('Register Device')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('warning'),

                Action::make('Open Session')
                    ->icon('heroicon-o-play')
                    ->color('success'),

                Action::make('Close Session')
                    ->icon('heroicon-o-stop')
                    ->color('danger'),

            ])
            ->defaultSort('terminal_name');
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
            'index' => ListTerminals::route('/'),
            'create' => CreateTerminal::route('/create'),
            'edit' => EditTerminal::route('/{record}/edit'),
        ];
    }
}

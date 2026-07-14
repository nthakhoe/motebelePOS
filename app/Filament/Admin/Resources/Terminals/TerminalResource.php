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

class TerminalResource extends Resource
{
    protected static ?string $model = Terminal::class;

    protected static UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TerminalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TerminalsTable::configure($table);
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

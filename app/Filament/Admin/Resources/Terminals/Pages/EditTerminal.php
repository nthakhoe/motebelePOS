<?php

namespace App\Filament\Admin\Resources\Terminals\Pages;

use App\Filament\Admin\Resources\Terminals\TerminalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTerminal extends EditRecord
{
    protected static string $resource = TerminalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

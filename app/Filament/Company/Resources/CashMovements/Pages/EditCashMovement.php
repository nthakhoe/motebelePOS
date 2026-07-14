<?php

namespace App\Filament\Company\Resources\CashMovements\Pages;

use App\Filament\Company\Resources\CashMovements\CashMovementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashMovement extends EditRecord
{
    protected static string $resource = CashMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

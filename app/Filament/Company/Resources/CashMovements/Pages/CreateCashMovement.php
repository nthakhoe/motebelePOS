<?php

namespace App\Filament\Company\Resources\CashMovements\Pages;

use App\Filament\Company\Resources\CashMovements\CashMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashMovement extends CreateRecord
{
    protected static string $resource = CashMovementResource::class;
}

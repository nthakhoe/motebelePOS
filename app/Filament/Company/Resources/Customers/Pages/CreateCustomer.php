<?php

namespace App\Filament\Company\Resources\Customers\Pages;

use App\Filament\Company\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}

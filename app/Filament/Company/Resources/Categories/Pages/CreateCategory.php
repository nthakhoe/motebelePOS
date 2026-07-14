<?php

namespace App\Filament\Company\Resources\Categories\Pages;

use App\Filament\Company\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}

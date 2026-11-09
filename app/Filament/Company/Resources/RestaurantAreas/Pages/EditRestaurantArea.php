<?php

namespace App\Filament\Company\Resources\RestaurantAreas\Pages;

use App\Filament\Company\Resources\RestaurantAreas\RestaurantAreaResource;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantArea extends EditRecord
{
    protected static string $resource = RestaurantAreaResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        $data['company_id'] = $user->company_id;

        if ($user->branch_id) {
            $data['branch_id'] = $user->branch_id;
        }

        return $data;
    }
}
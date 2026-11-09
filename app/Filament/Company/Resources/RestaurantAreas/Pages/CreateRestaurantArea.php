<?php

namespace App\Filament\Company\Resources\RestaurantAreas\Pages;

use App\Filament\Company\Resources\RestaurantAreas\RestaurantAreaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateRestaurantArea extends CreateRecord
{
    protected static string $resource = RestaurantAreaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['company_id'] = $user->company_id;

        if ($user->branch_id) {
            $data['branch_id'] = $user->branch_id;
        }

        return $data;
    }
}
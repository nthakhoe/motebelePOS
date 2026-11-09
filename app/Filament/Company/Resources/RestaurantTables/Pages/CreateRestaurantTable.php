<?php

namespace App\Filament\Company\Resources\RestaurantTables\Pages;

use App\Filament\Company\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateRestaurantTable extends CreateRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['company_id'] = $user->company_id;

        if ($user->branch_id) {
            $data['branch_id'] = $user->branch_id;
        }

        $area = \App\Models\RestaurantArea::query()
            ->where('id', $data['area_id'])
            ->where('company_id', $user->company_id)
            ->where('branch_id', $data['branch_id'])
            ->where('is_active', true)
            ->first();

        if (!$area) {
            throw ValidationException::withMessages([
                'data.area_id' => 'The selected dining area is invalid.',
            ]);
        }

        $data['status'] = 'available';

        return $data;
    }
}
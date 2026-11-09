<?php

namespace App\Filament\Company\Resources\RestaurantTables\Pages;

use App\Filament\Company\Resources\RestaurantTables\RestaurantTableResource;
use App\Models\RestaurantArea;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantTable extends EditRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        $area = RestaurantArea::query()
            ->where('id', $data['area_id'])
            ->where('company_id', $record->company_id)
            ->where('branch_id', $record->branch_id)
            ->where('is_active', true)
            ->first();

        if (!$area) {
            throw ValidationException::withMessages([
                'data.area_id' => 'The selected dining area is invalid.',
            ]);
        }

        $data['company_id'] = $record->company_id;
        $data['branch_id'] = $record->branch_id;

        return $data;
    }
}
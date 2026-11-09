<?php

namespace App\Filament\Company\Resources\RestaurantAreas\Pages;

use App\Filament\Company\Resources\RestaurantAreas\RestaurantAreaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantAreas extends ListRecords
{
    protected static string $resource = RestaurantAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Dining Area')
                ->icon('heroicon-o-plus'),
        ];
    }
}
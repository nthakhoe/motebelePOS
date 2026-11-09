<?php

namespace App\Filament\Company\Resources\RestaurantTables\Pages;

use App\Filament\Company\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantTables extends ListRecords
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Table')
                ->icon('heroicon-o-plus'),
        ];
    }
}
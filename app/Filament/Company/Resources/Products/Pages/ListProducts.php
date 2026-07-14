<?php

namespace App\Filament\Company\Resources\Products\Pages;

use App\Filament\Company\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Company\Widgets\ProductStats::class,
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        $data['created_by'] = auth()->id();

        $data['updated_by'] = auth()->id();

        if (empty($data['sku'])) {

            $data['sku'] = ProductService::generateSku();

        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

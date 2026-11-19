<?php

namespace App\Filament\Company\Pages;

use App\Models\Branch;
use App\Models\RestaurantArea;
use App\Models\RestaurantTable;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RestaurantFloorPlan extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Management';

    protected static ?string $navigationLabel = 'Floor Plan';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.company.pages.restaurant-floor-plan';

    public ?int $selectedBranchId = null;

    protected static ?string $title = null;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
            return;
        }

        $this->selectedBranchId = Branch::query()
            ->where('company_id', $user->company_id)
            ->orderBy('branch_name')
            ->value('id');
    }

    public function updatedSelectedBranchId(): void
    {
        $this->validateSelectedBranch();
    }

    protected function validateSelectedBranch(): void
    {
        $user = Auth::user();

        $valid = Branch::query()
            ->where('company_id', $user->company_id)
            ->when(
                $user->branch_id,
                fn ($query) => $query->where('id', $user->branch_id)
            )
            ->where('id', $this->selectedBranchId)
            ->exists();

        if (!$valid) {
            $this->selectedBranchId = $user->branch_id;
        }
    }

    /*public function getTitle(): string
    {
        return 'Restaurant Floor Plan';
    }

    public function getSubheading(): ?string
    {
        return 'Monitor restaurant tables and their availability.';
    }*/

    protected function getViewData(): array
    {
        $this->validateSelectedBranch();

        $user = Auth::user();

        $areas = RestaurantArea::query()
            ->where('company_id', $user->company_id)
            ->where('branch_id', $this->selectedBranchId)
            ->where('is_active', true)
            ->with([
                'tables' => function ($query) use ($user) {
                    $query
                        ->where('company_id', $user->company_id)
                        ->where('is_active', true)
                        ->with([
                            'activeOrder' => fn ($orderQuery) =>
                                $orderQuery->latest()->with('waiter'),
                        ])
                        ->orderBy('table_number');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tables = $areas->flatMap(fn ($area) => $area->tables);

        return [
            'areas' => $areas,
            'branches' => Branch::query()
                ->where('company_id', $user->company_id)
                ->when(
                    $user->branch_id,
                    fn ($query) => $query->where('id', $user->branch_id)
                )
                ->orderBy('branch_name')
                ->pluck('branch_name', 'id'),

            'statistics' => [
                'total' => $tables->count(),

                'available' => $tables->where(
                    'status',
                    RestaurantTable::STATUS_AVAILABLE
                )->count(),

                'occupied' => $tables->where(
                    'status',
                    RestaurantTable::STATUS_OCCUPIED
                )->count(),

                'reserved' => $tables->where(
                    'status',
                    RestaurantTable::STATUS_RESERVED
                )->count(),

                'cleaning' => $tables->where(
                    'status',
                    RestaurantTable::STATUS_CLEANING
                )->count(),
            ],
        ];
    }
}
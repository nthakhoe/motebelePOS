<?php

namespace App\Filament\Waiter\Pages;

use App\Models\Branch;
use App\Models\RestaurantOrder;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActiveOrders extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Management';

    protected static ?string $navigationLabel = 'Active Orders';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.waiter.pages.active-orders';

    public ?int $selectedBranchId = null;

    public string $statusFilter = 'all';

    public string $search = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->selectedBranchId = $user->branch_id
            ?: Branch::query()
                ->where('company_id', $user->company_id)
                ->value('id');
    }

    protected function baseQuery(): Builder
    {
        $user = Auth::user();

        return RestaurantOrder::query()
            ->where('company_id', $user->company_id)
            ->where('branch_id', $this->selectedBranchId)
            ->whereIn('status', [
                RestaurantOrder::STATUS_OPEN,
                RestaurantOrder::STATUS_SUBMITTED,
                RestaurantOrder::STATUS_PREPARING,
                RestaurantOrder::STATUS_READY,
                RestaurantOrder::STATUS_SERVED,
                RestaurantOrder::STATUS_BILL_REQUESTED,
            ]);
    }

    public function setStatus(string $status): void
    {
        $allowed = [
            'all',
            RestaurantOrder::STATUS_OPEN,
            RestaurantOrder::STATUS_SUBMITTED,
            RestaurantOrder::STATUS_PREPARING,
            RestaurantOrder::STATUS_READY,
            RestaurantOrder::STATUS_SERVED,
            RestaurantOrder::STATUS_BILL_REQUESTED,
        ];

        if (in_array($status, $allowed, true)) {
            $this->statusFilter = $status;
        }
    }

    public function openOrder(int $orderId)
    {
        $order = $this->baseQuery()
            ->whereKey($orderId)
            ->firstOrFail();

        if ($order->status === RestaurantOrder::STATUS_BILL_REQUESTED) {
            \Filament\Notifications\Notification::make()
                ->title('Bill already requested')
                ->body('This order cannot receive additional items.')
                ->warning()
                ->send();

            return;
        }

        return redirect(
            WaiterOrdering::getUrl([
                'order' => $order->id,
            ])
        );
    }

    protected function getViewData(): array
    {
        $query = $this->baseQuery()
            ->with([
                'table.area',
                'waiter',
                'items',
            ]);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);

            $query->where(function (Builder $q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('table', function (Builder $tableQuery) use ($search) {
                        $tableQuery->where('table_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('waiter', function (Builder $waiterQuery) use ($search) {
                        $waiterQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query
            ->latest('opened_at')
            ->get();

        $counts = $this->baseQuery()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $branches = Branch::query()
            ->where('company_id', Auth::user()->company_id)
            ->when(
                Auth::user()->branch_id,
                fn ($q) => $q->where('id', Auth::user()->branch_id)
            )
            ->orderBy('branch_name')
            ->pluck('branch_name', 'id');

        return [
            'orders' => $orders,
            'counts' => $counts,
            'branches' => $branches,
            'totalOrders' => $this->baseQuery()->count(),
            'openOrders' => $this->baseQuery()
                ->where('status', RestaurantOrder::STATUS_OPEN)
                ->count(),
            'submittedOrders' => $this->baseQuery()
                ->where('status', RestaurantOrder::STATUS_SUBMITTED)
                ->count(),
            'preparingOrders' => $this->baseQuery()
                ->where('status', RestaurantOrder::STATUS_PREPARING)
                ->count(),
            'readyOrders' => $this->baseQuery()
                ->where('status', RestaurantOrder::STATUS_READY)
                ->count(),
            'billRequestedOrders' => $this->baseQuery()
                ->where('status', RestaurantOrder::STATUS_BILL_REQUESTED)
                ->count(),
        ];
    }
}
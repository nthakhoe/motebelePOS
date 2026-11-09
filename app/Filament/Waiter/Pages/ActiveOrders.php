<?php

namespace App\Filament\Waiter\Pages;

use App\Models\Branch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatusLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActiveOrders extends Page
{
    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup =
        'Restaurant Management';

    protected static ?string $navigationLabel = 'Active Orders';

    protected static ?int $navigationSort = 5;

    protected string $view =
        'filament.waiter.pages.active-orders';

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

    protected function validateBranch(): void
    {
        $user = Auth::user();

        $valid = Branch::query()
            ->where('company_id', $user->company_id)
            ->when(
                $user->branch_id,
                fn ($query) => $query->where(
                    'id',
                    $user->branch_id
                )
            )
            ->whereKey($this->selectedBranchId)
            ->exists();

        if (!$valid) {
            throw ValidationException::withMessages([
                'selectedBranchId' => 'Invalid branch selected.',
            ]);
        }
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
            Notification::make()
                ->title('Bill already requested')
                ->body('Cancel the bill request before adding more items.')
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

    /**
     * Request a preliminary bill.
     */
    public function requestBill(int $orderId): void
    {
        $this->validateBranch();

        $user = Auth::user();

        DB::transaction(function () use ($orderId, $user) {

            $order = RestaurantOrder::query()
                ->where('id', $orderId)
                ->where('company_id', $user->company_id)
                ->where('branch_id', $this->selectedBranchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== RestaurantOrder::STATUS_SERVED) {
                throw ValidationException::withMessages([
                    'order' => 'Only served orders can have a bill requested.',
                ]);
            }

            $oldStatus = $order->status;

            $order->update([
                'status' => RestaurantOrder::STATUS_BILL_REQUESTED,
                'bill_requested_at' => now(),
            ]);

            RestaurantOrderStatusLog::create([
                'restaurant_order_id' => $order->id,
                'changed_by' => $user->id,
                'from_status' => $oldStatus,
                'to_status' => RestaurantOrder::STATUS_BILL_REQUESTED,
                'notes' => 'Waiter requested a preliminary bill.',
            ]);
        });

        Notification::make()
            ->title('Bill requested')
            ->body('You can now print the preliminary bill.')
            ->success()
            ->send();
    }

    /**
     * Cancel a bill request and allow additional items.
     */
    public function cancelBillRequest(int $orderId): void
    {
        $this->validateBranch();

        $user = Auth::user();

        DB::transaction(function () use ($orderId, $user) {

            $order = RestaurantOrder::query()
                ->where('id', $orderId)
                ->where('company_id', $user->company_id)
                ->where('branch_id', $this->selectedBranchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== RestaurantOrder::STATUS_BILL_REQUESTED) {
                throw ValidationException::withMessages([
                    'order' => 'This order does not have a pending bill request.',
                ]);
            }

            $oldStatus = $order->status;

            $order->update([
                'status' => RestaurantOrder::STATUS_SERVED,
                'bill_requested_at' => null,
            ]);

            RestaurantOrderStatusLog::create([
                'restaurant_order_id' => $order->id,
                'changed_by' => $user->id,
                'from_status' => $oldStatus,
                'to_status' => RestaurantOrder::STATUS_SERVED,
                'notes' => 'Waiter cancelled the bill request. Order reopened for additional items.',
            ]);
        });

        Notification::make()
            ->title('Bill request cancelled')
            ->body('The order can now receive additional items.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $this->validateBranch();

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
                        $tableQuery->where(
                            'table_number',
                            'like',
                            "%{$search}%"
                        );
                    })

                    ->orWhereHas('waiter', function (Builder $waiterQuery) use ($search) {
                        $waiterQuery->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
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

        return [
            'orders' => $orders,
            'counts' => $counts,

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

            'servedOrders' => $this->baseQuery()
                ->where('status', RestaurantOrder::STATUS_SERVED)
                ->count(),

            'billRequestedOrders' => $this->baseQuery()
                ->where('status', RestaurantOrder::STATUS_BILL_REQUESTED)
                ->count(),
        ];
    }
}
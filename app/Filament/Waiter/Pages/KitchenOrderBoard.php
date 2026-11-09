<?php

namespace App\Filament\Waiter\Pages;

use App\Models\Branch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatusLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenOrderBoard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-fire';

    protected static string|\UnitEnum|null $navigationGroup =
        'Restaurant Management';

    protected static ?string $navigationLabel = 'Kitchen Order Board';

    protected static ?int $navigationSort = 6;

    protected string $view =
        'filament.waiter.pages.kitchen-order-board';

    public ?int $selectedBranchId = null;

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

    protected function getOrders()
    {
        $user = Auth::user();

        return RestaurantOrder::query()
            ->where('company_id', $user->company_id)
            ->where('branch_id', $this->selectedBranchId)
            ->whereIn('status', [
                RestaurantOrder::STATUS_SUBMITTED,
                RestaurantOrder::STATUS_PREPARING,
                RestaurantOrder::STATUS_READY,
            ])
            ->with([
                'table.area',
                'waiter',
                'items',
            ])
            ->orderBy('opened_at')
            ->get();
    }

    public function startPreparing(int $orderId): void
    {
        $this->changeOrderStatus(
            $orderId,
            RestaurantOrder::STATUS_SUBMITTED,
            RestaurantOrder::STATUS_PREPARING,
            'Kitchen started preparing the order.'
        );
    }

    public function markReady(int $orderId): void
    {
        $this->changeOrderStatus(
            $orderId,
            RestaurantOrder::STATUS_PREPARING,
            RestaurantOrder::STATUS_READY,
            'Kitchen marked the order as ready.'
        );
    }

    protected function changeOrderStatus(
        int $orderId,
        string $expectedStatus,
        string $newStatus,
        string $notes
    ): void {

        $this->validateBranch();

        $user = Auth::user();

        DB::transaction(function () use (
            $orderId,
            $expectedStatus,
            $newStatus,
            $notes,
            $user
        ) {

            $order = RestaurantOrder::query()
                ->where('id', $orderId)
                ->where('company_id', $user->company_id)
                ->where('branch_id', $this->selectedBranchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== $expectedStatus) {
                throw ValidationException::withMessages([
                    'order' => 'This order has already been updated. Refresh the board and try again.',
                ]);
            }

            $oldStatus = $order->status;

            $order->update([
                'status' => $newStatus,
            ]);

            RestaurantOrderStatusLog::create([
                'restaurant_order_id' => $order->id,
                'changed_by' => $user->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'notes' => $notes,
            ]);
        });

        Notification::make()
            ->title('Order updated')
            ->body('The order status has been updated successfully.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $this->validateBranch();

        $orders = $this->getOrders();

        return [
            'newOrders' => $orders->where(
                'status',
                RestaurantOrder::STATUS_SUBMITTED
            ),

            'preparingOrders' => $orders->where(
                'status',
                RestaurantOrder::STATUS_PREPARING
            ),

            'readyOrders' => $orders->where(
                'status',
                RestaurantOrder::STATUS_READY
            ),

            'totalOrders' => $orders->count(),
        ];
    }
}
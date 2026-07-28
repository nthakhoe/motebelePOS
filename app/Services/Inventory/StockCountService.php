<?php

namespace App\Services\Inventory;

use App\Models\ProductStock;
use App\Models\StockCount;
use App\Models\StockCountItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class StockCountService
{
    /**
     * Start a stock count by creating a snapshot of current inventory.
     */
    public function start(StockCount $stockCount): void
    {
        DB::transaction(function () use ($stockCount) {

            if ($stockCount->items()->exists()) {
                throw new \Exception('This stock count has already been started.');
            }

            $stocks = ProductStock::query()
                ->where('company_id', $stockCount->company_id)
                ->where('branch_id', $stockCount->branch_id)
                ->with('product')
                ->get();

            foreach ($stocks as $stock) {

                StockCountItem::create([
                    'stock_count_id'      => $stockCount->id,
                    'product_id'          => $stock->product_id,
                    'system_quantity'     => $stock->quantity_on_hand,
                    'counted_quantity'    => null,
                    'variance_quantity'   => 0,
                    'adjustment_quantity' => 0,
                    'unit_cost'           => $stock->cost_price,
                    'variance_value'      => 0,
                    'status'              => 'Pending',
                ]);
            }

            $stockCount->update([
                'status'         => 'In Progress',
                'started_by'     => Auth::id(),
                'started_at'     => now(),
                'total_items'    => $stocks->count(),
                'counted_items'  => 0,
                'variance_items' => 0,
            ]);
        });
    }

    /**
     * Complete the stock count.
     */
    public function complete(StockCount $stockCount): void
    {
        DB::transaction(function () use ($stockCount) {

            // Reload relationships
            $stockCount->load('items');

            if ($stockCount->status !== 'In Progress') {
                throw new Exception('Only stock counts that are in progress can be completed.');
            }

            // Ensure every item has been counted
            $remaining = $stockCount->items()
                ->whereNull('counted_quantity')
                ->count();

            if ($remaining > 0) {
                throw new Exception(
                    "There are {$remaining} items that have not yet been counted."
                );
            }

            // Recalculate all variances
            foreach ($stockCount->items as $item) {

                $variance = $item->counted_quantity - $item->system_quantity;

                $item->update([
                    'variance_quantity'   => $variance,
                    'adjustment_quantity' => $variance,
                    'variance_value'      => $variance * $item->unit_cost,
                    'status'              => 'Counted',
                ]);
            }

            // Refresh totals
            $this->refreshProgress($stockCount);

            // Mark the count as completed
            $stockCount->update([
                'status'       => 'Completed',
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Approve the stock count.
     */
    public function approve(
        StockCount $stockCount,
        InventoryService $inventoryService
    ): void {

        DB::transaction(function () use ($stockCount, $inventoryService) {

            $stockCount->load('items');

            if ($stockCount->status !== 'Completed') {
                throw new Exception(
                    'Only completed stock counts can be approved.'
                );
            }

            foreach ($stockCount->items as $item) {

                if ($item->adjustment_quantity == 0) {
                    continue;
                }

                $inventoryService->adjustStock(

                    companyId: $stockCount->company_id,

                    branchId: $stockCount->branch_id,

                    product: $item->product,

                    difference: $item->adjustment_quantity,

                    userId: Auth::id(),

                    reference: $stockCount->reference,

                    remarks: $item->remarks,
                );

                $item->update([
                    'status' => 'Adjusted',
                ]);
            }

            $stockCount->update([
                'status'      => 'Approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });
    }

    public function refreshProgress(StockCount $stockCount): void
    {
        $stockCount->update([
            'counted_items' => $stockCount->items()
                ->whereNotNull('counted_quantity')
                ->count(),

            'variance_items' => $stockCount->items()
                ->where('variance_quantity', '!=', 0)
                ->count(),
        ]);
    }
}
<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\Product;
use Exception;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function issueStock(
        Product $product,
        int $branchId,
        float $quantity,
        string $referenceType,
        int $referenceId,
        int $userId
    ): ProductStock {

        return DB::transaction(function () use (
            $product,
            $branchId,
            $quantity,
            $referenceType,
            $referenceId,
            $userId
        ) {

            $stock = ProductStock::where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($stock->quantity_available < $quantity) {

                throw new Exception(
                    "Insufficient stock for {$product->product_name}"
                );

            }

            $quantityBefore = $stock->quantity_on_hand;

            $stock->quantity_on_hand -= $quantity;

            $stock->quantity_available =
                $stock->quantity_on_hand -
                $stock->quantity_reserved;

            $stock->last_sold_at = now();

            $stock->save();

            $this->createInventoryTransaction(
                stock: $stock,
                product: $product,
                quantityBefore: $quantityBefore,
                quantityIssued: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                userId: $userId
            );

            return $stock;
        });
    }

    public function issueStock(
            Product $product,
            int $branchId,
            float $quantity
        ): ProductStock {

            $stock = ProductStock::where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($stock->quantity_available < $quantity) {

                throw new Exception(
                    "{$product->product_name} has insufficient stock."
                );

            }

            $stock->quantity_on_hand -= $quantity;

            $stock->quantity_available =
                $stock->quantity_on_hand -
                $stock->quantity_reserved;

            $stock->last_sold_at = now();

            $stock->save();

            return $stock;
        }

    protected function createInventoryTransaction(
        ProductStock $stock,
        Product $product,
        float $quantityBefore,
        float $quantityIssued,
        string $referenceType,
        int $referenceId,
        int $userId
    ): void {

        InventoryTransaction::create([

            'company_id' => $stock->company_id,

            'branch_id' => $stock->branch_id,

            'product_id' => $product->id,

            'transaction_type' => 'Sale',

            'reference_type' => $referenceType,

            'reference_id' => $referenceId,

            'quantity_before' => $quantityBefore,

            'quantity' => -$quantityIssued,

            'quantity_after' => $stock->quantity_on_hand,

            'unit_cost' => $stock->average_cost,

            'total_cost' => $stock->average_cost * $quantityIssued,

            'remarks' => 'POS Sale',

            'user_id' => $userId,

            'transaction_date' => now(),

        ]);
    }
}

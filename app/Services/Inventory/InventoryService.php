<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
	public function receiveStock(
	    Product $product,
	    int $companyId,
	    int $branchId,
	    float $quantity,
	    float $unitCost,
	    string $reference,
	    ?string $remarks,
	    int $userId
	): InventoryTransaction
	{
	    return DB::transaction(function () use (
	        $product,
	        $companyId,
	        $branchId,
	        $quantity,
	        $unitCost,
	        $reference,
	        $remarks,
	        $userId
	    ) {

	        $stock = $this->updateProductStock(
	            product: $product,
	            companyId: $companyId,
	            branchId: $branchId,
	            quantity: $quantity,
	            movement: 'IN',
	            unitCost: $unitCost
	        );

	        return $this->createTransaction(
	            product: $product,
	            companyId: $companyId,
	            branchId: $branchId,
	            reference: $reference,
	            type: 'Purchase',
	            movement: 'IN',
	            quantity: $quantity,
	            unitCost: $unitCost,
	            balance: $stock->quantity_on_hand,
	            remarks: $remarks,
	            userId: $userId
	        );

	    });
	}

	public function issueStock(
	    Product $product,
	    int $companyId,
	    int $branchId,
	    float $quantity,
	    string $reference,
	    string $type,
	    ?string $remarks,
	    int $userId
	): InventoryTransaction
	{
	    return DB::transaction(function () use (
	        $product,
	        $companyId,
	        $branchId,
	        $quantity,
	        $reference,
	        $type,
	        $remarks,
	        $userId
	    ) {

	        $stock = ProductStock::where([
	            'company_id' => $companyId,
	            'branch_id' => $branchId,
	            'product_id' => $product->id,
	        ])->first();

	        if (! $stock) {
	            throw new InvalidArgumentException('No stock record found.');
	        }

	        if (
	            ! $product->allow_negative_stock &&
	            $stock->quantity_on_hand < $quantity
	        ) {
	            throw new InvalidArgumentException('Insufficient stock.');
	        }

	        $stock = $this->updateProductStock(
	            product: $product,
	            companyId: $companyId,
	            branchId: $branchId,
	            quantity: $quantity,
	            movement: 'OUT'
	        );

	        return $this->createTransaction(
	            product: $product,
	            companyId: $companyId,
	            branchId: $branchId,
	            reference: $reference,
	            type: $type,
	            movement: 'OUT',
	            quantity: $quantity,
	            unitCost: $stock->average_cost,
	            balance: $stock->quantity_on_hand,
	            remarks: $remarks,
	            userId: $userId
	        );

	    });
	}

	public function adjustStock(
	    Product $product,
	    int $companyId,
	    int $branchId,
	    float $difference,
	    string $reference,
	    ?string $remarks,
	    int $userId
	): InventoryTransaction
	{
	    return $difference >= 0
	        ? $this->receiveStock(
	            $product,
	            $companyId,
	            $branchId,
	            $difference,
	            0,
	            $reference,
	            $remarks,
	            $userId
	        )
	        : $this->issueStock(
	            $product,
	            $companyId,
	            $branchId,
	            abs($difference),
	            $reference,
	            'Adjustment',
	            $remarks,
	            $userId
	        );
	}

	private function updateProductStock(
	    Product $product,
	    int $companyId,
	    int $branchId,
	    float $quantity,
	    string $movement,
	    float $unitCost = 0
	): ProductStock
	{
	    $stock = ProductStock::firstOrCreate(

	        [
	            'company_id' => $companyId,
	            'branch_id' => $branchId,
	            'product_id' => $product->id,
	        ],

	        [
	            'quantity_on_hand' => 0,
	            'quantity_reserved' => 0,
	            'quantity_available' => 0,
	        ]

	    );

	    if ($movement === 'IN') {

	        $stock->quantity_on_hand += $quantity;
	        $stock->last_cost = $unitCost;
	        $stock->last_received_at = now();

	    } else {

	        $stock->quantity_on_hand -= $quantity;
	        $stock->last_sold_at = now();

	    }

	    $stock->quantity_available =
	        $stock->quantity_on_hand -
	        $stock->quantity_reserved;

	    $stock->save();

	    return $stock;
	}

	private function createTransaction(
	    Product $product,
	    int $companyId,
	    int $branchId,
	    string $reference,
	    string $type,
	    string $movement,
	    float $quantity,
	    float $unitCost,
	    float $balance,
	    ?string $remarks,
	    int $userId
	): InventoryTransaction
	{
	    return InventoryTransaction::create([

	        'company_id' => $companyId,

	        'branch_id' => $branchId,

	        'product_id' => $product->id,

	        'reference_no' => $reference,

	        'transaction_type' => $type,

	        'movement' => $movement,

	        'quantity' => $quantity,

	        'unit_cost' => $unitCost,

	        'balance_after' => $balance,

	        'remarks' => $remarks,

	        'created_by' => $userId,

	    ]);
	}

	public function getCurrentStock(
	    Product $product,
	    int $branchId
	): float
	{
	    return ProductStock::where([
	        'product_id' => $product->id,
	        'branch_id' => $branchId,
	    ])->value('quantity_on_hand') ?? 0;
	}
}
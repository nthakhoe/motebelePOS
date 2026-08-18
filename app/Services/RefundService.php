<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Inventory\InventoryService;

class RefundService
{
    public function process(
        Sale $sale,
        User $user,
        array $items,
        string $refundMethod,
        ?string $referenceNumber = null,
        ?string $reason = null,
        ?string $remarks = null
    ): Refund {

        return DB::transaction(function () use (
            $sale,
            $user,
            $items,
            $refundMethod,
            $referenceNumber,
            $reason,
            $remarks
        ) {

            /*
             * Lock the sale while processing the refund.
             */
            $sale = Sale::query()
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages([
                    'sale' => 'Only completed sales can be refunded.',
                ]);
            }

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'At least one item must be selected for refund.',
                ]);
            }

            $sale->load('items');

            $refundTotal = 0;

            $refundRows = [];

            foreach ($items as $itemData) {

                $saleItemId = (int) ($itemData['sale_item_id'] ?? 0);

                $refundQuantity = (float) (
                    $itemData['quantity'] ?? 0
                );

                if ($refundQuantity <= 0) {
                    continue;
                }

                $saleItem = $sale->items
                    ->firstWhere('id', $saleItemId);

                if (! $saleItem) {
                    throw ValidationException::withMessages([
                        'items' => "Sale item {$saleItemId} does not belong to this sale.",
                    ]);
                }

                /*
                 * Calculate how much of this particular sale item
                 * has already been refunded.
                 */
                $alreadyRefunded = (float) DB::table('refund_items')
                    ->join(
                        'refunds',
                        'refunds.id',
                        '=',
                        'refund_items.refund_id'
                    )
                    ->where(
                        'refund_items.sale_item_id',
                        $saleItem->id
                    )
                    ->where(
                        'refunds.status',
                        'completed'
                    )
                    ->sum('refund_items.quantity');

                $originalQuantity = (float) $saleItem->quantity;

                $remainingQuantity =
                    $originalQuantity - $alreadyRefunded;

                if ($refundQuantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "Cannot refund {$refundQuantity} units of item #{$saleItem->id}. " .
                            "Only {$remainingQuantity} remain refundable.",
                    ]);
                }

                $unitPrice = (float) $saleItem->unit_price;

                $refundAmount =
                    round(
                        $refundQuantity * $unitPrice,
                        2
                    );

                $refundTotal += $refundAmount;

                $product = $saleItem->product ?? null;

                $refundRows[] = [
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id ?? null,

                    'product_name' =>
                        $product?->product_name
                        ?? $saleItem->description
                        ?? 'Product',

                    'sku' =>
                        $product?->sku
                        ?? null,

                    'quantity' => $refundQuantity,

                    'unit_price' => $unitPrice,

                    'refund_amount' => $refundAmount,

                    'reason' => $reason,
                ];
            }

            if ($refundTotal <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'The refund amount must be greater than zero.',
                ]);
            }

            /*
             * Never allow the total refund to exceed the sale.
             */
            $alreadyRefundedTotal = (float) $sale->refunds()
                ->where('status', 'completed')
                ->sum('total_amount');

            if (
                $alreadyRefundedTotal + $refundTotal
                > (float) $sale->total
            ) {
                throw ValidationException::withMessages([
                    'items' =>
                        'The refund exceeds the remaining refundable amount.',
                ]);
            }

            /*
             * Generate refund reference.
             */
            $refundNumber =
                'REF-' .
                now()->format('YmdHis') .
                '-' .
                random_int(100, 999);

            /*
             * Create refund header.
             */
            $refund = Refund::create([
                'company_id' => $sale->company_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'register_id' => $sale->register_id,

                'refund_number' => $refundNumber,

                'total_amount' => $refundTotal,

                'refund_method' => $refundMethod,

                'reference_number' => $referenceNumber,

                'reason' => $reason,

                'remarks' => $remarks,

                'status' => 'completed',

                'refunded_at' => now(),
            ]);

            /*
             * Store every refunded item.
             */
            foreach ($refundRows as $row) {
                $refund->items()->create($row);
            }

                        /*
            |--------------------------------------------------------------------------
            | Stage 4: Record Payment
            |--------------------------------------------------------------------------
            */
            

            /*
             * Determine whether this was a full or partial refund.
             */
            $newRefundedTotal =
                $alreadyRefundedTotal + $refundTotal;

            if ($newRefundedTotal >= (float) $sale->total) {

                $sale->update([
                    'status' => 'refunded',
                ]);

            } else {

                $sale->update([
                    'status' => 'partially_refunded',
                ]);
            }

            app(RegisterSessionService::class)
                ->recordRefund(
                    sale: $sale,
                    refundAmount: $refund->total_amount,
                    userId: $user->id,
                );
            /*
            |--------------------------------------------------------------------------
            | Restore refunded stock
            |--------------------------------------------------------------------------
            |
            | A refund puts the returned quantity back into the branch stock.
            | InventoryService updates both:
            |
            |   1. product_stocks.quantity_on_hand
            |   2. inventory_transactions
            |
            | Because we are already inside DB::transaction(), if inventory
            | restoration fails, the refund itself is rolled back as well.
            |--------------------------------------------------------------------------
            */

            $inventoryService = app(InventoryService::class);

            foreach ($refundRows as $row) {

                if (! $row['product_id']) {
                    continue;
                }

                $saleItem = $sale->items
                    ->firstWhere('id', $row['sale_item_id']);

                if (! $saleItem) {
                    continue;
                }

                $product = $saleItem->product;

                if (! $product) {
                    continue;
                }

                $inventoryService->receiveStock(
                    product: $product,
                    companyId: $sale->company_id,
                    branchId: $sale->branch_id,
                    quantity: (float) $row['quantity'],
                    unitCost: (float) ($saleItem->cost_price ?? 0),
                    reference: $refund->refund_number,
                    remarks: 'Stock returned from sale refund ' .
                        $refund->refund_number,
                    userId: $user->id,
                );
            }

            return $refund->load('items');
        });
    }
}
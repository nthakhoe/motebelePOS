<?php

namespace App\Services\Procurement;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PurchaseOrderService
{
    /**
     * Create Purchase Order
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {

            if (empty($data['purchase_order_no'])) {
                $data['purchase_order_no'] = $this->generatePurchaseOrderNumber();
            }

            $data['status'] = 'Draft';

            $data['created_by'] = Auth::id();

            return PurchaseOrder::create($data);

        });
    }

    /**
     * Update Purchase Order
     */
    public function update(
        PurchaseOrder $purchaseOrder,
        array $data
    ): PurchaseOrder {

        if (! in_array($purchaseOrder->status, ['Draft'])) {
            throw new InvalidArgumentException(
                'Only draft purchase orders can be edited.'
            );
        }

        return DB::transaction(function () use ($purchaseOrder, $data) {

            $purchaseOrder->update($data);

            return $purchaseOrder->fresh();

        });
    }

    /**
     * Submit Purchase Order
     */
    public function submit(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== 'Draft') {

            throw new InvalidArgumentException(
                'Only draft purchase orders can be submitted.'
            );
        }

        if ($purchaseOrder->items()->count() == 0) {

            throw new InvalidArgumentException(
                'Purchase order has no items.'
            );
        }

        $this->refreshTotals($purchaseOrder);

        $purchaseOrder->update([
            'status' => 'Submitted',
        ]);
    }

    /**
     * Approve Purchase Order
     */
    public function approve(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== 'Submitted') {

            throw new InvalidArgumentException(
                'Only submitted purchase orders can be approved.'
            );
        }

        $purchaseOrder->update([

            'status' => 'Approved',

            'approved_by' => Auth::id(),

            'approved_at' => now(),

        ]);
    }

    /**
     * Cancel Purchase Order
     */
    public function cancel(
        PurchaseOrder $purchaseOrder,
        ?string $reason = null
    ): void {

        if (in_array($purchaseOrder->status, [
            'Received',
            'Closed',
        ])) {

            throw new InvalidArgumentException(
                'This purchase order cannot be cancelled.'
            );
        }

        $purchaseOrder->update([

            'status' => 'Cancelled',

            'remarks' => $reason,

        ]);
    }

    /**
     * Close Purchase Order
     */
    public function close(PurchaseOrder $purchaseOrder): void
    {
        if (! in_array($purchaseOrder->status, [
            'Received',
            'Partially Received',
        ])) {

            throw new InvalidArgumentException(
                'Purchase order cannot be closed.'
            );
        }

        $purchaseOrder->update([
            'status' => 'Closed',
        ]);
    }

    /**
     * Refresh Purchase Totals
     */
    public function refreshTotals(
        PurchaseOrder $purchaseOrder
    ): void {

        $purchaseOrder->load('items');

        $subtotal = $purchaseOrder->items->sum(function ($item) {

            return $item->ordered_quantity * $item->unit_cost;

        });

        $discount = $purchaseOrder->items->sum('discount');

        $tax = $purchaseOrder->items->sum('tax');

        $total = $subtotal - $discount + $tax;

        $purchaseOrder->update([

            'subtotal' => $subtotal,

            'discount' => $discount,

            'tax' => $tax,

            'total' => $total,

        ]);
    }

    /**
     * Generate Purchase Order Number
     */
    public function generatePurchaseOrderNumber(): string
    {
        $last = PurchaseOrder::latest('id')->first();

        if (! $last) {
            return 'PO000001';
        }

        $number = (int) Str::after(
            $last->purchase_order_no,
            'PO'
        );

        return 'PO' . str_pad(
            $number + 1,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
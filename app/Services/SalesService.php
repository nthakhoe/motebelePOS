<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Models\RegisterSession;
use App\Services\Lekuka\receiptService;

class SalesService
{

    public function __construct(
            protected InventoryService $inventoryService,

    protected PaymentService $paymentService,
    )
    {

    }

    
    protected function createSaleItems(
        Sale $sale,
        array $cart,
    ): void {

        $discountService = app(\App\Services\DiscountService::class);
        $taxService = app(\App\Services\TaxService::class);

        foreach ($cart as $item) {

            $product = Product::with('unit')->findOrFail($item['id']);

            $unitPrice = (float) $item['price'];
            $quantity = (float) $item['quantity'];
            $discount = (float) ($item['discount'] ?? 0);

            // Validate and calculate the item discount.
            $discountResult = $discountService->calculate(
                unitPrice: $unitPrice,
                quantity: $quantity,
                discount: $discount,
                costPrice: (float) $product->cost_price,
            );

            // Calculate tax using the discounted selling price.
            $taxResult = $taxService->calculateInclusive(
                price: $discountResult['effective_unit_price'],
                qty: $quantity,
                rate: (float) ($product->tax_rate ?? 0),
                additionalRate: (float) ($product->lekuka_additional_tax_rate ?? 0),
                additionalType: $product->lekuka_additional_tax_type,
                mainType: $product->lekuka_tax_type,
            );

            $costPrice = (float) $product->cost_price;

            // Profit excludes the tax component and accounts for discounts.
            $profit = round(
                $taxResult['subtotal'] - ($costPrice * $quantity),
                2
            );

            SaleItem::create([

                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'unit_id' => $product->unit_id,

                'quantity' => $quantity,
                'unit_price' => $unitPrice,

                // Actual monetary discount for this line.
                'discount' => $discountResult['discount'],

                'tax_rate' => $product->tax_rate ?? 0,

                // Existing Lekuka tax snapshots.
                'lekuka_tax_id' => $product->lekuka_tax_id,
                'lekuka_tax_type' => $product->lekuka_tax_type,
                'lekuka_tax_code' => $product->lekuka_tax_code,

                'lekuka_additional_tax_id' => $product->lekuka_additional_tax_id,
                'lekuka_additional_tax_type' => $product->lekuka_additional_tax_type,
                'lekuka_additional_tax_code' => $product->lekuka_additional_tax_code,
                'lekuka_additional_tax_rate' => $product->lekuka_additional_tax_rate,

                'tax_amount' => $taxResult['tax'],

                // Final payable amount for this item after discount.
                'line_total' => $taxResult['total'],

                'cost_price' => $costPrice,
                'profit' => $profit,
                'remarks' => null,

            ]);
        }
    }

    public function processSale(
        array $cart,
        int $customerId,
        int $paymentMethodId,
        float $amountReceived,
        User $cashier
    ): Sale {

        return DB::transaction(function () use (
            $cart,
            $customerId,
            $paymentMethodId,
            $amountReceived,
            $cashier
        ) {

            /*
            |--------------------------------------------------------------------------
            | Stage 1: Create Sale
            |--------------------------------------------------------------------------
            */

            $sale = $this->createSale(
                cart: $cart,
                customerId: $customerId,
                amountReceived: $amountReceived,
                cashier: $cashier,
            );

            /*
            |--------------------------------------------------------------------------
            | Stage 2: Create Sale Items
            |--------------------------------------------------------------------------
            */

            $this->createSaleItems(
                sale: $sale,
                cart: $cart,
            );

            /*
            |--------------------------------------------------------------------------
            | Stage 3: Update Inventory
            |--------------------------------------------------------------------------
            */

            $this->updateInventory(
                sale: $sale,
                cart: $cart,
                cashier: $cashier,
            );

            /*
            |--------------------------------------------------------------------------
            | Stage 4: Record Payment
            |--------------------------------------------------------------------------
            */

            $this->paymentService->recordPayment(

                sale: $sale,

                paymentMethodId: $paymentMethodId,

                amountReceived: $amountReceived,

                referenceNumber: $sale->reference_number ?? null,

                authorizationCode: $sale->provider ?? null,

                cashier: $cashier,

            );

            /*
            |--------------------------------------------------------------------------
            | Stage 5: Record Session
            |--------------------------------------------------------------------------
            */

            app(RegisterSessionService::class)->recordSale($sale);

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Sale With Relationships
            |--------------------------------------------------------------------------
            */

            return $sale->fresh([
                'customer',
                'items.product',
                'payments.paymentMethod',
            ]);

        });
    }

    public function getRegisteredDeviceForBranch(int $branchId): LekukaDevice
    {
        return LekukaDevice::query()
            ->where('branch_id', $branchId)
            ->where('registered', true)
            ->firstOrFail();
    }

    protected function createSale(
        array $cart,
        int $customerId,
        float $amountReceived,
        User $cashier,
    ): Sale {

    $subtotal = 0.00;
    $vat = 0.00;
    $discount = 0.00;
    $total = 0.00;

    $discountService = app(\App\Services\DiscountService::class);
    $taxService = app(\App\Services\TaxService::class);

    foreach ($cart as $item) {

        $product = Product::findOrFail($item['id']);

        $unitPrice = (float) $item['price'];
        $quantity = (float) $item['quantity'];
        $itemDiscount = (float) ($item['discount'] ?? 0);

        $discountResult = $discountService->calculate(
                unitPrice: (float) $item['price'],
                quantity: (float) $item['quantity'],
                discount: (float) ($item['discount'] ?? 0),
                costPrice: (float) $product->cost_price,
        );

        $taxResult = $taxService->calculateInclusive(
            price: $discountResult['effective_unit_price'],
            qty: $quantity,
            rate: (float) ($product->tax_rate ?? 0),
            additionalRate: (float) ($product->lekuka_additional_tax_rate ?? 0),
            additionalType: $product->lekuka_additional_tax_type,
            mainType: $product->lekuka_tax_type,
        );

        $subtotal += $taxResult['subtotal'];
        $vat += $taxResult['tax'];
        $discount += $discountResult['discount'];
        $total += $taxResult['total'];
    }

    $subtotal = round($subtotal, 2);
    $vat = round($vat, 2);
    $discount = round($discount, 2);
    $total = round($total, 2);

        $registerSession = RegisterSession::query()
            ->where('user_id', $cashier->id)
            ->where('company_id', $cashier->company_id)
            ->where('branch_id', $cashier->branch_id)
            ->where('status', 'Open')
            ->lockForUpdate()
            ->first();

        if (! $registerSession) {
            throw new \RuntimeException(
                'No open register session found for this cashier.'
            );
        }

        return Sale::create([

            'company_id' => $cashier->company_id,

            'branch_id' => $cashier->branch_id,

            'register_id' => $registerSession->id,

            'user_id' => $cashier->id,

            'customer_id' => $customerId,

            'sale_number' => $this->generateSaleNumber(),

            'subtotal' => $subtotal,

            'discount' => $discount,

            'tax' => $vat,

            'total' => $total,

            'amount_paid' => $amountReceived,

            'change' => round($amountReceived - $total, 2),

            'sale_type' => 'Cash',

            'status' => 'Completed',

            'submitted_to_lekuka' => false,

            'remarks' => null,

            'completed_at' => now(),

        ]);
    }

    protected function generateSaleNumber(): string
    {
        return 'SAL-'.now()->format('YmdHis');
    }

    protected function updateInventory(
        Sale $sale,
        array $cart,
        User $cashier
    ): void {

        foreach ($cart as $item) {

            $product = Product::findOrFail($item['id']);

            $this->inventoryService->issueStock(

                product: $product,

                branchId: $cashier->branch_id,

                quantity: $item['quantity'],

                referenceType: Sale::class,

                referenceId: $sale->id,

                userId: $cashier->id

            );

        }

    }
}
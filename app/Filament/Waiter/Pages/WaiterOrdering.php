<?php

namespace App\Filament\Waiter\Pages;

use App\Models\Product;
use App\Models\Branch;
use App\Models\RestaurantArea;
use App\Models\RestaurantTable;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantOrderStatusLog;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WaiterOrdering extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Management';

    protected static ?string $navigationLabel = 'Waiter Ordering';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.waiter.pages.waiter-ordering';

    public ?int $selectedBranchId = null;

    public ?int $selectedTableId = null;

    public ?int $selectedAreaId = null;

    public int $guestCount = 1;

    public ?int $selectedCategoryId = null;

    public ?int $editingOrderId = null;

    public bool $showActiveOrders = false;

    public string $search = '';

    public array $cart = [];

    public string $orderNotes = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->selectedBranchId = $user->branch_id
            ?: Branch::where('company_id', $user->company_id)
                ->value('id');

        $orderId = request()->query('order');

        if ($orderId) {
            $this->loadExistingOrder((int) $orderId);
        }
    }

    protected function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    protected function branchId(): int
    {
        return (int) $this->selectedBranchId;
    }

    protected function validateBranch(): void
    {
        $user = Auth::user();

        if (!$this->selectedBranchId) {
            throw ValidationException::withMessages([
                'selectedBranchId' => 'No branch is assigned to this user. Please contact your administrator.',
            ]);
        }

        $valid = Branch::query()
            ->where('company_id', $user->company_id)
            ->when(
                $user->branch_id,
                fn ($query) => $query->where('id', $user->branch_id)
            )
            ->whereKey($this->selectedBranchId)
            ->exists();

        if (!$valid) {
            throw ValidationException::withMessages([
                'selectedBranchId' => 'Invalid branch selected.',
            ]);
        }
    }

    public function selectArea(int $areaId): void
    {
        $this->validateBranch();

        $area = RestaurantArea::query()
            ->where('id', $areaId)
            ->where('company_id', $this->companyId())
            ->where('branch_id', $this->branchId())
            ->where('is_active', true)
            ->firstOrFail();

        $this->selectedAreaId = $area->id;
        $this->selectedTableId = null;
        $this->editingOrderId = null;
        $this->resetOrder();
    }

    public function selectTable(int $tableId): void
    {
        $this->validateBranch();

        $table = RestaurantTable::query()
            ->where('id', $tableId)
            ->where('company_id', $this->companyId())
            ->where('branch_id', $this->branchId())
            ->where('is_active', true)
            ->firstOrFail();

        if ($table->status === RestaurantTable::STATUS_AVAILABLE) {

            if ($table->activeOrder()->exists()) {
                throw ValidationException::withMessages([
                    'selectedTableId' => 'This table already has an active order.',
                ]);
            }

            $this->resetOrder();
            $this->editingOrderId = null;

            $this->selectedTableId = $table->id;
            $this->selectedAreaId = $table->area_id;
            $this->showActiveOrders = false;

            return;
        }

        if ($table->status === RestaurantTable::STATUS_OCCUPIED) {

            $order = $table->activeOrder()
                ->whereIn('status', [
                    RestaurantOrder::STATUS_OPEN,
                    RestaurantOrder::STATUS_SUBMITTED,
                    RestaurantOrder::STATUS_PREPARING,
                    RestaurantOrder::STATUS_READY,
                    RestaurantOrder::STATUS_SERVED,
                ])
                ->latest()
                ->first();

            if (!$order) {
                throw ValidationException::withMessages([
                    'selectedTableId' => 'No order can currently be reopened for this table.',
                ]);
            }

            $this->loadExistingOrder($order->id);

            return;
        }

        throw ValidationException::withMessages([
            'selectedTableId' => 'This table cannot currently receive a new order.',
        ]);
    }

    public function loadExistingOrder(int $orderId): void
    {
        $this->validateBranch();

        $user = Auth::user();

        $order = RestaurantOrder::query()
            ->where('id', $orderId)
            ->where('company_id', $user->company_id)
            ->where('branch_id', $this->selectedBranchId)
            ->whereIn('status', [
                RestaurantOrder::STATUS_OPEN,
                RestaurantOrder::STATUS_SUBMITTED,
                RestaurantOrder::STATUS_PREPARING,
                RestaurantOrder::STATUS_READY,
                RestaurantOrder::STATUS_SERVED,
            ])
            ->with(['items', 'table.area'])
            ->firstOrFail();

        $this->editingOrderId = $order->id;
        $this->selectedTableId = $order->table_id;
        $this->selectedAreaId = $order->table?->area_id;

        $this->guestCount = $order->guest_count;
        $this->orderNotes = $order->notes ?? '';

        // Existing items are displayed separately.
        // The cart contains only additional products.

        $this->cart = [];

        $this->showActiveOrders = false;
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function addProduct(int $productId): void
    {
        $this->validateBranch();

        $product = Product::query()
            ->where('id', $productId)
            ->where('company_id', $this->companyId())
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $key = (string) $product->id;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
        } else {
            $this->cart[$key] = [
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'unit_price' => (float) $product->selling_price,
                'tax_rate' => (float) $product->tax_rate,
                'quantity' => 1,
                'special_instructions' => '',
            ];
        }
    }

    public function updateQuantity(int $productId, int $change): void
    {
        $key = (string) $productId;

        if (!isset($this->cart[$key])) {
            return;
        }

        $quantity = (int) $this->cart[$key]['quantity'] + $change;

        if ($quantity <= 0) {
            unset($this->cart[$key]);
            return;
        }

        $this->cart[$key]['quantity'] = $quantity;
    }

    public function removeProduct(int $productId): void
    {
        unset($this->cart[(string) $productId]);
    }

    public function updateInstructions(int $productId, string $instructions): void
    {
        $key = (string) $productId;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['special_instructions'] = mb_substr(
                $instructions,
                0,
                2000
            );
        }
    }

    public function startNewOrder(): void
    {
        $this->resetOrder();

        $this->editingOrderId = null;
        $this->selectedTableId = null;
        $this->selectedAreaId = null;
        $this->showActiveOrders = false;
    }

    public function resetOrder(): void
    {
        $this->cart = [];
        $this->guestCount = 1;
        $this->orderNotes = '';
        $this->search = '';
        $this->selectedCategoryId = null;
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->cart)->sum(
            fn ($item) => $item['unit_price'] * $item['quantity']
        );
    }

    public function getProductsProperty()
    {
        $query = Product::query()
            ->where('company_id', $this->companyId())
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);

            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('product_name')
            ->limit(60)
            ->get();
    }

    public function submitOrder(): void
    {
        $this->validateBranch();

        if (!$this->selectedTableId) {
            throw ValidationException::withMessages([
                'selectedTableId' => 'Please select a table.',
            ]);
        }

        if (empty($this->cart)) {
            throw ValidationException::withMessages([
                'cart' => 'Please add at least one new product.',
            ]);
        }

        if ($this->guestCount < 1 || $this->guestCount > 100) {
            throw ValidationException::withMessages([
                'guestCount' => 'Enter a valid number of guests.',
            ]);
        }

        $user = Auth::user();

        $wasEditing = (bool) $this->editingOrderId;

        DB::transaction(function () use ($user, $wasEditing) {

            $table = RestaurantTable::query()
                ->where('id', $this->selectedTableId)
                ->where('company_id', $user->company_id)
                ->where('branch_id', $this->selectedBranchId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$table->is_active) {
                throw ValidationException::withMessages([
                    'selectedTableId' => 'This table is inactive.',
                ]);
            }

            if ($wasEditing) {

                $order = RestaurantOrder::query()
                    ->where('id', $this->editingOrderId)
                    ->where('company_id', $user->company_id)
                    ->where('branch_id', $table->branch_id)
                    ->where('table_id', $table->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $previousStatus = $order->status;

                if (!in_array($order->status, [
                    RestaurantOrder::STATUS_OPEN,
                    RestaurantOrder::STATUS_SUBMITTED,
                    RestaurantOrder::STATUS_PREPARING,
                    RestaurantOrder::STATUS_READY,
                    RestaurantOrder::STATUS_SERVED,
                ], true)) {
                    throw ValidationException::withMessages([
                        'cart' => 'This order can no longer receive additional items.',
                    ]);
                }

            } else {

                $previousStatus = null;

                if ($table->status !== RestaurantTable::STATUS_AVAILABLE) {
                    throw ValidationException::withMessages([
                        'selectedTableId' => 'This table is no longer available.',
                    ]);
                }

                if ($table->activeOrder()->exists()) {
                    throw ValidationException::withMessages([
                        'selectedTableId' => 'This table already has an active order.',
                    ]);
                }

                $order = RestaurantOrder::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $table->branch_id,
                    'table_id' => $table->id,
                    'waiter_id' => $user->id,
                    'order_number' => 'RST-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(4)),
                    'guest_count' => $this->guestCount,
                    'status' => RestaurantOrder::STATUS_SUBMITTED,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'notes' => $this->orderNotes ?: null,
                    'opened_at' => now(),
                ]);

                $table->update([
                    'status' => RestaurantTable::STATUS_OCCUPIED,
                ]);
            }

            // Update guest count and order notes.
            $order->update([
                'guest_count' => $this->guestCount,
                'notes' => $this->orderNotes ?: null,
            ]);

            foreach ($this->cart as $item) {

                $product = Product::query()
                    ->where('id', $item['product_id'])
                    ->where('company_id', $user->company_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->firstOrFail();

                $quantity = (float) $item['quantity'];

                if ($quantity <= 0 || $quantity > 9999) {
                    throw ValidationException::withMessages([
                        'cart' => 'Invalid product quantity.',
                    ]);
                }

                $unitPrice = (float) $product->selling_price;
                $taxRate = (float) $product->tax_rate;

                $lineSubtotal = round($unitPrice * $quantity, 2);

                // Assumes selling prices include tax.
                $lineTax = $taxRate > 0
                    ? round($lineSubtotal * $taxRate / (100 + $taxRate), 2)
                    : 0;

                RestaurantOrderItem::create([
                    'restaurant_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'subtotal' => $lineSubtotal,
                    'tax_amount' => $lineTax,
                    'total_amount' => $lineSubtotal,
                    'special_instructions' => $item['special_instructions'] ?? null,
                ]);
            }

            // Recalculate totals using ALL order items.
            $items = $order->items()->get();

            $subtotal = $items->sum('subtotal');
            $taxAmount = $items->sum('tax_amount');

            $order->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'total_amount' => round($subtotal - $order->discount_amount, 2),
            ]);

            RestaurantOrderStatusLog::create([
                'restaurant_order_id' => $order->id,
                'changed_by' => $user->id,
                'from_status' => $previousStatus,
                'to_status' => $order->status,
                'notes' => $wasEditing
                    ? 'Additional items added by waiter.'
                    : 'New restaurant order submitted.',
            ]);
        });

        $this->cart = [];
        $this->editingOrderId = null;
        $this->selectedTableId = null;
        $this->selectedAreaId = null;
        $this->guestCount = 1;
        $this->orderNotes = '';
        $this->search = '';

        \Filament\Notifications\Notification::make()
            ->title('Order submitted successfully')
            ->body('The restaurant order has been saved.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $this->validateBranch();

        $user = Auth::user();

        $areas = RestaurantArea::query()
            ->where('company_id', $user->company_id)
            ->where('branch_id', $this->selectedBranchId)
            ->where('is_active', true)
            ->with([
                'tables' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('table_number'),
            ])
            ->orderBy('sort_order')
            ->get();

        $categories = \App\Models\Category::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        $selectedTable = $this->selectedTableId
            ? RestaurantTable::query()
                ->where('company_id', $user->company_id)
                ->where('branch_id', $this->selectedBranchId)
                ->find($this->selectedTableId)
            : null;

        return [
            'areas' => $areas,
            'categories' => $categories,
            'products' => $this->products,
            'selectedTable' => $selectedTable,
            'branches' => Branch::query()
                ->where('company_id', $user->company_id)
                ->when(
                    $user->branch_id,
                    fn ($query) => $query->where('id', $user->branch_id)
                )
                ->orderBy('branch_name')
                ->pluck('branch_name', 'id'),

            'existingOrder' => $this->editingOrderId
                ? RestaurantOrder::query()
                    ->where('company_id', $user->company_id)
                    ->where('branch_id', $this->selectedBranchId)
                    ->with('items')
                    ->find($this->editingOrderId)
                : null,
        ];
    }
}
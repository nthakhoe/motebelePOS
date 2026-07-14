<?php

namespace App\Filament\Cashier\Pages;

use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;


class POS extends Page implements HasActions
{
    use InteractsWithActions;
    public $products = [];

    public $categories = [];

    public array $cart = [];

    public ?int $selectedCategory = null;

    public string $search = '';

    public ?int $customerId = null;

    public ?Customer $customer = null;

    public float $subtotal = 0;

    public float $discount = 0;

    public float $vat = 0;

    public float $total = 0;

    public string $barcode = '';

    public float $amountReceived = 0;

    public float $change = 0;

    public ?int $paymentMethodId = null;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Point of Sale';

    protected static UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.cashier.pages.p-o-s';

    protected ?string $heading = '';

    protected ?string $subheading = '';

    public function mount(): void
    {
        $this->loadCategories();

        $this->loadProducts();

        $this->cart = [];

        $this->calculateTotals();

        $walkIn = Customer::firstOrCreate(
            ['customer_code' => 'WALK-IN'],
            [
                'customer_name' => 'Walk-in Customer',
                'phone' => null,
            ]
        );

        $this->customer = $walkIn;
        $this->customerId = $walkIn->id;

    }

    protected function loadCategories(): void
    {
        $this->categories = Category::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get();
    }

    protected function loadProducts(): void
    {
        $query = Product::query()
            ->with([
                'category',
                'unit',
            ])
            ->where('is_active', true);

        /*
        |--------------------------------------------------------------------------
        | Category Filter
        |--------------------------------------------------------------------------
        */

        if ($this->selectedCategory) {

            $query->where('category_id', $this->selectedCategory);

        }

        /*
        |--------------------------------------------------------------------------
        | Product Search
        |--------------------------------------------------------------------------
        */

        if ($this->search != '') {

            $query->where(function ($q) {

                $q->where('product_name', 'like', "%{$this->search}%")
                  ->orWhere('barcode', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%");

            });

        }

        $this->products = $query
            ->orderBy('product_name')
            ->get();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::query()
            ->with('unit')
            ->find($productId);

        if (! $product) {
            return;
        }

        /*
         * Check if product already exists in the cart.
         */
        if (isset($this->cart[$productId])) {

            $this->cart[$productId]['quantity']++;

            $this->cart[$productId]['line_total'] =
                $this->cart[$productId]['quantity'] *
                $this->cart[$productId]['price'];

        } else {

            $this->cart[$productId] = [

                'id' => $product->id,

                'barcode' => $product->barcode,

                'name' => $product->product_name,

                'unit' => $product->unit?->short_name,

                'price' => (float) $product->selling_price,

                'quantity' => 1,

                'discount' => 0,

                'tax' => 15,

                'line_total' => (float) $product->selling_price,

            ];
        }

        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $this->subtotal = 0;
        $this->discount = 0;
        $this->vat = 0;

        $taxService = app(\App\Services\TaxService::class);

        foreach ($this->cart as $item) {

            $result = $taxService->calculateInclusive(
                $item['price'],
                $item['quantity'],
                $item['tax']
            );

            $this->subtotal += $item['line_total'];

            $this->discount += $item['discount'];

            $this->vat += $result['tax'];
        }

        $this->total =
            $this->subtotal
            - $this->discount
            + $this->vat;
    }

    public function increaseQty(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        // Later we'll validate available stock here.
        $this->cart[$productId]['quantity']++;

        $this->cart[$productId]['line_total'] =
            $this->cart[$productId]['price'] *
            $this->cart[$productId]['quantity'];

        $this->calculateTotals();
    }

    public function decreaseQty(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['quantity'] <= 1) {

            unset($this->cart[$productId]);

        } else {

            $this->cart[$productId]['quantity']--;

            $this->cart[$productId]['line_total'] =
                $this->cart[$productId]['price'] *
                $this->cart[$productId]['quantity'];
        }

        $this->calculateTotals();
    }
    
    public function removeItem(int $productId): void
    {
        unset($this->cart[$productId]);

        $this->calculateTotals();
    }

    public function clearCart(): void
    {
        $this->cart = [];

        $this->calculateTotals();
    }

    public function updatedSearch(): void
    {
        $this->loadProducts();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategory = $categoryId;

        $this->loadProducts();
    }

    public function scanBarcode(): void
    {
        if (blank($this->barcode)) {
            return;
        }

        $product = Product::query()
            ->where('barcode', trim($this->barcode))
            ->where('is_active', true)
            ->first();

        if (! $product) {

            $this->dispatch('notify', [
                'type' => 'danger',
                'message' => 'Product not found.',
            ]);

            $this->barcode = '';

            return;
        }

        $this->addToCart($product->id);

        $this->barcode = '';
    }

    public function customerAction(): Action
    {
        return Action::make('customer')

            ->label('Change')

            ->icon('heroicon-o-user')

            ->modalHeading('Select Customer')

            ->modalWidth('3xl')

            ->form([

                Forms\Components\Select::make('customer_id')

                    ->label('Customer')

                    ->searchable()

                    ->preload()

                    ->options(

                        Customer::orderBy('first_name')

                            ->pluck('first_name', 'id')

                    )

                    ->required(),

            ])

            ->action(function (array $data): void {

                $this->customer = Customer::find($data['customer_id']);

                $this->customerId = $this->customer->id;

            });
    }

    public function checkoutAction(): Action
    {
        return Action::make('checkout')

            ->label('Checkout')

            ->icon('heroicon-o-credit-card')

            ->color('success')

            ->modalHeading('Checkout')

            ->modalWidth('2xl')

            ->form([

                Forms\Components\Placeholder::make('amount_due')
                    ->label('Amount Due')
                    ->content(fn () => 'M ' . number_format($this->total, 2)),

                Forms\Components\Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(
                        PaymentMethod::where('is_active', true)
                            ->pluck('name', 'id')
                    )
                    ->required(),

                Forms\Components\TextInput::make('amount_received')
                    ->label('Amount Received')
                    ->numeric()
                    ->default($this->total)
                    ->required()
                    ->live(),

            ])

            ->action(function (array $data) {

                $amountReceived = (float) $data['amount_received'];

                if ($amountReceived < $this->total) {

                    \Filament\Notifications\Notification::make()
                        ->title('Amount received is less than the amount due.')
                        ->danger()
                        ->send();

                    return;
                }

                $change = $amountReceived - $this->total;

                // Temporary
                dd([
                    'cart' => $this->cart,
                    'customer' => $this->customerId,
                    'payment_method' => $data['payment_method_id'],
                    'amount_received' => $amountReceived,
                    'change' => $change,
                ]);

            });
    }

}

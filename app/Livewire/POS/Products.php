<?php

namespace App\Livewire\POS;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;

class Products extends Component
{
    public array $cart = [];

    public function render()
    {
        return view('livewire.pos.products', [
            'products' => Product::where('is_active', true)->get(),
        ]);
    }

    public function addToCart($productId): void
    {
        $product = Product::findOrFail($productId);

        if (isset($this->cart[$productId])) {

            $this->cart[$productId]['quantity']++;

        } else {

            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->product_name,
                'price' => $product->selling_price,
                'quantity' => 1,
            ];
        }

        $this->cart = array_values($this->cart);
    }

    public function increaseQty($productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        }
    }

    public function decreaseQty($productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['quantity']--;

        if ($this->cart[$productId]['quantity'] <= 0) {
            unset($this->cart[$productId]);
        }
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($quantity <= 0) {

            unset($this->cart[$productId]);

        } else {

            $this->cart[$productId]['quantity'] = $quantity;

            $this->cart[$productId]['line_total'] =
                $this->cart[$productId]['price'] * $quantity;
        }

        $this->calculateTotals();
    }
}
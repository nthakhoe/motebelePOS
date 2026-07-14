<?php

namespace App\Livewire\POS;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;

class Cart extends Component
{
    public string $search = '';

    public ?int $selectedCategory = null;

    public array $cart = [];

    public function render()
    {
        $products = Product::query()

            ->when($this->search, function ($query) {

                $query->where(function ($q) {

                    $q->where('product_name', 'like', "%{$this->search}%")
                      ->orWhere('barcode', 'like', "%{$this->search}%")
                      ->orWhere('sku', 'like', "%{$this->search}%");

                });

            })

            ->when($this->selectedCategory, function ($query) {

                $query->where('category_id', $this->selectedCategory);

            })

            ->where('is_active', true)

            ->orderBy('product_name')

            ->paginate(24);

        return view('livewire.pos.cart', [

            'cart' => $this->cart,

        ]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }
}
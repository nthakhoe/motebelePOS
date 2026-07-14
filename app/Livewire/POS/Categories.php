<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use App\Models\Category;

class Categories extends Component
{
    public $categories;

    public $selectedCategory = null;

    public function mount()
    {
        $this->categories = Category::where('is_active', true)
            ->orderBy('category_name')
            ->get();
    }

    public function selectCategory($categoryId)
    {
        $this->selectedCategory = $categoryId;

        $this->dispatch('categorySelected', categoryId: $categoryId);
    }

    public function render()
    {
        return view('livewire.pos.categories');
    }
}
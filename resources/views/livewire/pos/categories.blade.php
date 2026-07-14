<div class="category-panel">

    <div class="sidebar-title">
        <h3>Categories</h3>
    </div>

    <div >

        <button type="button" class="category active"
            wire:click="selectCategory(null)"
            class="{{ is_null($selectedCategory) ? 'active' : '' }}"
        >
            <div class="category-icon">
                <i class="fas fa-store"></i>
            </div>

            <span>📦 All Products</span>
        </button>

        @forelse($categories as $category)

            <button type="button" class="category"
                wire:click="selectCategory({{ $category->id }})"
                class="category {{ $selectedCategory == $category->id ? 'active' : '' }}"
            >

                <span> 🍞 {{ $category->category_name }}</span>

            </button>

        @empty

            <div class="empty-categories">

                <i class="fas fa-folder-open"></i>

                <p>No categories found.</p>

            </div>

        @endforelse

    </div>

</div>
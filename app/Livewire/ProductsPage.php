<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Helper\CartManagment;
use App\Livewire\Partials\Navbar;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;


#[Title('Products Page - E-commerce')]
class ProductsPage extends Component
{
    use WithPagination;

    #[Url]
    public array $selected_categories = [];

    #[Url]
    public array $selected_brands = [];

    #[Url]
    public bool $featured = false;

    #[Url]
    public bool $sale = false;

    #[Url]
    public ?int $price_range = null;

    #[Url]
    public string $sort = 'latest';

    public function updatedSelectedCategories()
    {
        $this->resetPage();
    }

    public function updatedSelectedBrands()
    {
        $this->resetPage();
    }

    public function updatedFeatured()
    {
        $this->resetPage();
    }

    public function updatedSale()
    {
        $this->resetPage();
    }

    public function updatedPriceRange()
    {
        $this->resetPage();
    }

    public function updatedSort()
    {
        $this->resetPage();
    }

    // Add product to cart
    public function addToCart($product_id){
        $total_count = CartManagment::addItemToCart($product_id);

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        LivewireAlert::text('Product added to the cart successfully!')
        ->success()
        ->toast()
        ->position('bottom-end')
        ->show();
    }

    public function render()
    {
        $brands = Brand::where('is_active', 1)->get(['id', 'name', 'slug']);
        $categories = Category::where('is_active', 1)->get(['id', 'name', 'slug']);

        $maxPrice = (int) Product::where('is_active', 1)->max('price');

        $productQuery = Product::query()->where('is_active', 1);

        if (!empty($this->selected_categories)) {
            $productQuery->whereIn('category_id', $this->selected_categories);
        }

        if (!empty($this->selected_brands)) {
            $productQuery->whereIn('brand_id', $this->selected_brands);
        }

        if ($this->featured) {
            $productQuery->where('is_featured', 1);
        }

        if ($this->sale) {
            $productQuery->where('on_sale', 1);
        }

        if (!is_null($this->price_range)) {
            $productQuery->where('price', '<=', (int) $this->price_range);
        }

        if ($this->sort === 'latest') {
            $productQuery->latest();
        } elseif ($this->sort === 'price') {
            $productQuery->orderBy('price');
        }

        return view('livewire.products-page', [
            'products' => $productQuery->paginate(9),
            'categories' => $categories,
            'brands' => $brands,
            'maxPrice' => $maxPrice,
        ]);
    }
}

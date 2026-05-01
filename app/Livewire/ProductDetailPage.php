<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Product;
use App\Helper\CartManagment;
use App\Livewire\Partials\Navbar;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

#[Title('Product Detail - E-commerce')]
class ProductDetailPage extends Component
{
    public $slug;
    public $quantity = 1;

    public function mount($slug){
        $this->slug = $slug;
    }

    public function increaseQty(){
        $this->quantity ++;
    }

    public function decreaseQty(){
        if($this->quantity > 1){
            $this->quantity --;
        }
    }

    // Add product to cart
    public function addToCart($product_id){
        $total_count = CartManagment::addItemToCartWithQty($product_id, $this->quantity);

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        LivewireAlert::text('Product added to the cart successfully!')
        ->success()
        ->toast()
        ->position('bottom-end')
        ->show();
    }

    public function render()
    {
        $product = Product::where('slug', $this->slug)->firstOrFail();

        return view('livewire.product-detail-page', [
            'product' => $product
        ]);
    }
}

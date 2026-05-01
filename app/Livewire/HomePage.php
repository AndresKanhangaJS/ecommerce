<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Brand;
use App\Models\Category;

#[Title('Home Page - E-commerce')]
class HomePage extends Component
{
    public function render()
    {
        $brands = Brand::where('is_active', 1)->get();
        $categories = Category::where('is_active', 1)->get();

        return view('livewire.home-page', compact('brands', 'categories'));
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Order;
use Livewire\WithPagination;

#[Title('My Orders - E-commerce')]
class MyOrdersPage extends Component
{
    use WithPagination;
    public function render()
    {
        $orders = Order::where('user_id', auth()->id())->latest()->paginate(5);
        return view('livewire.my-orders-page', [
            'orders' => $orders
        ]);
    }
}

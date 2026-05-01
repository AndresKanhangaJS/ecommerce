<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;

#[Title('Order Detail - E-commerce')]
class MyOrderDetailPage extends Component
{
    public $order_id;

    public function mount($order)
    {
        $this->order_id = $order;
    }

    public function render()
    {
        $order = Order::where('id', $this->order_id)->firstOrFail();
        $order_itens = OrderItem::where('order_id', $this->order_id)->with('product')->get();
        $address = Address::where('order_id', $this->order_id)->first();
        return view('livewire.my-order-detail-page', [
            'order' => $order,
            'order_itens' => $order_itens,
            'address' => $address
        ]);
    }
}

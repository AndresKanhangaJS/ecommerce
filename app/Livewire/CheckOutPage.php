<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Helper\CartManagment;
use App\Models\Order;
use App\Models\Address;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;

#[Title('Checkout Page - E-commerce')]
class CheckOutPage extends Component
{
    public $first_name;
    public $last_name;
    public $phone;
    public $street_address;
    public $city;
    public $state;
    public $zip_code;
    public $payment_method;

    public function mount(){
        $cart_itens = CartManagment::getCartItemsFromCookie();

        if(count($cart_itens) == 0){
            return redirect('/products');
        }
    }

    public function placeOrder(){
        $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'street_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip_code' => 'required',
            'payment_method' => 'required'
        ]);

        $cart_itens = CartManagment::getCartItemsFromCookie();

        $line_itens = [];
        foreach($cart_itens as $item){
            $line_itens[] = [
                'price_data' => [
                    'currency' => 'aoa',
                    'unit_amount' => $item['unit_amount'] * 100,
                    'product_data' => [
                        'name' => $item['name']
                    ]
                ],
                'quantity' => $item['quantity']
            ];
        }

        $order = new Order();
        $order->user_id = auth()->user()->id;
        $order->grand_total = CartManagment::calculateGrandTotal($cart_itens);
        $order->payment_method = $this->payment_method;
        $order->payment_status = 'pending';
        $order->status = 'new';
        $order->currency = 'AOA';
        $order->shipping_amount = 0;
        $order->shipping_method = 'none';
        $order->notes = 'Order placed by ' . auth()->user()->name;

        $address = new Address();
        $address->first_name = $this->first_name;
        $address->last_name = $this->last_name;
        $address->phone = $this->phone;
        $address->street_address = $this->street_address;
        $address->city = $this->city;
        $address->state = $this->state;
        $address->zip_code = $this->zip_code;

        $redirect_url = '';

        if($this->payment_method === 'stripe'){
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $session = Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => auth()->user()->email,
                'line_items' => $line_itens,
                'mode' => 'payment',
                'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('cancel'),
            ]);

            $redirect_url = $sessionCheckout->url;
        } else{
            $redirect_url = route('success');
        }

        $order->save();
        $address->order_id = $order->id;
        $address->save();
        $order->items()->createMany($cart_itens);
        CartManagment::clearCartItems();
        Mail::to(request()->user())->send(new OrderPlaced($order));

        return redirect($redirect_url);
    }

    public function render()
    {
        $cart_itens = CartManagment::getCartItemsFromCookie();
        $grand_total = CartManagment::calculateGrandTotal($cart_itens);
        return view('livewire.check-out-page', [
            'cart_itens' => $cart_itens,
            'grand_total' => $grand_total
        ]);
    }
}

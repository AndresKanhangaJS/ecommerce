<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Order;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Livewire\Attributes\Url;

#[Title('Success - E-commerce')]
class SuccessPage extends Component
{
    #[Url]
    public $session_id;

    public function render()
    {
        $latest_order = Order::with('address')->where('user_id', auth()->user()->id)->latest()->first();

        if(!session()->has('session_id')){
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $session_info = Session::retrieve($this->session_id);

            if($session_info->payment_status != 'paid'){
                $latest_order->payment_status = 'failed';
                $latest_order->save();

                return redirect()->route('cancel');
            }else if($latest_order->payment_status == 'paid'){
                $latest_order->payment_status = 'paid';
                $latest_order->save();
            }
        }

        return view('livewire.success-page', [
            'order' => $latest_order
        ]);
    }
}

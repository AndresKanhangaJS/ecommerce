<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Order;
use App\Services\OrderPaymentService;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Livewire\Attributes\Url;

#[Title('Success - E-commerce')]
class SuccessPage extends Component
{
    #[Url]
    public $session_id;

    public function render(OrderPaymentService $orderPaymentService)
    {
        $order = null;

        if($this->session_id){
            Stripe::setApiKey(config('services.stripe.secret'));
            $session_info = Session::retrieve($this->session_id);

            $order = $orderPaymentService->markPaidFromStripeSession($session_info);

            if($order && $order->payment_status !== 'paid'){
                return redirect()->route('cancel');
            }
        }

        if(! $order){
            $order = Order::with('address')->where('user_id', auth()->user()->id)->latest()->first();
        }

        return view('livewire.success-page', [
            'order' => $order
        ]);
    }
}

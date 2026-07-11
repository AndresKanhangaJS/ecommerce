<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Checkout\Session;

class OrderPaymentService
{
    // Applies a Stripe Checkout Session's payment status to the Order it belongs to.
    // Matches by stripe_session_id rather than "latest order for the user" so concurrent
    // checkouts can't update the wrong order.
    public function markPaidFromStripeSession(Session $session): ?Order
    {
        $order = Order::where('stripe_session_id', $session->id)->first();

        if (! $order) {
            return null;
        }

        if ($session->payment_status !== 'paid') {
            $order->payment_status = 'failed';
            $order->save();

            return $order;
        }

        $order->payment_status = 'paid';
        $order->status = 'processing';
        $order->save();

        return $order;
    }
}

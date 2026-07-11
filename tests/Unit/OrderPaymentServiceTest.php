<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Tests\TestCase;

class OrderPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_the_matching_order_as_paid_when_the_stripe_session_is_paid(): void
    {
        $order = $this->createOrder(['stripe_session_id' => 'cs_test_123']);
        $otherOrder = $this->createOrder(['stripe_session_id' => 'cs_test_other']);

        $session = Session::constructFrom(['id' => 'cs_test_123', 'payment_status' => 'paid']);

        $updated = (new OrderPaymentService())->markPaidFromStripeSession($session);

        $this->assertNotNull($updated);
        $this->assertSame($order->id, $updated->id);
        $this->assertSame('paid', $updated->fresh()->payment_status);
        $this->assertSame('processing', $updated->fresh()->status);
        $this->assertSame('pending', $otherOrder->fresh()->payment_status);
    }

    public function test_marks_the_matching_order_as_failed_when_the_stripe_session_is_not_paid(): void
    {
        $order = $this->createOrder(['stripe_session_id' => 'cs_test_456']);

        $session = Session::constructFrom(['id' => 'cs_test_456', 'payment_status' => 'unpaid']);

        (new OrderPaymentService())->markPaidFromStripeSession($session);

        $this->assertSame('failed', $order->fresh()->payment_status);
    }

    public function test_returns_null_when_no_order_matches_the_session_id(): void
    {
        $session = Session::constructFrom(['id' => 'cs_test_unknown', 'payment_status' => 'paid']);

        $result = (new OrderPaymentService())->markPaidFromStripeSession($session);

        $this->assertNull($result);
    }

    private function createOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'grand_total' => 100,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'status' => 'new',
            'currency' => 'AOA',
        ], $overrides));
    }
}

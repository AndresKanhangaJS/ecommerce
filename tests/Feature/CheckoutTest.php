<?php

namespace Tests\Feature;

use App\Livewire\CheckOutPage;
use App\Mail\OrderPlaced;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_order_creates_order_address_and_items_and_redirects_to_success(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = $this->createProduct(['price' => 150]);

        Livewire::withCookie('cart_items', json_encode([
                [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'image' => null,
                    'quantity' => 2,
                    'unit_amount' => 150,
                    'total_amount' => 300,
                ],
            ]))
            ->actingAs($user)
            ->test(CheckOutPage::class)
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('phone', '999999999')
            ->set('street_address', 'Rua Principal 1')
            ->set('city', 'Luanda')
            ->set('state', 'Luanda')
            ->set('zip_code', '0000')
            ->set('payment_method', 'cash')
            ->call('placeOrder')
            ->assertRedirect(route('success'));

        $order = Order::first();

        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('pending', $order->payment_status);
        $this->assertEquals(300, $order->grand_total);

        $this->assertDatabaseHas('addresses', [
            'order_id' => $order->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Mail::assertSent(OrderPlaced::class);
    }

    public function test_place_order_recalculates_price_from_the_database_instead_of_trusting_the_cart_cookie(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        // Product's current price is 150, but the cart cookie still has a stale price of 50
        // (e.g. cached before an admin changed the price).
        $product = $this->createProduct(['price' => 150]);

        Livewire::withCookie('cart_items', json_encode([
                [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'image' => null,
                    'quantity' => 2,
                    'unit_amount' => 50,
                    'total_amount' => 100,
                ],
            ]))
            ->actingAs($user)
            ->test(CheckOutPage::class)
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('phone', '999999999')
            ->set('street_address', 'Rua Principal 1')
            ->set('city', 'Luanda')
            ->set('state', 'Luanda')
            ->set('zip_code', '0000')
            ->set('payment_method', 'cash')
            ->call('placeOrder');

        $order = Order::first();

        $this->assertEquals(300, $order->grand_total);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'unit_amount' => 150,
            'total_amount' => 300,
        ]);
    }

    public function test_mount_redirects_to_products_when_cart_is_empty(): void
    {
        $user = User::factory()->create();

        Livewire::withCookie('cart_items', json_encode([]))
            ->actingAs($user)
            ->test(CheckOutPage::class)
            ->assertRedirect('/products');
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Category', 'slug' => 'category-'.uniqid()]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand-'.uniqid()]);

        return Product::create(array_merge([
            'name' => 'Widget',
            'slug' => 'widget-'.uniqid(),
            'price' => 100,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ], $overrides));
    }
}

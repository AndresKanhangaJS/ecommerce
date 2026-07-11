<?php

namespace Tests\Feature;

use App\Helper\CartManagment;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class CartManagmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_cart_items_from_cookie_reads_the_current_request_cookie(): void
    {
        // Regression test: CartManagment used to call the non-existent Cookie::get(),
        // which throws a BadMethodCallException on every page load.
        $this->bindCartCookie([
            ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'unit_amount' => 10, 'total_amount' => 20],
        ]);

        $items = CartManagment::getCartItemsFromCookie();

        $this->assertCount(1, $items);
        $this->assertSame(2, $items[0]['quantity']);
    }

    public function test_get_cart_items_from_cookie_returns_empty_array_when_no_cookie_present(): void
    {
        $this->bindCartCookie([]);

        $this->assertSame([], CartManagment::getCartItemsFromCookie());
    }

    public function test_add_item_to_cart_creates_a_new_line(): void
    {
        $product = $this->createProduct(['price' => 100]);
        $this->bindCartCookie([]);

        $count = CartManagment::addItemToCart($product->id);

        $this->assertSame(1, $count);
        $items = $this->queuedCartItems();
        $this->assertSame($product->id, $items[0]['product_id']);
        $this->assertSame(1, $items[0]['quantity']);
        $this->assertEquals(100, $items[0]['unit_amount']);
        $this->assertEquals(100, $items[0]['total_amount']);
    }

    public function test_add_item_to_cart_increments_quantity_when_already_present(): void
    {
        $product = $this->createProduct(['price' => 100]);
        $this->bindCartCookie([
            ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_amount' => 100, 'total_amount' => 100],
        ]);

        CartManagment::addItemToCart($product->id);

        $items = $this->queuedCartItems();
        $this->assertCount(1, $items);
        $this->assertSame(2, $items[0]['quantity']);
        $this->assertEquals(200, $items[0]['total_amount']);
    }

    public function test_add_item_to_cart_with_qty_sets_an_explicit_quantity(): void
    {
        $product = $this->createProduct(['price' => 50]);
        $this->bindCartCookie([
            ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_amount' => 50, 'total_amount' => 50],
        ]);

        CartManagment::addItemToCartWithQty($product->id, 5);

        $items = $this->queuedCartItems();
        $this->assertSame(5, $items[0]['quantity']);
        $this->assertEquals(250, $items[0]['total_amount']);
    }

    public function test_remove_item_from_cart(): void
    {
        $this->bindCartCookie([
            ['product_id' => 1, 'name' => 'A', 'quantity' => 1, 'unit_amount' => 10, 'total_amount' => 10],
            ['product_id' => 2, 'name' => 'B', 'quantity' => 1, 'unit_amount' => 20, 'total_amount' => 20],
        ]);

        CartManagment::removeItemFromCart(1);

        $items = array_values($this->queuedCartItems());
        $this->assertCount(1, $items);
        $this->assertSame(2, $items[0]['product_id']);
    }

    public function test_increment_and_decrement_item_quantity(): void
    {
        $this->bindCartCookie([
            ['product_id' => 1, 'name' => 'A', 'quantity' => 1, 'unit_amount' => 10, 'total_amount' => 10],
        ]);
        CartManagment::incrementItemQuantity(1);
        $this->assertSame(2, $this->queuedCartItems()[0]['quantity']);

        $this->bindCartCookie($this->queuedCartItems());
        CartManagment::decrementItemQuantity(1);
        $this->assertSame(1, $this->queuedCartItems()[0]['quantity']);

        // Quantity never drops below 1.
        $this->bindCartCookie($this->queuedCartItems());
        CartManagment::decrementItemQuantity(1);
        $this->assertSame(1, $this->queuedCartItems()[0]['quantity']);
    }

    public function test_calculate_grand_total_sums_line_totals(): void
    {
        $total = CartManagment::calculateGrandTotal([
            ['total_amount' => 10],
            ['total_amount' => 25.5],
        ]);

        $this->assertEquals(35.5, $total);
    }

    private function bindCartCookie(array $items): void
    {
        $this->app->instance('request', Request::create('/', 'GET', [], [
            'cart_items' => json_encode($items),
        ]));
    }

    private function queuedCartItems(): array
    {
        $cookie = Cookie::queued('cart_items');
        $this->assertNotNull($cookie, 'Expected a "cart_items" cookie to have been queued.');

        return json_decode($cookie->getValue(), true);
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

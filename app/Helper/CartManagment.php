<?php

namespace App\Helper;

use Illuminate\Support\Facades\Cookie;
use App\Models\Product;

class CartManagment
{
    // Add item to cart
    static public function addItemToCart($product_id, $quantity = 1){
        $cart_items = self::getCartItemsFromCookie();

        $existing_item = null;

        foreach($cart_items as $key => $item){
            if($item['product_id'] == $product_id){
                $existing_item = $key;
                break;
            }
        }

        if($existing_item !== null){
            $cart_items[$existing_item]['quantity'] ++; // Increment quantity

            $cart_items[$existing_item]['total_amount'] = $cart_items[$existing_item]['quantity'] * $cart_items[$existing_item]['unit_amount']; // Update total amount
        } else {
            $product = Product::where('id', $product_id)->first(['id', 'name', 'price', 'images']);

            if($product){
                $cart_items[] = [
                    'product_id' => $product_id,
                    'name' => $product->name,
                    'image' => $product->images[0] ?? null,
                    'quantity' => 1,
                    'unit_amount' => $product->price,
                    'total_amount' => $product->price
                ]; // Add new item
            }
        }

        self::addCartItemToCookie($cart_items);

        return count($cart_items);
    }

    // Add item to cart with qty
    static public function addItemToCartWithQty($product_id, $quantity){
        $cart_items = self::getCartItemsFromCookie();

        $existing_item = null;

        foreach($cart_items as $key => $item){
            if($item['product_id'] == $product_id){
                $existing_item = $key;
                break;
            }
        }

        if($existing_item !== null){
            $cart_items[$existing_item]['quantity'] = $quantity; // Set quantity

            $cart_items[$existing_item]['total_amount'] = $cart_items[$existing_item]['quantity'] * $cart_items[$existing_item]['unit_amount']; // Update total amount
        } else {
            $product = Product::where('id', $product_id)->first(['id', 'name', 'price', 'images']);

            if($product){
                $cart_items[] = [
                    'product_id' => $product_id,
                    'name' => $product->name,
                    'image' => $product->images[0] ?? null,
                    'quantity' => $quantity,
                    'unit_amount' => $product->price,
                    'total_amount' => $product->price * $quantity
                ]; // Add new item
            }
        }

        self::addCartItemToCookie($cart_items);

        return count($cart_items);
    }

    // Remove item from cart
    static public function removeItemFromCart($product_id){
        $cart_items = self::getCartItemsFromCookie();

        foreach($cart_items as $key => $item){
            if($item['product_id'] == $product_id){
                unset($cart_items[$key]);
            }
        }

        self::addCartItemToCookie($cart_items);

        return $cart_items;
    }

    // Add cart items to cookie
    static public function addCartItemToCookie($cart_items){
        Cookie::queue('cart_items', json_encode($cart_items), 60 * 24 * 7); // Store for 7 days
    }


    // CLear cart items from cookie
    static public function clearCartItems(){
        Cookie::queue(Cookie::forget('cart_items'));
    }


    // Get all cart items from cookie
    static public function getCartItemsFromCookie(){
        $cart_items = json_decode(Cookie::get('cart_items'), true);
        if(!$cart_items){
            $cart_items = [];
        }
        return $cart_items;
    }


    // Increment item quantity
    static public function incrementItemQuantity($product_id){
        $cart_items = self::getCartItemsFromCookie();

        foreach($cart_items as $key => $item){
            if($item['product_id'] == $product_id){
                $cart_items[$key]['quantity'] ++; // Increment quantity

                $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount']; // Update total amount
            }
        }

        self::addCartItemToCookie($cart_items);

        return $cart_items;
    }


    // Decrement item quantity
    static public function decrementItemQuantity($product_id){
        $cart_items = self::getCartItemsFromCookie();

        foreach($cart_items as $key => $item){
            if($item['product_id'] == $product_id){
                if($cart_items[$key]['quantity'] > 1) {
                    $cart_items[$key]['quantity'] --; // Decrement quantity

                    $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount']; // Update total amount
                    continue;
                }
            }
        }

        self::addCartItemToCookie($cart_items);

        return $cart_items;
    }


    // Calculate grand total
    static public function calculateGrandTotal($items){
        return array_sum(array_column($items, 'total_amount'));
    }
}


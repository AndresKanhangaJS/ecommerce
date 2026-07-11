---
name: checkout-payments
description: Domain knowledge for the cart/checkout/Stripe/order payment flow in this project — file map, lifecycle, and bugs already fixed here that must not be reintroduced. Use whenever touching cart, checkout, Stripe, orders, or payment status code.
---

# Cart → Checkout → Payment lifecycle

## File map

- `app/Helper/CartManagment.php` — the cart. Cookie-based (`cart_items`, JSON, 7-day
  TTL), not a DB table. Reads via `request()->cookie('cart_items')` (NOT
  `Cookie::get()` — that method does not exist on Laravel's `CookieJar`; calling it
  throws `BadMethodCallException`). Writes via `Cookie::queue()`.
- `app/Livewire/CheckOutPage.php` — validates shipping fields, re-prices the cart
  against the DB (`applyCurrentPrices()` — never trust `unit_amount` from the cart
  cookie, it may be stale), creates `Order` + `Address` + `OrderItem`s, and if
  `payment_method === 'stripe'` creates a Stripe Checkout `Session` and stores its id
  on `Order.stripe_session_id`.
- `app/Services/OrderPaymentService.php` — single source of truth for turning a Stripe
  `Session` into an order status update. Looks the `Order` up by
  `stripe_session_id` (never by "latest order for the user" — a user can have more
  than one order in flight). Sets `payment_status` to `paid`/`failed` based on
  `$session->payment_status`.
- `app/Http/Controllers/StripeWebhookController.php` — `POST /webhooks/stripe`
  (CSRF-exempt, see `bootstrap/app.php`). Verifies the Stripe signature and delegates
  `checkout.session.completed` to `OrderPaymentService`. This is the reliable
  confirmation path — it doesn't depend on the customer's browser making it back to
  `/success`.
- `app/Livewire/SuccessPage.php` — also calls `OrderPaymentService` (best-effort,
  synchronous confirmation on redirect back from Stripe) so the UI can show the
  updated status immediately, but the webhook is the source of truth.
- `app/Models/User.php::canAccessPanel()` — Filament admin access is gated by the
  `is_admin` boolean column, not a hardcoded email.

## Bugs already fixed here — don't reintroduce them

1. `Cookie::get()` doesn't exist in Laravel; use `request()->cookie()`.
2. `CheckOutPage::placeOrder()` used to redirect to an undefined `$sessionCheckout`
   variable instead of `$session` — fatal on every Stripe checkout.
3. `SuccessPage` used to compare `$order->payment_status == 'paid'` (the value already
   in the DB) instead of the Stripe session's status — orders never actually
   transitioned to `paid`.
4. Order matching used to be "the user's latest order" instead of matching by
   `stripe_session_id` — wrong order could get updated if a user had two checkouts in
   flight.
5. Stripe keys must come from `config('services.stripe.*')` (see
   `config/services.php`), not raw `env()` calls in application code.

## Testing

`tests/Feature/CartManagmentTest.php`, `tests/Feature/CheckoutTest.php`, and
`tests/Unit/OrderPaymentServiceTest.php` cover this flow. Checkout tests use
`payment_method !== 'stripe'` to avoid hitting the real Stripe API;
`OrderPaymentServiceTest` builds a `Stripe\Checkout\Session` in memory via
`Session::constructFrom([...])` to test payment-status transitions without network
calls. Follow the same pattern for new tests in this area — don't call the live
Stripe API from tests.

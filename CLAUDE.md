# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 12 e-commerce app. Storefront is built with Livewire 3 (full-page components), the admin panel with
Filament 3, and checkout payments go through Stripe.

## Commands

The project has no PHP/Node installed on the host — everything runs through Docker
(`docker-compose.yml`). See the `docker-env` skill for the full command reference; the
essentials:

Start everything (build on first run):
```
docker compose up -d --build
```
The `app` container's entrypoint (`docker/php/entrypoint.sh`) auto-copies `.env`,
runs `composer install`, `key:generate`, `migrate --force`, and `storage:link` on
first boot, so a plain `up` is enough to get a working app. Services: `app` (php-fpm),
`nginx` (`http://localhost:${APP_PORT:-8080}`), `mysql`, `redis`, `queue` (supervisor
running `queue:work`), `mailpit` (`http://localhost:8025`), `node` (Vite on `5173`).

Tests:
```
docker compose exec app php artisan test                                  # full suite
docker compose exec app php artisan test --filter=TestName                # single test
docker compose exec app php artisan test tests/Feature/SomeTest.php       # single file
```
Test env uses sqlite in-memory DB, array cache/session, sync queue (see `phpunit.xml`) — no external services needed to run tests.

Lint / format (Laravel Pint):
```
docker compose exec app vendor/bin/pint            # format app/ per Laravel style
docker compose exec app vendor/bin/pint --test     # check only, no changes
```

Frontend build:
```
docker compose exec node npm run build
```

## Architecture

**Two front ends share one Laravel app:**
- `/` , `/products`, `/cart`, `/checkout`, `/my-orders`, auth pages, etc. — full-page Livewire components under
  `app/Livewire/`, routed directly in `routes/web.php` (e.g. `Route::get('/cart', CartPage::class)`), each with a
  matching Blade view in `resources/views/livewire/`. `app/Livewire/Auth/*` handles login/register/password-reset;
  `app/Livewire/Partials/{Navbar,Footer}.php` are shared layout partials rendered inside
  `resources/views/components/layouts/app.blade.php`.
- `/admin` — Filament panel configured in `app/Providers/Filament/AdminPanelProvider.php` (registered in
  `bootstrap/providers.php`). Resources (`app/Filament/Resources/*Resource.php`) are CRUD screens for Product,
  Category, Brand, Order, User; each resource has a `Pages/` subfolder (List/Create/Edit/View) and some have
  `RelationManagers/` (e.g. `OrderResource/RelationManagers/AddressRelationManager.php`) and `Widgets/`
  (e.g. `OrderResource/Widgets/OrderStats.php`, registered as a dashboard widget in `AdminPanelProvider`).
  Resources/pages/widgets under `app/Filament` are auto-discovered by directory, so new ones don't need manual
  registration — just follow the existing naming convention. Panel access is gated by `User::canAccessPanel()`
  checking the `is_admin` boolean column, not a hardcoded email.

  Note: `app/Providers/Filament/AdminPanelProvider_test.php` is a stray duplicate of `AdminPanelProvider.php`
  (older "StockPro" branding) that also declares `class AdminPanelProvider` in the same namespace but is not
  wired up in `bootstrap/providers.php`. It's dead code and a landmine for autoloading — don't edit it as if it
  were live, and prefer deleting it over maintaining two copies if you touch panel config.

**Cart is cookie-based, not DB-based.** `app/Helper/CartManagment.php` (static methods) is the single source of
truth for cart state: it reads/writes a JSON-encoded `cart_items` cookie (7-day TTL) and computes per-item and
grand totals. There's no `Cart` model — cart items only become persisted `OrderItem` rows at checkout. Any page
that touches the cart (CartPage, ProductDetailPage, CheckOutPage, Navbar) goes through this helper, and the
Navbar listens for the `update-cart-count` Livewire event to keep its badge in sync after other components
mutate the cart.

**Checkout flow** (`app/Livewire/CheckOutPage.php`): validates shipping/address fields → re-prices the cart against
the DB (`applyCurrentPrices()` — the cart cookie's `unit_amount` may be stale) → builds Stripe line items → creates
the `Order` (status `new`, payment_status `pending`) and its `Address` → if `payment_method === 'stripe'`, creates a
Stripe Checkout `Session` (id stored on `Order.stripe_session_id`) and redirects there (success/cancel URLs point to
`SuccessPage`/`CancelPage`); otherwise redirects straight to the success route → persists `OrderItem` rows via
`$order->items()->createMany($cart_items)`, clears the cart cookie, and emails the user via
`App\Mail\OrderPlaced` (Blade template `resources/views/emails/orders/placed.blade.php`). Stripe currency is
hardcoded to `aoa` (Angolan Kwanza) in cents.

**Payment confirmation** goes through `app/Services/OrderPaymentService.php`, which is the single source of truth
for turning a Stripe `Session` into an order status update — it looks the `Order` up by `stripe_session_id` (never
"latest order for the user") and sets `payment_status` from `$session->payment_status`. Two callers feed it: the
`POST /webhooks/stripe` route (`app/Http/Controllers/StripeWebhookController.php`, CSRF-exempt per
`bootstrap/app.php`), which verifies the Stripe signature and is the reliable confirmation path since it doesn't
depend on the customer's browser making it back to `/success`; and `app/Livewire/SuccessPage.php`, which calls it
best-effort on redirect back from Stripe so the UI can show the updated status immediately. See the
`checkout-payments` skill for the full list of bugs already fixed in this flow.

**Models** (`app/Models/`) are plain Eloquent, minimal logic: `Product` belongs to `Category`/`Brand` and casts
`images` to an array (first image is used as the cart/list thumbnail); `Order` has many `OrderItem` and one
`Address`; relations are the main thing worth knowing before querying across them.

**Auth** uses Laravel's built-in `auth`/`guest` middleware groups (see `routes/web.php`) with Livewire pages for
login/register/forgot/reset instead of the default Breeze/Jetstream controllers — there's no separate
`AuthController`.

---
name: docker-env
description: Operate this project's local Docker environment (docker-compose.yml) — start/stop containers, run artisan/composer/npm/pint/tests inside them, view logs, reset the database. Use whenever the user asks to run the app, run tests, run migrations, or manage containers.
---

# Docker environment

This project has no PHP/Node installed on the host — everything runs through
`docker-compose.yml`. Services: `app` (php-fpm), `nginx` (port `${APP_PORT:-8080}`),
`mysql`, `redis`, `queue` (supervisor running `queue:work`), `mailpit` (SMTP catcher,
UI on `${FORWARD_MAILPIT_PORT:-8025}`), `node` (Vite dev server on `5173`).

`docker/php/entrypoint.sh` runs on `app` container start: copies `.env.example` to
`.env` if missing, `composer install` if `vendor/` is missing, `key:generate` if
`APP_KEY` is empty, `migrate --force`, and `storage:link`. So a plain `up` is usually
enough to get a working app — no manual composer/migrate step required on first run.

## Common commands

Start everything (build on first run):
```
docker compose up -d --build
```

App: `http://localhost:${APP_PORT:-8080}` · Mailpit UI: `http://localhost:8025` ·
Vite/HMR: `http://localhost:5173`

Stop:
```
docker compose down
```

Run artisan / composer / npm / pint inside the `app` container:
```
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=TestName
docker compose exec app php artisan test tests/Feature/SomeTest.php
docker compose exec app vendor/bin/pint
docker compose exec app vendor/bin/pint --test
docker compose exec app composer require some/package
docker compose exec node npm run build
```

Reset the database (drops and re-runs all migrations + `DatabaseSeeder`):
```
docker compose exec app php artisan migrate:fresh --seed
```
Seeded users: `test@example.com` (regular) and `admin@example.com` (admin, `/admin`
access via `is_admin`), both password `password`.

Logs:
```
docker compose logs -f app
docker compose logs -f queue
```

Rebuild after changing `docker/php/Dockerfile` or `docker-compose.yml`:
```
docker compose up -d --build
```

MySQL shell:
```
docker compose exec mysql mysql -u ecommerce -p ecommerce
```

## Gotchas

- The whole repo is bind-mounted into `app`/`queue`/`node`, so code edits on the host
  are reflected immediately — only `--build` is needed when the Dockerfile itself or
  installed extensions change, not for ordinary PHP/Blade/JS edits.
- `queue` uses the same image as `app` but runs `supervisord` (see
  `docker/php/supervisord.conf`) instead of `php-fpm`, so a code change that needs the
  queue worker restarted requires `docker compose restart queue`.
- Stripe webhooks need a public URL. For local testing, use the Stripe CLI:
  `stripe listen --forward-to localhost:${APP_PORT:-8080}/webhooks/stripe`, then copy
  the printed signing secret into `.env` as `STRIPE_WEBHOOK_SECRET`.

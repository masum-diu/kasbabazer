# Kasba Bazer

A local social-commerce marketplace for Kasba, Brahmanbaria. Laravel 13 + Inertia/React.

## Run locally

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# Local quick start uses SQLite (set DB_CONNECTION=sqlite in .env and create database/database.sqlite).
php artisan migrate:fresh --seed     # loads dummy users, shops, listings, comments, chats
php artisan storage:link
npm run build                        # or `npm run dev`
php artisan serve
```

Production database is PostgreSQL (`DB_CONNECTION=pgsql`, enable `pdo_pgsql`).

## Demo accounts (development only, password `password`)

| Role   | Email                 |
|--------|-----------------------|
| Admin  | admin@kasbabazer.com  |
| Seller | seller@kasbabazer.com |
| Buyer  | buyer@kasbabazer.com  |

Dummy product pictures are local SVGs in `public/dummy` (no internet needed).

## Google sign-in (Clerk)

Set `VITE_CLERK_PUBLISHABLE_KEY` and `CLERK_SECRET_KEY` in `.env`, enable Google in the Clerk dashboard,
then rebuild assets. Clerk sessions are verified server-side (`ClerkService`) and exchanged for a Laravel session.

## Features

Listings with photos, search/filters/sort, infinite scroll, nested comments and replies, reactions,
saved items, seller shops and reviews, buyer-seller chat, notifications, reports, and an admin panel (`/admin`).
Heavy sections (comments, related items) load lazily when scrolled into view.

## Quality checks

```bash
php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse && npm run types:check
```

A REST API exists for auth, categories and locations (`routes/api.php`, Sanctum).
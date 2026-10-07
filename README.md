# Splitwise-like Expense Sharing API

This project is a Laravel + MongoDB backend for a Splitwise-style application with manual random-token authentication, group management, expense splitting, balances, and settlements.

## Stack
- Laravel 12
- MongoDB via `mongodb/laravel-mongodb`
- No JWT, Passport, Sanctum, or OAuth authentication packages
- Manual Bearer-token middleware

## Requirements
- PHP 8.2+
- MongoDB running locally on `mongodb://127.0.0.1:27017`
- Composer dependencies installed

## Setup
1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Create the environment file if needed:
   ```bash
   copy .env.example .env
   ```
3. Start MongoDB locally.
4. Generate the Laravel app key:
   ```bash
   php artisan key:generate
   ```
5. Run the seeders:
   ```bash
   php artisan db:seed
   ```
6. Start the app:
   ```bash
   php artisan serve
   ```

## Main API flow
- `POST /api/register`
- `POST /api/login`
- `POST /api/logout`
- `GET /api/me`
- `POST /api/groups`
- `POST /api/groups/{groupId}/members`
- `GET /api/groups/{groupId}/balances`
- `POST /api/groups/{groupId}/expenses`
- `POST /api/groups/{groupId}/settlements`

## Authentication
Authentication uses a secure random token generated with `bin2hex(random_bytes(32))` and stored in a `tokens` collection. Each authenticated request includes:

```http
Authorization: Bearer <token>
```

The custom middleware validates the bearer token and attaches the authenticated user to the request.

## Notes
This implementation follows the assignment requirements for manual token auth with MongoDB, but a live MongoDB instance is required for runtime testing and full feature verification.

# Laravel API Based Boilerplate

This is a Best Laravel API boilerplate project with pre-configured authentication, authorization, and other common features.

## Features

- Laravel 11.x
- User Authentication (Laravel Sanctum)
- Role-based Authorization (Spatie Laravel-permission)
- Google OAuth Integration
- API Routes for User Management
- Database Migrations and Seeders
- Docker Configuration

## Requirements

- PHP 8.2+
- Composer
- Docker (optional)

## Installation

1. Clone the repository:

   ```
   git clone git@github.com:hironate/laravel-api-boilerplate.git
   cd laravel-api-boilerplate
   ```

2. Install dependencies:

   ```
   composer install
   ```

3. Copy the `.env.example` file to `.env` and configure your environment variables:

   ```
   cp .env.example .env
   ```

4. Generate application key:

   ```
   php artisan key:generate
   ```

5. Run migrations and seeders:
   ```
   php artisan migrate --seed
   ```

## Docker Setup

This project includes a Docker configuration for easy setup. To use Docker:

1. Make sure Docker and Docker Compose are installed on your system.

2. Build and start the containers:

   ```
   docker-compose up -d --build
   ```

3. The application will be available at `http://localhost:8000`.

## API Routes

The API routes are defined in the following files:

- `routes/api.php`
- `routes/user.php`

## Authentication

This project uses Laravel Sanctum for API authentication. The authentication routes and controllers are located in:

- `app/Http/Controllers/Api/Auth/AuthController.php`
- `routes/api.php`

## Authorization

Role-based authorization is implemented using Spatie's Laravel-permission package. Roles and permissions can be managed through the `UserAndRoleSeeder`:

Google OAuth is configured for user authentication. Make sure to set up your Google OAuth credentials in the `.env` file:

## Testing

Run the tests using PHPUnit:

```
php artisan test
```

## License

This project is licensed under the MIT License. See the `LICENSE` file for more details.

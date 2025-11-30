# mta-community

**MTA Community** (comm2) is a community platform for Multi Theft Auto (MTA) that enables developers to upload, manage, and distribute MTA resources (scripts, gamemodes, maps, etc.). The platform includes user authentication, resource management with versioning, ratings, downloads tracking, and integrations with MTA servers, forum news, and GitHub.

## Key Features

- User authentication with 2FA
- News articles listing
- MTA server listings integration
- MTA GitHub activity display
- MTA Resource upload and version management
- MTA Resource ratings and download tracking
- Admin/moderation tools

## Technology Stack

### Backend

Laravel (PHP Framework) with single MySQL database.

### Frontend

- **Livewire** - UI library
- **Tailwind CSS** - CSS framework
- **Vite** - Build tool

### Authentication

- **Laravel Fortify** - Authentication backend
- Two-factor authentication (TOTP)
- Email verification

### Development Tools

- **Pest PHP** - Testing framework
- **Laravel Pint** - Code style fixer

## Getting Started

### Dependencies

- PHP 8.4 with GD extension
- Composer
- NodeJS & NPM
- MySQL server 8+ (one database)

#### Notes [Windows]

- PHP, Composer and Node.JS can be easily installed using [Laravel Herd](https://herd.laravel.com/windows).
- MySQL Server can be set up using the [official installer](https://dev.mysql.com/downloads/installer/).

#### Notes [Linux]

All of this should be easy to set up with your package manager.

### Setup

- Ensure PHP, Composer and NPM are available
- Create a MySQL database (e.g. "comm2")
- `cd comm2`
- `composer install`
- Copy .env.example to .env and configure correctly
- Generate a Laravel secret key: `php artisan key:generate`
- Run the DB migrations: `php artisan migrate`
- If desired, seed the DB: `php artisan db:seed`
- Run `npm install` to prepare NodeJS environment

### Development

- `cd comm2`
- Start local dev server: `composer run dev`
- Run the linter: `composer run lint:fix`
- Run tests and ensure they are passing: `composer run test`

## License

Unless otherwise specified, all source code hosted on this repository is licensed under the GPLv3 license. See the [LICENSE](./LICENSE) file for more details.

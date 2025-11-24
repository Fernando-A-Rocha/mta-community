# mta-community

New community platform for Multi Theft Auto

## `comm2` Project

### Dependencies

- PHP 8.4
- Composer
- NodeJS & NPM
- MySQL server 8+ (one database)

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

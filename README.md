# mta-community

**MTA Community** (comm2) is a community platform for Multi Theft Auto (MTA) that enables developers to upload, manage, and distribute MTA resources (scripts, gamemodes, maps, etc.). The platform includes user authentication, resource management with versioning, ratings, downloads tracking, and integrations with MTA servers, forum news, and GitHub.

## Contributing

Please read the [CONTRIBUTING.md file](./CONTRIBUTING.md).

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

## Social Features

- **Notifications center** – Unread badge in the navigation, modal detail view, bulk read/unread/delete actions, and automatic notifications for resource updates, releases, reviews, friend activity, and report status changes.
- **Resource & user following** – Authenticated members can follow resources they do not own and any public profile; followers automatically receive release/edit/review updates.
- **Friendships** – Full request/accept/decline/cancel/unfriend flow, privacy toggle to block incoming requests, and management tooling under `Settings → Friends` (including requests by username and existing friend lists).
- **Privacy guardrails** – Switching a profile to private forces followers to be removed with explicit confirmation so users understand the impact beforehand.

### Manual Verification

1. Run the latest migrations: `php artisan migrate`.
2. Sign in with two distinct test accounts (A & B).
3. As account A, follow a resource owned by account B; edit the resource and ensure A receives a notification and can mark it read/unread/delete from `/notifications`.
4. From B’s profile, send A a friend request, accept/decline/cancel it from both the profile header and `Settings → Friends`, and confirm notifications fire for each state change.
5. Toggle the “Allow friend requests” switch in `Settings → Friends` and verify requests are blocked until it is re-enabled.
6. Follow account B from A, publish a new resource/review as B, and confirm A receives user-follow notifications.
7. Switch account B to a private profile, confirm the warning/checkbox flow, and verify all followers are removed automatically.

## License

Unless otherwise specified, all source code hosted on this repository is licensed under the GPLv3 license. See the [LICENSE](./LICENSE) file for more details.

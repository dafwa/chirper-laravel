# chirper-laravel - Chirper with Admin Dashboard

Simple microblog (“chirps”) built on **Laravel 12 / PHP 8.2**, Blade, Tailwind CSS v4, and Vite. Users can post short updates, edit/delete their own chirps, and admins can manage users and all chirps from a dashboard.

## Features
- Public feed of the latest 50 chirps.
- Authenticated users can create, edit, and delete their own chirps (policy protected).
- Admin dashboard with stats, recent activity, user management, and chirp moderation.
- Dark/light theme toggle (DaisyUI CDN + custom Tailwind tokens).
- Axios-ready bootstrap (for future AJAX use); current UI is server-rendered Blade.

## Stack
- Backend: Laravel 12, PHP 8.2; Pest for testing; Laravel Pint (dev) for linting.
- Frontend: Blade, Tailwind CSS v4 via `@tailwindcss/vite`, DaisyUI CDN, Vite bundler.
- Database: default Laravel stack; chirps table with cascade delete on users.

## Quickstart
```bash
# Clone Repository
git clone https://github.com/dafwa/chirper-laravel.git

# Open Project Folder
cd chirper-laravel

# Setup Project
# (Install all dependencies + setup .env + keygen + migrate database + npm install && build)
composer run setup

# Run Dev servers (PHP + queue listener + Vite)
composer run dev
```

## Manual Setup
```bash
# If you want to setup project manually
npm install && npm run build
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Handy composer scripts:
- `composer setup` - install PHP deps, ensure `.env`, keygen, migrate (force), npm install, build.
- `composer dev` - run `php artisan serve`, queue listener, and `npm run dev` via `concurrently`.
- `composer test` - clear config then `php artisan test` (Pest).

## Admin Access
- Users have an `is_admin` boolean. Admin-only routes use `IsAdmin` middleware (aliased as `admin`).
- Create an admin manually:
  ```php
  php artisan tinker
  \App\Models\User::create([
      'name' => 'Admin',
      'email' => 'admin@example.com',
      'password' => bcrypt('password'),
      'is_admin' => true,
  ]);
  ```

## Key Routes
- Public: `GET /` feed.
- Auth: `POST /chirps`, `GET /chirps/{chirp}/edit`, `PUT /chirps/{chirp}`, `DELETE /chirps/{chirp}`.
- Auth (guest): `GET|POST /register`, `GET|POST /login`; Logout `POST /logout`.
- Admin (auth + admin): `GET /admin` dashboard; `/admin/users` list/delete (admins protected); `/admin/chirps` list/edit/update/delete.

## Notable Implementation Details
- Policy: `ChirpPolicy` restricts update/delete to the owner; other abilities return false (creation is enforced by route auth).
- Chirps `user_id` is nullable; UI handles anonymous avatars gracefully.
- Middleware alias registered in `bootstrap/app.php`.
- Tailwind theme tokens and DaisyUI provide the dark/light toggle; success toasts auto-fade.

## Project Layout Highlights
- `app/Http/Controllers/ChirpController.php` - feed + CRUD.
- `app/Http/Controllers/AdminController.php` - dashboard, user/chirp management.
- `app/Http/Middleware/IsAdmin.php` - admin gate.
- `app/Policies/ChirpPolicy.php` - ownership enforcement.
- `resources/views/` - Blade layouts, auth screens, feed, admin pages.
- `resources/css/app.css` - Tailwind v4 + custom themes; `resources/js/bootstrap.js` - Axios setup.
- `database/migrations/` - chirps table, `is_admin` column, cascade delete update.
- `add_admin_guide.md` - step-by-step notes on how the admin feature was added.

## Testing
- Pest is available (`./vendor/bin/pest` or `composer test`). Current suite is the Laravel examples; add feature tests for chirp CRUD and admin flows as needed.

## Security / Limitations
- No email verification or password reset flows included.
- No rate limiting or spam controls; feed is public, mutations require auth.
- Admin users cannot be deleted via UI; deleting a user cascades their chirps via DB FK.

## License
MIT (Laravel skeleton). See `LICENSE` in the upstream Laravel repo; this project follows the same license. If you modify and redistribute, keep the license notice intact.


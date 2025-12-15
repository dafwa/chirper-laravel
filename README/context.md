# Project Context – belajar-laravel

## Overview
- A Laravel 12 (PHP 8.2+) demo of “Chirper” – a simple microblog feed where authenticated users post short “chirps”, edit/delete their own posts, and admins can moderate users and content.
- Frontend is Blade + Tailwind CSS v4 (via `@tailwindcss/vite`) with DaisyUI served from CDN, bundled through Vite. JavaScript is minimal (Axios bootstrap only).
- Includes an admin dashboard (user/chirp management) gated by a custom `IsAdmin` middleware and `is_admin` flag on users.

## Local Setup & Scripts
- Core steps (`README.md`): clone → `npm install && npm run build` → `composer install` → copy `.env` → `php artisan key:generate` → `php artisan migrate` → `composer run dev`.
- Composer scripts:
  - `composer setup`: install PHP deps, create `.env` if missing, keygen, migrate (force), install npm deps, build assets.
  - `composer dev`: runs `concurrently` → `php artisan serve`, `php artisan queue:listen --tries=1`, and `npm run dev`.
  - `composer test`: clears config then runs `php artisan test` (Pest preinstalled).
- Vite scripts (`package.json`): `npm run dev`, `npm run build`; uses `laravel-vite-plugin` and Tailwind v4.

## Routing Snapshot (`routes/web.php`)
- Public: `GET /` → `ChirpController@index` (list feed).
- Auth required: `POST /chirps`, `GET /chirps/{chirp}/edit`, `PUT /chirps/{chirp}`, `DELETE /chirps/{chirp}`.
- Auth guests: `GET /register`, `POST /register` (invokable controller); `GET /login`, `POST /login` (invokable controller).
- Logout: `POST /logout` (auth).
- Admin (prefix `/admin`, middleware `auth` + `IsAdmin`):
  - Dashboard `GET /admin` (counts + recent users/chirps).
  - Users `GET /admin/users`, delete user `DELETE /admin/users/{user}` (blocked for admins).
  - Chirps `GET /admin/chirps`, edit `GET /admin/chirps/{chirp}/edit`, update `PUT ...`, delete `DELETE ...`.

## Middleware & Policies
- `IsAdmin` middleware aborts 403 unless authenticated user has `is_admin=true`; aliased as `admin` in `bootstrap/app.php`.
- `ChirpPolicy` allows `update`/`delete` only for the owning user; `@can` checks used in chirp component. Other abilities return `false` (view/create/viewAny/restore/forceDelete), so only route middleware controls creation.

## Controllers
- `ChirpController`: lists latest 50 chirps with user eager-loading; store/update validate `message` (required|string|max:255) with friendly errors; uses auth user relation to create; authorize update/delete via policy; redirect with flash success.
- `AdminController`: dashboard aggregates counts and recent activity; paginated user list with chirp counts and delete (prevent deleting admins); chirp list with pagination and edit/delete; edit/update validate message; all responses return admin Blade views.
- Auth controllers (`Auth\Register`, `Login`, `Logout`): invokable; register validates/creates user and logs in; login attempts with remember option and session regeneration; logout invalidates session and token.

## Models & Data
- `User`: fillable `name`, `email`, `password`, `is_admin`; casts `password` (hashed), `is_admin` (bool); relation `chirps()`.
- `Chirp`: fillable `message`; relation `user()` belongsTo.
- Migrations:
  - `create_chirps_table`: `user_id` FK (nullable) with cascade delete, `message`, timestamps.
  - `add_is_admin_to_users_table`: boolean flag default false.
  - `add_cascade_delete_to_chirps_table`: redefines FK to cascade (for existing tables).
  - Standard Laravel cache/jobs/users seeds present.

## Views (Blade)
- `components/layout.blade.php`: global shell with DaisyUI theme toggle, nav showing auth state, optional admin link, success toast, OG/meta, Vite assets, footer art.
- `home.blade.php`: chirp form (auth only via route middleware) + feed rendering `x-chirp`.
- `components/chirp.blade.php`: displays avatar (Laravel cloud), author name, timestamps, edited indicator, message; shows Edit/Delete buttons when policy allows.
- `chirps/edit.blade.php`: user-facing edit form.
- Auth views (`auth/login`, `auth/register`): styled forms with validation feedback.
- Admin views:
  - `admin/index`: stats cards + recent users/chirps.
  - `admin/users`: paginated table with role badge; delete non-admin users with confirmation.
  - `admin/chirps`: paginated list with edit/delete controls.
  - `admin/edit-chirp`: edit form with metadata block.
- `welcome.blade.php` retained from Laravel skeleton (unused in routes).

## Frontend Assets
- `resources/css/app.css`: Tailwind v4 directives with extensive custom theme tokens for dark/light, card/input/button styling, and fade-out animation for success toast.
- `resources/js/bootstrap.js`: registers Axios, sets `X-Requested-With`; `app.js` imports it. No SPA logic; Blade renders server HTML.
- Vite config includes Laravel plugin and Tailwind plugin; inputs `resources/css/app.css`, `resources/js/app.js`.

## Admin Guide Doc
- `add_admin_guide.md` documents how the admin feature was integrated: model changes, migrations, middleware, controller, routes, views, cascade delete, seeding an admin user, and nav link instructions.

## Testing & Quality
- Pest + Laravel test scaffolding present; only example tests shipped.
- No custom linting setup beyond Laravel Pint (dev dependency) and default Tailwind processing.

## Behavioral Notes / Risks
- Chirp policy disallows `view/create` but routes still permit; enforcement depends on route middleware.
- Chirp `user_id` is nullable; UI expects `$chirp->user` may be null and handles anonymous avatars.
- Admin delete user cascades chirps via DB FK; admins themselves cannot be deleted via UI.
- No rate limiting or spam/abuse controls; auth required for create/edit/delete but feed is public.
- Email verification, password reset, and profile management are not implemented.


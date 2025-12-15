# Admin Dashboard Installation Guide

Follow these steps to add the admin dashboard to your Chirper project:

## Step 1: Update User Model
Replace your `app/Models/User.php` with the updated version that includes `is_admin` in the fillable array and casts.
```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin', // Add this line
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean', // Add this line
        ];
    }

    public function chirps(): HasMany
    {
        return $this->hasMany(Chirp::class);
    }
}
```

## Step 2: Create Migration
Create a new migration file in `database/migrations/` with a timestamp filename like:
`2024_xx_xx_xxxxxx_add_is_admin_to_users_table.php`

```bash
php artisan make:migration add_is_admin_to_users_table
```

Copy the migration code provided.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
```

## Step 3: Run Migration
```bash
php artisan migrate
```

## Step 4: Create Middleware
Create the file `app/Http/Middleware/IsAdmin.php` and copy the middleware code.

```bash
php artisan make:middleware IsAdmin
```

`app/Http/Middleware/IsAdmin.php` code:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->is_admin) {
            abort(403, 'Unauthorized action.');
        }
        
        return $next($request);
    }
}
```

## Step 5: Register Middleware (Laravel 12)
Open `bootstrap/app.php` and add the middleware alias inside the `withMiddleware` callback:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\IsAdmin::class,
    ]);
})
```

## Step 6: Create Controller
Create the file `app/Http/Controllers/AdminController.php` and copy the controller code.

```bash
php artisan make:controller AdminController
```

`app/Http/Controllers/AdminController.php` code:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chirp;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalChirps = Chirp::count();
        $recentUsers = User::latest()->take(5)->get();
        $recentChirps = Chirp::with('user')->latest()->take(5)->get();

        return view('admin.index', compact('totalUsers', 'totalChirps', 'recentUsers', 'recentChirps'));
    }

    public function users()
    {
        $users = User::withCount('chirps')->paginate(15);
        return view('admin.users', compact('users'));
    }

    public function destroyUser(User $user)
    {
        if ($user->is_admin) {
            return redirect()->route('admin.users')->with('error', 'Cannot delete admin users.');
        }

        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User and their chirps deleted successfully.');
    }

    public function chirps()
    {
        $chirps = Chirp::with('user')->latest()->paginate(15);
        return view('admin.chirps', compact('chirps'));
    }

    public function editChirp(Chirp $chirp)
    {
        return view('admin.edit-chirp', compact('chirp'));
    }

    public function updateChirp(Request $request, Chirp $chirp)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $chirp->update($validated);

        return redirect()->route('admin.chirps')->with('success', 'Chirp updated successfully.');
    }

    public function destroyChirp(Chirp $chirp)
    {
        $chirp->delete();
        return redirect()->route('admin.chirps')->with('success', 'Chirp deleted successfully.');
    }
}
```

## Step 7: Update Routes
Add the admin routes to your `routes/web.php` file. Add them at the end of the file.

```php
use App\Http\Controllers\AdminController;

// Admin Routes
Route::middleware(['auth', \App\Http\Middleware\IsAdmin::class])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    
    Route::get('/chirps', [AdminController::class, 'chirps'])->name('admin.chirps');
    Route::get('/chirps/{chirp}/edit', [AdminController::class, 'editChirp'])->name('admin.chirps.edit');
    Route::put('/chirps/{chirp}', [AdminController::class, 'updateChirp'])->name('admin.chirps.update');
    Route::delete('/chirps/{chirp}', [AdminController::class, 'destroyChirp'])->name('admin.chirps.destroy');
});
```

## Step 8: Create Views
Create a new folder `resources/views/admin/` and create these files:
- `index.blade.php` (Dashboard)
- `users.blade.php` (User Management)
- `chirps.blade.php` (Chirp Management)
- `edit-chirp.blade.php` (Edit Chirp Form)

Copy the respective view code into each file.

## Step 9: Add Cascade Delete to Migration
Create or update your chirps table migration to include cascade delete:

In your existing chirps migration, update the foreign key:
```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

If you already have the chirps table, create a new migration:
```bash
php artisan make:migration add_cascade_delete_to_chirps_table
```

And add:
```php
public function up(): void
{
    Schema::table('chirps', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->cascadeOnDelete();
    });
}
```

Then run: `php artisan migrate`

## Step 10: Create Admin User (Option A - Using Seeder)
Create the seeder file `database/seeders/AdminUserSeeder.php` and run:
```bash
php artisan db:seed --class=AdminUserSeeder
```

**Default admin credentials:**
- Email: `admin@chirper.com`
- Password: `password`

## Step 10: Create Admin User (Option B - Manual)
Alternatively, use Tinker to create an admin manually:
```bash
php artisan tinker
```

Then run:
```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('your-password'),
    'is_admin' => true
]);
```

## Step 11: Add Navigation Link (Optional)
To add an admin dashboard link to your navigation, edit your layout file (likely `resources/views/layouts/app.blade.php` or navigation component) and add:

```blade
@if(auth()->user()->is_admin)
    <a href="{{ route('admin.dashboard') }}" class="...">
        Admin Dashboard
    </a>
@endif
```

## Step 12: Test!
1. Login with your admin credentials
2. Visit `/admin` to access the dashboard
3. Test user management at `/admin/users`
4. Test chirp management at `/admin/chirps`

## Security Notes
- Only users with `is_admin = true` can access admin routes
- Admin users cannot be deleted from the user management interface
- Deleting a user will automatically delete all their chirps (cascade delete)

## Troubleshooting
- If you get a 403 error, make sure your user has `is_admin` set to `true`
- If views don't load, check that you're using the `<x-app-layout>` component
- If pagination doesn't work, make sure Tailwind is processing the Laravel pagination views
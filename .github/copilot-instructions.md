# XentroCine - AI Coding Agent Instructions

## Project Overview
XentroCine is a cinema booking system migrated from Node.js/Express to **Laravel 12** + **PHP 8.2** with a vanilla JavaScript frontend (no frameworks). It provides movie browsing, seat selection, booking management, and admin capabilities.

## Architecture & Data Flow

### Key Components
1. **Backend**: Laravel API (`/api/*` routes) returning JSON for AJAX consumption
2. **Frontend**: Blade templates + vanilla JS (`resources/js/app-api.js`) with stateful client-side management
3. **Database**: MySQL with custom string IDs (not auto-incrementing) for users, movies, and bookings

### Critical Relationships
- **Bookings** → **BookingItems** (1:many): Each booking contains multiple seat reservations
- **Movies** → **Showtimes** (1:many): Movies have multiple screening times
- **Users** → **Bookings** (1:many): Nullable relationship (allows guest bookings via `user_id` set null on delete)
- **Unique Constraint**: `booking_items` enforces unique seats per session via composite key `(movie_id, booking_date, showtime, cinema, seat_key)`

### Custom ID Generation Pattern
All primary entities use **string IDs with auto-generation**:
```php
// In Model boot() method (see User.php, Movie.php, Booking.php)
$model->id = 'prefix_' . Str::random(6) . substr(base_convert(time(), 10, 36), -4);
// Examples: u_abc123xyz, mv100, bk_def456abc
```
⚠️ **Always set** `public $incrementing = false;` and `protected $keyType = 'string';` in models using this pattern.

### Session-Based Authentication
- Uses Laravel's built-in session auth (not Sanctum/Passport)
- CSRF tokens required for all non-GET requests (handled via `<meta name="csrf-token">`)
- Frontend checks `/api/auth/session` on page load to restore user state

## Development Workflow

### Quick Start Commands
```bash
# Setup from scratch
composer run setup  # Installs deps, copies .env, generates key, migrates, builds assets

# Development (concurrent servers)
composer run dev    # Runs: Laravel server + queue worker + Pail logs + Vite dev server

# Database operations
php artisan migrate:fresh --seed  # Reset DB with sample data (3 movies, 2 users)
php artisan db:seed --class=XentroCineSeeder  # Seed only
```

### Testing
```bash
composer test  # Clears config cache and runs PHPUnit
# Tests located in tests/Feature/ and tests/Unit/
```

### Asset Compilation
- **Dev**: `npm run dev` (Vite hot reload on port 5173)
- **Prod**: `npm run build` (outputs to `public/build/`)
- **Vite config**: Only compiles `resources/css/styles.css` and `resources/js/app-api.js`

## Project-Specific Conventions

### Route Organization (routes/web.php)
```php
// Page routes (return Blade views)
Route::get('/', fn() => view('index'))->name('home');

// API routes (return JSON for AJAX)
Route::prefix('api')->group(function () {
    Route::prefix('auth')->group(function () { /* ... */ });
    Route::prefix('movies')->group(function () { /* ... */ });
});
```
⚠️ **API routes require authentication** - Check `Auth::check()` in controllers and return 401 if unauthorized.

### Controller Response Pattern
```php
// Success response
return response()->json($data);

// Error response (always include 'error' key)
return response()->json(['error' => 'Message'], 401|500);
```

### Frontend State Management (resources/js/app-api.js)
```javascript
// Global state object
const state = {
  currentUser: null,  // Set after session check
  movies: [],
  cart: [],           // Array of booking items before checkout
  bookings: []        // User's completed bookings
};

// All API calls use this helper (auto-adds CSRF token)
async function apiCall(endpoint, options = {}) { /* ... */ }
```

### Seat Booking Logic
- Seats represented as `{row: int, col: int}` → converted to `seat_key` string (e.g., "R3C5")
- Check availability via `POST /api/seats/check` before booking
- `booking_items` table enforces uniqueness per screening session
- Frontend renders 9 rows × 14 columns with aisle after column 7

## Database Seeders
**Default credentials** (after `db:seed`):
- Admin: `admin@xentrocine.local` / `Admin123!`
- Client: `john@example.com` / `password123`

Sample data: 3 movies (Star Quest, The Last Laugh, Hidden Tide) with 4-5 showtimes each.

## Common Gotchas

1. **Custom IDs**: Never use `$model->id++` or expect integer IDs. Always let boot() generate them.
2. **CSRF Protection**: Frontend must include `csrf_token()` in meta tag; `app-api.js` handles header injection.
3. **Blade Layouts**: All views extend `layouts/app.blade.php` which includes nav, auth state, and Vite directives.
4. **Password Hashing**: User model auto-hashes via `'password' => 'hashed'` cast - no manual `Hash::make()` needed.
5. **Eloquent Relationships**: Use string foreign keys (`'user_id', 'id'`) explicitly when non-standard.

## File Locations for Common Tasks

### Adding a new feature
- **Route**: `routes/web.php` (API under `/api` prefix)
- **Controller**: `app/Http/Controllers/` (follows Resource controller pattern)
- **Model**: `app/Models/` (remember custom ID setup)
- **View**: `resources/views/` (extends `layouts/app.blade.php`)
- **Frontend JS**: `resources/js/app-api.js` (monolithic, consider modularizing)

### Modifying database schema
- **Migration**: `database/migrations/` (prefix: `2025_10_30_*`)
- **Seeder**: `database/seeders/XentroCineSeeder.php`

### Styling
- **Source**: `resources/css/styles.css` (compiled by Vite)
- **Public**: `public/css/styles.css` (for non-Vite assets)

## Integration Points

- **No external APIs** currently integrated
- **No queue workers** configured (but Laravel queue support ready)
- **No email service** configured (Laravel mail config present in `config/mail.php`)
- **No payment gateway** - booking status defaults to "Paid" immediately

## Next Steps for Scaling

- Extract frontend state management from monolithic `app-api.js` into modules
- Add API rate limiting (currently disabled)
- Implement actual payment integration (GCash, PayPal, Stripe)
- Add email notifications for bookings (Laravel mail ready)
- Consider Vue/React for complex UI interactions (seat map, real-time updates)

# XentroCine - AI Coding Agent Instructions

## Project Overview
XentroCine is a cinema booking system migrated from Node.js/Express to **Laravel 12** + **PHP 8.2** with vanilla JavaScript frontend. Core features: movie browsing, real-time seat selection, e-ticket system with QR codes, email verification, staff portal with camera-based QR scanning, and walk-in booking support.

## Architecture & Data Flow

### Key Components
1. **Backend**: Laravel API (`/api/*` routes) returning JSON for AJAX consumption
2. **Frontend**: Blade templates + vanilla JS (`resources/js/app-api.js`) with stateful client-side management
3. **Database**: MySQL with custom string IDs (not auto-incrementing) for users, movies, and bookings

### Critical Relationships
- **Bookings** → **BookingItems** (1:many): Each booking contains multiple seat reservations
- **Movies** → **Showtimes** (1:many): Movies have multiple screening times
- **Users** → **Bookings** (1:many): Nullable relationship (allows guest bookings via `user_id` set null on delete)
- **Bookings** → **Users** (checked_in_by): Tracks which staff/admin scanned the QR code
- **Unique Constraints**: 
  - `booking_items.unique_seat_session`: Prevents double-booking same seat per screening
  - `bookings.et_qr_token`: Unique token for e-ticket QR codes

### E-Ticket & QR System Architecture
**Problem Solved**: Reduce queue time at peak by enabling online pre-booking with single-scan entry (eliminates separate cashier queue).

**Data Flow**:
1. Customer books online → Backend generates `et_qr_token` (40-char random string with "et_" prefix)
2. `BookingConfirmation` mailable sends email with embedded QR code (base64 PNG via `simplesoftwareio/simple-qrcode`)
3. Customer arrives → Staff scans QR via `/staff` portal (uses `html5-qrcode` for browser camera access)
4. **Paid bookings (digital/GCash)**: First scan shows details; staff must tap "Verify Entry" to check-in (explicit confirmation)
5. **Pending (cash) bookings**: Staff clicks "Accept Cash & Check-in" → atomically updates `status='Paid'` + checks in

**Key Fields** (`bookings` table):
- `payment_method`: 'cash' | 'digital' (determines initial status)
- `et_qr_token`: Unique entry token (indexed)
- `entry_scanned_at`: Check-in timestamp (null until scanned)
- `checked_in_by`: Foreign key to `users.id` (staff/admin who scanned)
- `status`: 'Pending' | 'Paid' | 'Cancelled'

**Idempotency**: Re-scanning returns 409 "Already checked in" without modifying data. The scanner pauses on decode to prevent duplicate reads; staff resumes with a "Scan Next" action.

### Custom ID Generation Pattern
All primary entities use **string IDs with auto-generation**:
```php
// In Model boot() method (see User.php, Movie.php, Booking.php)
$model->id = 'prefix_' . Str::random(6) . substr(base_convert(time(), 10, 36), -4);
// Examples: u_abc123xyz, mv100, bk_def456abc
```
**Always set** `public $incrementing = false;` and `protected $keyType = 'string';` in models using this pattern.

### Session-Based Authentication
- Uses Laravel's built-in session auth (not Sanctum/Passport)
- CSRF tokens required for all non-GET requests (handled via `<meta name="csrf-token">`)
- Frontend checks `/api/auth/session` on page load to restore user state
- **Email Verification**: Users implement `MustVerifyEmail`; booking blocked until `email_verified_at` is set
- **Roles**: 'client' | 'admin' | 'staff' (staff can scan QR + create walk-in bookings; admin can also manage movies)

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
- **Dev (desktop/HMR)**: `npm run dev` (Vite hot reload on port 5173)
- **Cloudflare/mobile (no HMR)**: `npm run dev:cf` (disables `public/hot`, builds in watch mode)
- **Prod**: `npm run build` (outputs to `public/build/`)
- **Vite config**: Only compiles `resources/css/styles.css` and `resources/js/app-api.js`

### Cloudflare Tunnel (Mobile testing over HTTPS)
We standardize on Cloudflare for mobile testing.

```bash
# Start Laravel
php artisan serve

# Build assets without HMR
npm run dev:cf

# Expose with a tunnel (copy the URL)
cloudflared tunnel --url http://localhost:8000
```

Set the generated URL in `.env` and clear config:

```env
APP_URL=https://<your-tunnel>.trycloudflare.com
```

```bash
php artisan config:clear
```

Notes:
- Our CORS config allows `*.trycloudflare.com` and also uses `APP_URL`.
- Avoid Vite hot while using Cloudflare; use `npm run dev:cf`.

## Mac Development Setup (One-Time)

### Prerequisites
If switching from Windows to Mac, you'll need to set up the PHP environment and dependencies.

#### 1. Install Homebrew (if not installed)
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

#### 2. Install PHP 8.2+
```bash
brew install php@8.2
brew link php@8.2 --force --overwrite

# Verify installation
php -v  # Should show PHP 8.2.x
```

#### 3. Install Composer
```bash
brew install composer

# Or download directly:
# curl -sS https://getcomposer.org/installer | php
# mv composer.phar /usr/local/bin/composer
```

#### 4. Install MySQL/MariaDB
```bash
# Option A: MySQL
brew install mysql
brew services start mysql

# Option B: MariaDB (lighter alternative)
brew install mariadb
brew services start mariadb

# Secure installation (set root password)
mysql_secure_installation
```

#### 5. Install Node.js & npm
```bash
brew install node

# Verify
node -v
npm -v
```

#### 6. Install Imagick Extension (Critical for QR Code PNG Generation)
**This is the key difference from Windows - Mac installation is much simpler:**

```bash
# Install ImageMagick library first
brew install imagemagick

# Install the PHP extension via PECL
pecl install imagick

# Verify installation
php -m | grep imagick  # Should show "imagick"

# If not showing, add to php.ini:
echo "extension=imagick.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")

# Restart PHP-FPM (if using)
brew services restart php@8.2
```

**Troubleshooting Imagick on Mac:**
- If `pecl install imagick` fails, check Xcode Command Line Tools:
  ```bash
  xcode-select --install
  ```
- If brew's ImageMagick conflicts, specify the path:
  ```bash
  export PKG_CONFIG_PATH="/usr/local/opt/imagemagick@6/lib/pkgconfig"
  pecl install imagick
  ```
- **Fallback**: The project already has SVG QR fallback; if Imagick fails, emails still work with SVG codes.

#### 7. Clone & Setup Project
```bash
# Clone repository
git clone <repo-url> xentrocine
cd xentrocine

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure .env for Mac
# Update database credentials:
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=xentrocine_db
# DB_USERNAME=root
# DB_PASSWORD=<your-mysql-password>

# Create database
mysql -u root -p -e "CREATE DATABASE xentrocine_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations with seed data
php artisan migrate:fresh --seed

# Build frontend assets
npm run build
```

#### 8. Browser Camera Permissions (for QR Scanning)
**Mac-specific considerations:**

- **Safari**: Requires HTTPS or localhost; may prompt for camera access
- **Chrome/Firefox**: Works on `http://localhost` without HTTPS
- **System Preferences**: Ensure browser has camera permissions
  1. Go to System Preferences → Security & Privacy → Privacy → Camera
  2. Check your browser (Chrome, Firefox, Safari) in the list
  3. If not listed, launch the browser and visit `/staff` to trigger permission prompt

**Testing the camera scanner:**
```bash
# Start development server
php artisan serve  # Runs on http://127.0.0.1:8000

# Visit staff portal
# Open http://127.0.0.1:8000/staff in Chrome or Firefox
# Allow camera access when prompted
```

**Tips:**
- Use **Chrome** or **Firefox** for best `html5-qrcode` compatibility
- Safari may require additional permissions; prefer Chrome for development
- If camera doesn't start, check DevTools console for errors
- Fallback: Use manual token entry field on staff portal

#### 9. Development Workflow on Mac
```bash
# Option A: Single command (recommended)
composer run dev
# Starts: Laravel server + queue worker + logs + Vite hot reload

# Option B: Separate terminals
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev

# Terminal 3 (optional): Queue worker
php artisan queue:listen

# Terminal 4 (optional): Logs
php artisan pail
```

#### 10. Verify Everything Works
```bash
# Check PHP extensions
php -m | grep -E 'pdo_mysql|imagick|gd'
# Should show: pdo_mysql, imagick (or gd)

# Check Composer packages
composer show | grep simple-qrcode

# Check npm packages
npm list html5-qrcode

# Test email (opens Mailtrap)
# 1. Create a booking with GCash
# 2. Check Mailtrap inbox for e-ticket with QR code
# 3. QR should be PNG if imagick loaded, SVG otherwise

# Test QR scanner
# 1. Visit http://127.0.0.1:8000/staff
# 2. Log in as staff@staff / St@ff_123
# 3. Allow camera access
# 4. Scan a QR code from /qr-test page or Mailtrap email
```

### Mac vs Windows Key Differences

| Aspect | Windows (XAMPP) | Mac (Homebrew) |
|--------|----------------|----------------|
| PHP Installation | Pre-bundled in XAMPP | `brew install php@8.2` |
| Imagick Install | Manual DLL placement + php.ini | `pecl install imagick` (auto-configures) |
| MySQL | XAMPP Control Panel | `brew services start mysql` |
| File Permissions | Generally permissive | May need `chmod 755 storage bootstrap/cache` |
| Line Endings | CRLF (may cause issues) | LF (Git autocrlf should handle) |
| Camera Access | Chrome/Edge work well | Prefer Chrome; Safari has stricter rules |
| Paths | Backslashes (handled by Laravel) | Forward slashes (native) |

### Post-Setup Sync from Windows
If you already have the project on Windows:

```bash
# Pull latest changes (after committing from Windows)
git pull origin main

# Reinstall dependencies (lock files should match)
composer install
npm install

# Rebuild assets (Mac may have different Vite cache)
npm run build

# Migrate & seed (if schema changed)
php artisan migrate:fresh --seed

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**Important:** Database is not synced automatically. If you need the exact Windows data:
1. Export from Windows: `php artisan db:seed` or export SQL via phpMyAdmin
2. Import to Mac: `mysql -u root -p xentrocine_db < backup.sql`

For development, fresh seed data is usually sufficient.

## Project-Specific Conventions

### Route Organization (routes/web.php)
```php
// Page routes (return Blade views)
Route::get('/', fn() => view('index'))->name('home');

// API routes (return JSON for AJAX)
Route::prefix('api')->group(function () {
    Route::prefix('auth')->group(function () { /* ... */ });
    Route::prefix('movies')->group(function () { /* ... */ });
    Route::prefix('bookings')->group(function () {
        Route::post('/scan', [BookingController::class, 'scan']); // QR verification
    });
});

// Email verification (signed route outside API prefix)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])->name('verification.verify');
```
⚠️ **API routes require authentication** - Check `Auth::check()` in controllers and return 401 if unauthorized.
⚠️ **Staff routes require role check** - `in_array($user->role, ['admin', 'staff'])`

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
- Admin: `admin@admin` / `@dmin_123`
- Staff: `staff@staff` / `St@ff_123`
- Client: `client@client` / `P@ssword_123`

Sample data: 3 movies (Star Quest [PG], The Last Laugh [R-13], Hidden Tide [G]) with 4-5 showtimes each.

## Common Gotchas

1. **Custom IDs**: Never use `$model->id++` or expect integer IDs. Always let boot() generate them.
2. **CSRF Protection**: Frontend must include `csrf_token()` in meta tag; `app-api.js` handles header injection.
3. **Blade Layouts**: All views extend `layouts/app.blade.php` which includes nav, auth state, and Vite directives.
4. **Password Hashing**: User model auto-hashes via `'password' => 'hashed'` cast - no manual `Hash::make()` needed.
5. **Eloquent Relationships**: Use string foreign keys (`'user_id', 'id'`) explicitly when non-standard.
6. **Cloudflare Tunnels**: When exposing to mobile, do not use Vite hot (no `public/hot`). Run `npm run dev:cf` and set `APP_URL` to your tunnel URL.

## File Locations for Common Tasks

### Adding a new feature
- **Route**: `routes/web.php` (API under `/api` prefix)
- **Controller**: `app/Http/Controllers/` (follows Resource controller pattern)
- **Model**: `app/Models/` (remember custom ID setup)
- **View**: `resources/views/` (extends `layouts/app.blade.php`)
- **Frontend JS**: `resources/js/app-api.js` (monolithic, consider modularizing)

### Modifying database schema
- **Migration**: `database/migrations/` (prefix: `2025_10_30_*`, `2025_11_02_*`)
- **Seeder**: `database/seeders/XentroCineSeeder.php`

### E-Ticket System
- **Mailable**: `app/Mail/BookingConfirmation.php` (sends QR code email)
- **Email Template**: `resources/views/emails/booking-confirmation.blade.php`
- **Staff Portal**: `resources/views/staff.blade.php` (QR scanner + walk-in booking)
- **QR Test Page**: `resources/views/qr-test.blade.php` (dev tool for generating test QR codes)

### Styling
- **Source**: `resources/css/styles.css` (compiled by Vite)
- **Public**: `public/css/styles.css` (for non-Vite assets)

## Integration Points

- **Email Service**: Mailtrap configured for development (`.env` credentials); uses Laravel Mail with `BookingConfirmation` mailable
- **QR Code Generation**: `simplesoftwareio/simple-qrcode` package (server-side, embedded in emails as base64 PNG)
- **QR Code Scanning**: `html5-qrcode` npm package (client-side, uses device camera via browser)
- **Timezone**: Asia/Manila (server-side; frontend uses server-provided dates)
- **Payment Gateway**: None (currently simulated; `payment_method` field ready for integration)
- **Queue Workers**: Available but not required (emails sent synchronously)

## Next Steps for Scaling

- Extract frontend state management from monolithic `app-api.js` into modules
- Add API rate limiting (currently disabled)
- Implement actual payment integration (GCash, PayPal, Stripe)
- Consider Vue/React for complex UI interactions (seat map, real-time updates)
- Add analytics dashboard (pre-booking vs walk-in ratio, average check-in time)

## Testing & Development Tools

- **QR Test Page**: `/qr-test` - Generate and display QR codes for any booking (no hardware needed)
- **Testing Guide**: `TESTING_GUIDE.md` - Complete workflows, demo scripts, troubleshooting
- **Email Testing**: Mailtrap inbox shows all sent e-tickets with embedded QR codes
- **Camera Scanner**: Staff portal uses device camera (laptop webcam or phone) via `html5-qrcode`
- **Manual Token Entry**: Fallback input for QR verification when camera unavailable

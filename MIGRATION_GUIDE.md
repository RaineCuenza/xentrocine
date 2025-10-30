# XentroCine - Node.js to Laravel Migration Guide

## ✅ Migration Complete!

Your cinema booking system has been successfully migrated from Node.js/Express to Laravel 11 with PHP.

---

## 🗂️ What Was Migrated

### Database Schema
- **Users table** - Customer accounts with role-based access (client/admin)
- **Movies table** - Movie catalog with titles, descriptions, posters, prices
- **Showtimes table** - Movie screening schedules
- **Bookings table** - Customer reservations with status tracking
- **Booking Items table** - Individual seat reservations per booking
- **Laravel Framework tables** - Sessions, cache, jobs, password resets

### Backend Features
- ✅ Session-based authentication (matching Node.js express-session)
- ✅ Bcrypt password hashing
- ✅ Role-based authorization (client/admin)
- ✅ Movie CRUD operations
- ✅ Seat availability checking
- ✅ Booking creation with transaction safety
- ✅ Booking cancellation
- ✅ Admin dashboard with KPIs

### Frontend Features
- ✅ Blade templating with layout inheritance
- ✅ Responsive CSS (exact copy from Node.js)
- ✅ JavaScript with AJAX calls to Laravel API
- ✅ CSRF token protection
- ✅ Session persistence

---

## 🚀 Setup Instructions

### 1. Environment Configuration
Your `.env` file should already have:
```env
DB_CONNECTION=mysql
DB_DATABASE=xentrocine_db
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### 2. Database Setup
The migrations have been run with seed data:
```bash
php artisan migrate:fresh --seed
```

**Seeded Data:**
- **Admin User:** admin@admin.com / admin123
- **Client Users:** 
  - client1@example.com / password123
  - client2@example.com / password123
- **3 Movies** with multiple showtimes each

### 3. Start the Development Server

**You need TWO terminals running:**

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```
Visit: `http://localhost:8000`

**Terminal 2 - Vite Dev Server (for asset compilation):**
```bash
npm run dev
```

This enables:
- ✅ **Hot Module Reloading** - CSS/JS changes update instantly
- ✅ **Asset versioning** - Automatic cache-busting
- ✅ **Minification** - Optimized production builds

**For production deployment:**
```bash
npm run build
php artisan serve
```

---

## 🧪 Testing Guide

### Test 1: User Registration
1. Navigate to `http://localhost:8000/login`
2. Click the **"Sign Up"** tab
3. Register a new account:
   - Email: `test@example.com`
   - Password: `password123`
   - Password Confirmation: `password123`
4. ✅ Should automatically log in and redirect to home

### Test 2: User Login
1. Go to `http://localhost:8000/login`
2. Login with seeded client:
   - Email: `client1@example.com`
   - Password: `password123`
3. ✅ Should redirect to home with "Welcome, client1"

### Test 3: Browse Movies
1. Visit `http://localhost:8000` (home page)
2. ✅ Should see 3 movies with posters, prices, ratings
3. Click "Book Seats" on any movie
4. ✅ Should redirect to booking page

### Test 4: Make a Booking (Requires Login)
1. Login as a client user
2. Click "Book Seats" on a movie
3. Select a showtime from dropdown
4. ✅ Seat map should load (10x10 grid)
5. Click 2-3 seats to select them
6. ✅ Seats should turn green, cart should update with total
7. Click "Checkout"
8. ✅ Success message should appear
9. ✅ Booking should show in "My Bookings" section

### Test 5: View My Bookings
1. While logged in, scroll to "My Bookings" on booking page
2. ✅ Should see your booking with:
   - Movie title
   - Showtime date/time
   - Seat numbers
   - Total price
   - Status (Paid/Pending/Cancelled)

### Test 6: Cancel a Booking
1. In "My Bookings", click "Cancel" on a booking
2. ✅ Status should change to "Cancelled"
3. ✅ Seats should become available for rebooking

### Test 7: Admin Access
1. Logout if logged in
2. Login with admin credentials:
   - Email: `admin@admin.com`
   - Password: `admin123`
3. ✅ Should see "Admin Dashboard" in navigation
4. Click "Admin Dashboard"
5. ✅ Should see KPI cards:
   - Total Movies
   - Total Users
   - Total Bookings
   - Total Revenue
6. ✅ Should see movies table with edit/delete buttons

### Test 8: Admin Movie Management
1. On admin dashboard, click "Edit" on a movie
2. ✅ Form should populate with movie data
3. Update the title or price
4. Click "Update Movie"
5. ✅ Movie should update in the list

### Test 9: Admin Add Movie
1. On admin dashboard, fill out "Add New Movie" form
2. Enter:
   - Title: `New Movie`
   - Price: `400`
   - Rating: `4.0`
   - Poster: `/images/movie.jpg` (local) or `https://via.placeholder.com/300x450` (URL)
   - Showtimes: `14:00,16:30,19:00`
3. Click "Add Movie"
4. ✅ New movie should appear in the list

**Note:** Movie posters can be:
- Local images: `/images/yourimage.jpg` (stored in `public/images/`)
- External URLs: `https://example.com/poster.jpg`

### Test 10: Admin Delete Movie
1. Click "Delete" on a movie
2. Confirm the deletion
3. ✅ Movie should be removed from the list

### Test 11: Session Persistence
1. Login as any user
2. Navigate to different pages (home → booking → about)
3. ✅ Should stay logged in across all pages
4. Close browser and reopen within 120 minutes
5. ✅ Should still be logged in (session persists)

### Test 12: Seat Availability
1. Login as client1@example.com
2. Book seats 1A, 1B, 1C for a showtime
3. Logout and login as client2@example.com
4. Try to book the same showtime
5. ✅ Seats 1A, 1B, 1C should show as "Booked" (red)
6. ✅ Cannot select those seats

### Test 13: Logout
1. While logged in, click "Logout" in navigation
2. ✅ Should redirect to login page
3. ✅ Try accessing `/admin`
4. ✅ Should redirect to login (unauthorized)

---

## 📁 Project Structure

```
xentrocine/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php         # Signup, login, logout, session
│   │   ├── MovieController.php        # Movie CRUD operations
│   │   ├── BookingController.php      # Create, view, cancel bookings
│   │   └── SeatController.php         # Check seat availability
│   └── Models/
│       ├── User.php                    # User model with role enum
│       ├── Movie.php                   # Movie model
│       ├── Showtime.php                # Showtime model
│       ├── Booking.php                 # Booking model with status helpers
│       └── BookingItem.php             # Booking item model
├── database/
│   ├── migrations/
│   │   ├── 2025_10_30_113205_create_xentrocine_tables.php
│   │   └── 2025_10_30_113452_create_laravel_default_tables.php
│   └── seeders/
│       └── XentroCineSeeder.php        # Admin + sample data
├── routes/
│   └── web.php                         # All routes (pages + API)
├── resources/
│   ├── css/
│   │   └── styles.css                  # Source CSS (compiled by Vite)
│   ├── js/
│   │   └── app-api.js                  # Source JavaScript (compiled by Vite)
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php           # Master layout
│       ├── index.blade.php             # Home page
│       ├── booking.blade.php           # Booking page
│       ├── login.blade.php             # Login/signup page
│       ├── admin.blade.php             # Admin dashboard
│       └── about.blade.php             # About page
└── public/
    ├── images/                         # Movie poster images
    │   ├── starquest.jpg               # ✅ Local image for Star Quest
    │   ├── thelastlaugh.jpg            # ✅ Local image for The Last Laugh
    │   └── hiddentide.jpg              # ✅ Local image for Hidden Tide
    └── build/                          # Compiled assets (auto-generated)
        ├── manifest.json               # Asset manifest for Laravel
        └── assets/
            ├── styles-[hash].css       # Compiled & minified CSS
            └── app-api-[hash].js       # Compiled & minified JS
```

### Movie Posters

**Local Images (Recommended):**
1. Place image files in `public/images/`
2. Reference as: `/images/yourimage.jpg`
3. Served directly by web server (fast)

**External URLs:**
1. Use full URL: `https://example.com/poster.jpg`
2. Requires internet connection
3. May be slower to load

**Example:**
```php
// In seeder or admin form
'poster' => '/images/starquest.jpg'     // ✅ Local
'poster' => 'https://via.placeholder.com/300x450'  // ✅ External
```

### Asset Pipeline (Vite)

**Development Mode:** `npm run dev`
- Files in `resources/css/` and `resources/js/` are served via Vite dev server
- Changes automatically reload in browser (Hot Module Replacement)
- Source maps enabled for debugging

**Production Mode:** `npm run build`
- Files compiled to `public/build/assets/` with version hashes
- Minified and optimized for performance
- Laravel automatically uses hashed filenames via `@vite()` directive

---

## 🔑 Key Differences from Node.js

| Feature | Node.js | Laravel |
|---------|---------|---------|
| **Routing** | `app.get('/api/movies')` | `Route::get('/api/movies')` in web.php |
| **Sessions** | `express-session` + `express-mysql-session` | Laravel session (database driver) |
| **Auth** | Manual `req.session.userId` | `Auth::login()` / `Auth::user()` |
| **Database** | Raw SQL queries with `mysql2` | Eloquent ORM models |
| **Templates** | EJS with `<%=` syntax | Blade with `{{ }}` syntax |
| **CSRF** | None (vulnerable) | Built-in CSRF protection |
| **Middleware** | Custom functions | Laravel middleware stack |

---

## 🛠️ Common Commands

```bash
# Development (run both in separate terminals)
php artisan serve          # Terminal 1: Laravel server
npm run dev                # Terminal 2: Vite dev server with hot reload

# Production build
npm run build              # Compile and minify assets
php artisan serve          # Start server with compiled assets

# Reset database with fresh seed data
php artisan migrate:fresh --seed

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check routes
php artisan route:list

# Run tests (if you create them)
php artisan test
```

---

## 🐛 Troubleshooting

### Issue: "CSRF token mismatch"
**Solution:** Clear browser cookies and cache, then refresh page.

### Issue: "Session not persisting"
**Solution:** Verify `.env` has `SESSION_DRIVER=database` and run `php artisan migrate` to ensure sessions table exists.

### Issue: "Class 'App\Models\User' not found"
**Solution:** Run `composer dump-autoload`

### Issue: "Access denied for user 'root'@'localhost'"
**Solution:** Update `.env` with correct database credentials:
```env
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Issue: "Vite manifest not found"
**Solution:** Run `npm run build` to generate compiled assets, or run `npm run dev` in a separate terminal.

### Issue: CSS/JS not loading or showing 404
**Solution:** 
1. Make sure Vite dev server is running: `npm run dev`
2. For production, build assets first: `npm run build`
3. Check `public/build/manifest.json` exists

### Issue: Changes to CSS/JS not updating
**Solution:** 
- In development: Make sure `npm run dev` is running
- In production: Run `npm run build` after changes
- Clear browser cache (Ctrl+Shift+R or Ctrl+F5)

### Issue: Seats not updating in real-time
**Solution:** This is expected behavior. The app uses AJAX but doesn't have WebSockets. Refresh the page to see latest seat availability.

---

## ✨ Features Preserved from Node.js

✅ Session-based authentication (no JWT tokens)
✅ Role-based access control (client/admin)
✅ Seat booking with conflict detection
✅ Transaction safety for bookings
✅ Admin dashboard with statistics
✅ Movie CRUD operations
✅ User registration and login
✅ Password hashing with bcrypt
✅ All original styling and UI

---

## 🎯 Next Steps (Optional Enhancements)

1. **Real-time updates** - Implement Laravel Echo + Pusher for live seat availability
2. **Payment integration** - Add Stripe/PayPal for actual payments
3. **Email notifications** - Send booking confirmations via email
4. **Password reset** - Implement forgot password functionality
5. **Admin analytics** - Add charts for revenue/booking trends
6. **Movie search** - Add search and filter functionality
7. **User profiles** - Allow users to update their information
8. **Booking history** - Pagination for large booking lists
9. **API documentation** - Generate Swagger/OpenAPI docs
10. **Unit tests** - Add PHPUnit tests for controllers/models

---

## 📝 Notes

- All primary keys use custom string IDs (`u_*`, `mv_*`, `bk_*`)
- Sessions expire after 120 minutes of inactivity
- Database uses MySQL (same as Node.js version)
- All API endpoints return JSON for AJAX consumption
- Page routes return Blade views for server-side rendering
- CSRF protection is automatic via meta tag + JavaScript

---

**Migration completed successfully! 🎉**

For questions or issues, check Laravel documentation: https://laravel.com/docs/11.x

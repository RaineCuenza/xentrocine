# XentroCine - Cinema Booking System

A cinema booking system built with Laravel 11 and PHP. This project was migrated from a Node.js/Express application to Laravel, providing a modern, secure, and scalable solution for managing movie screenings, bookings, and customer accounts.

## 📋 What This System Does

XentroCine is a web-based cinema management and booking platform that includes:

- **User Management**: Customer registration and authentication with role-based access (client/admin)
- **Movie Catalog**: Browse available movies with details, posters, and pricing
- **Showtime Scheduling**: View movie screening times and availability
- **Seat Booking**: Real-time seat selection and booking with conflict prevention
- **Booking Management**: View, track, and cancel bookings
- **Admin Dashboard**: Administrative interface for managing movies, showtimes, and viewing booking statistics
- **Session-Based Authentication**: Secure login system with bcrypt password hashing

## 🛠️ Requirements

Before setting up this project, ensure you have the following installed:

- **PHP** >= 8.2
- **Composer** (PHP dependency manager)
- **Node.js** >= 18.x and **npm** (for frontend asset compilation)
- **MySQL** >= 8.0 or **MariaDB** >= 10.3
- **Web Server** (Apache/Nginx) or use Laravel's built-in server

## 🚀 Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/RaineCuenza/xentrocine.git
cd xentrocine
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit the `.env` file and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xentrocine_db
DB_USERNAME=root
DB_PASSWORD=your_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Create Database

Create a MySQL database named `xentrocine_db`:

```sql
CREATE DATABASE xentrocine_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or using MySQL command line:

```bash
mysql -u root -p -e "CREATE DATABASE xentrocine_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 7. Run Database Migrations

```bash
php artisan migrate
```

This will create all necessary tables:
- users
- movies
- showtimes
- bookings
- booking_items
- sessions (for authentication)
- cache, jobs, password_resets

### 8. Seed the Database (Optional)

To populate the database with sample data:

```bash
php artisan db:seed
```

This will create:
- Sample admin and client users
- Sample movies with posters
- Sample showtimes

### 9. Build Frontend Assets

For development with hot reload:

```bash
npm run dev
```

For production (minified and optimized):

```bash
npm run build
```

### 10. Start the Development Server

```bash
php artisan serve
```

The application will be available at: `http://localhost:8000`

## 📁 Project Structure

```
xentrocine/
├── app/
│   ├── Http/Controllers/    # Application controllers
│   └── Models/               # Eloquent models
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── public/                   # Web root (compiled assets)
├── resources/
│   ├── css/                  # Source CSS files
│   ├── js/                   # Source JavaScript files
│   └── views/                # Blade templates
└── routes/
    └── web.php               # Application routes
```

## 🔐 Default Credentials (After Seeding)

**Admin Account:**
- Email: `admin@xentrocine.com`
- Password: `admin123`

**Client Account:**
- Email: `client@xentrocine.com`
- Password: `client123`

## 🔧 Common Commands

```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration (drop all tables and re-migrate)
php artisan migrate:fresh --seed
```

## 🐛 Troubleshooting

**Issue: "Permission denied" errors**
```bash
# Give write permissions to storage and cache folders
chmod -R 775 storage bootstrap/cache
```

**Issue: Assets not loading**
```bash
# Make sure you've built the assets
npm run build
```

**Issue: Database connection failed**
- Verify your `.env` database credentials
- Ensure MySQL service is running
- Check if the database exists


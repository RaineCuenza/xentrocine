# XentroCine - Movie Booking System

## Setup Guide

Follow these steps to set up and run the project on your local machine.

---

## Step 1: Install Required Software

### 1.1 Install Node.js

1. Download Node.js from: **https://nodejs.org/**
2. Download the **LTS version** (recommended)
3. Run the installer with default settings
4. Restart your computer after installation

**Verify installation:**
```powershell
node --version
npm --version
```

### 1.2 Install XAMPP

1. Download XAMPP from: **https://www.apachefriends.org/**
2. Run the installer
3. Install to default location: `C:\xampp`
4. You only need **MySQL** component (Apache is optional)

---

## Step 2: Setup Database

### 2.1 Start XAMPP

1. Open **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`)
2. Click **Start** next to **MySQL**
3. Wait until status turns green
4. Click **Start** next to **Apache** (for phpMyAdmin)

### 2.2 Create Database

1. Open your browser and go to: **http://localhost/phpmyadmin**
2. Click **"New"** in the left sidebar
3. Database name: `xentrocine_db`
4. Collation: `utf8mb4_general_ci`
5. Click **"Create"**

### 2.3 Import Database Structure

1. Select the `xentrocine_db` database you just created
2. Click the **"SQL"** tab at the top
3. Open the file `database/schema.sql` from the project folder in a text editor
4. Copy **all** the contents
5. Paste into the SQL text area in phpMyAdmin
6. Click **"Go"** to execute

### 2.4 Import Sample Data

1. Still in the **"SQL"** tab
2. Open the file `database/seed.sql` from the project folder
3. Copy **all** the contents
4. Paste into the SQL text area in phpMyAdmin
5. Click **"Go"** to execute

### 2.5 Verify Database

1. You should see 6 tables in the left sidebar:
   - users
   - movies
   - showtimes
   - bookings
   - booking_items
   - sessions
2. Click on **users** table → you should see 3 users (1 admin, 2 clients)

---

## Step 3: Install Project Dependencies

1. Open **PowerShell** or **Command Prompt**
2. Navigate to the project folder:
   ```powershell
   cd "path\to\movie_booking_system"
   ```
3. Install all required packages:
   ```powershell
   npm install
   ```
4. Wait for installation to complete (may take 1-2 minutes)

**Expected output:**
```
added 179 packages in 15s
```

---

## Step 3.5: Create your .env file (required)

Before you start the server you must create a `.env` file from the included example. This file contains database and server settings and is ignored by Git so your secrets won't be committed.

1. From the project root, copy the example file to `.env`:
```powershell
Copy-Item .env.example .env
```

2. Edit `.env` and update the values below to match your environment (open it in your editor):

- DB_HOST: usually `localhost` for XAMPP
- DB_USER: MySQL username (default is `root` for XAMPP)
- DB_PASSWORD: MySQL password for the above user (leave empty if you use no password for root)
- DB_NAME: `xentrocine_db` (the database you created/imported)
- DB_PORT: usually `3306` for MySQL
- PORT: the server port (default `3000`)
- NODE_ENV: `development` (or `production`)
- SESSION_SECRET: replace `your-secret-key-change-this-in-production` with a secure random string

Example minimal `.env` (do NOT commit this file):
```text
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_mysql_password_here
DB_NAME=xentrocine_db
DB_PORT=3306

PORT=3000
NODE_ENV=development

SESSION_SECRET=6f2e9c2b1a7f4e1c8d9b3a0f5c7d8e1a
```

3. (Optional) Generate a secure SESSION_SECRET using Node.js (recommended):
```powershell
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```
Copy the output and paste it into `SESSION_SECRET` in `.env`.

Notes:
- The project already includes `.env` in `.gitignore`, so the real `.env` will not be tracked by Git.
- If you change `PORT` make sure to open that port in your browser (e.g., `http://localhost:3001` if you set `PORT=3001`).


## ▶️ Step 4: Start the Application

### 4.1 Make Sure XAMPP MySQL is Running

- Check XAMPP Control Panel
- MySQL should have **green** status

### 4.2 Start the Server

In PowerShell/Command Prompt (in project folder):
```powershell
npm start
```

**You should see:**
```
 Database connected successfully

🎬 ================================ 🎬
   XentroCine Server Running!
🎬 ================================ 🎬

   🌐 Server: http://localhost:3000
   📊 API:    http://localhost:3000/api
   🏥 Health: http://localhost:3000/api/health

   Press Ctrl+C to stop
```

### 4.3 Open the Application

1. Open your web browser
2. Go to: **http://localhost:3000**
3. You should see the XentroCine homepage

---

## Login Credentials

Use these credentials to test the application:

**Admin Account:**
- Email: `admin@xentrocine.local`
- Password: `Admin123!`

**Client Account:**
- Email: `john@example.com`
- Password: `password123`

---

## Verify Everything Works

### Test 1: Homepage
- Visit: http://localhost:3000
- Should see movie cards with "Star Quest", "The Last Laugh", "Hidden Tide"

### Test 2: Login
- Click "Log in / Sign up"
- Use admin credentials above
- Should redirect to admin dashboard

### Test 3: Booking
- Logout and login as client (`john@example.com`)
- Click "Booking & Reservation"
- Select a movie, date, time, and seats
- Should be able to add to cart and place order

### Test 4: Database
- Check phpMyAdmin → `bookings` table
- Should see your test booking

---

## Troubleshooting

### "Cannot connect to database"

**Solutions:**
1. Check XAMPP MySQL is running (green in Control Panel)
2. Make sure database `xentrocine_db` exists in phpMyAdmin
3. Verify you imported both `schema.sql` and `seed.sql`

### "Port 3000 is already in use"

**Solutions:**
1. Close other applications using port 3000
2. Or edit the `.env` file and change `PORT=3001`

### "Module not found" errors

**Solutions:**
```powershell
# Delete node_modules folder and reinstall
Remove-Item -Recurse -Force node_modules
npm install
```

### "XAMPP MySQL won't start"

**Solutions:**
1. Check if another MySQL service is running
2. Change MySQL port in XAMPP config
3. Run XAMPP as Administrator

### Login doesn't work

**Solutions:**
1. Make sure you imported `database/seed.sql`
2. Check `users` table in phpMyAdmin has data
3. Use the exact credentials shown above (case-sensitive password)

---

## Daily Usage

**Every time you want to run the project:**

1. Start XAMPP Control Panel
2. Start **MySQL** service
3. Open terminal in project folder
4. Run: `npm start`
5. Open browser to: http://localhost:3000

**To stop:**
- Press `Ctrl+C` in the terminal
- Stop MySQL in XAMPP (optional)

---

## Project Structure

```
movie_booking_system/
├── config/
│   └── database.js          # Database connection
├── database/
│   ├── schema.sql          # Database structure (IMPORTANT)
│   └── seed.sql            # Sample data (IMPORTANT)
├── routes/
│   ├── auth.js             # Login/signup
│   ├── movies.js           # Movie management
│   ├── bookings.js         # Booking system
│   └── seats.js            # Seat availability
├── server.js               # Main server file
├── app-api.js              # Frontend JavaScript
├── .env                    # Configuration (auto-created)
├── package.json            # Dependencies
└── (HTML, CSS files)
```

---

## Important Notes

1. **Keep XAMPP MySQL running** while using the application
2. **Don't modify database files** unless you know what you're doing
3. **Passwords are encrypted** - you cannot see them in phpMyAdmin
4. **Fresh database?** - Just re-import `schema.sql` and `seed.sql`

---

## 🎓 For Development

If you want to make changes and have the server auto-restart:

```powershell
npm run dev
```

---

## ✨ Summary

**Quick Setup Checklist:**
- [ ] Install Node.js
- [ ] Install XAMPP
- [ ] Start MySQL in XAMPP
- [ ] Create database `xentrocine_db`
- [ ] Import `schema.sql`
- [ ] Import `seed.sql`
- [ ] Run `npm install`
- [ ] Run `npm start`
- [ ] Open http://localhost:3000

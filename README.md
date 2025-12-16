    # XentroCine — Cinema Booking (Laravel 12)

    XentroCine is a cinema booking system migrated from Node.js/Express to Laravel 12 (PHP 8.2) with vanilla JavaScript on the frontend. It supports online booking, QR-based e-tickets, a staff portal with camera scanning, and walk‑in bookings.

    This README is the single source of truth for setup and development on macOS and Windows. We use Cloudflare Tunnel for mobile testing over HTTPS.

    ## Features

    - Browse movies and showtimes, reserve seats or pax
    - E‑ticket with unique QR token sent by email
    - Staff portal with camera scanner (html5‑qrcode)
    - Check‑in flow:
        - Paid (GCash/digital): staff must tap Verify Entry to check in
        - Pending (cash): staff taps Accept Cash & Check‑in to mark Paid and check in
    - Admin can manage movies and view bookings
    - Session‑based auth with email verification and roles (client, staff, admin)

    ## Tech stack

    - Backend: Laravel 12, PHP 8.2, MySQL/MariaDB
    - Frontend: Blade + vanilla JS (Vite build)
    - QR: simplesoftwareio/simple‑qrcode (email), html5‑qrcode (scanner)

    ## Prerequisites

    Install the following before starting.

    Windows
    - PHP 8.2 (XAMPP, WAMP, or https://windows.php.net)
    - Composer https://getcomposer.org/download/
    - MySQL 8+ or MariaDB 10.6+
    - Node.js 18+ (LTS) https://nodejs.org/en
    - Cloudflare Tunnel (cloudflared) https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/

    macOS
    - Homebrew https://brew.sh/
    - PHP 8.2: `brew install php@8.2 && brew link php@8.2 --force`
    - Composer: `brew install composer`
    - MySQL: `brew install mysql && brew services start mysql` (or `brew install mariadb`)
    - Node.js: `brew install node`
    - ImageMagick + Imagick (for PNG QR in emails, optional): `brew install imagemagick && pecl install imagick`
    - Cloudflare Tunnel: `brew install cloudflare/cloudflare/cloudflared`

    ## 1 Clone and install

    ```bash
    git clone https://github.com/RaineCuenza/xentrocine.git
    cd xentrocine
    composer install
    npm install
    npm install --save html5-qrcode
    cp .env.example .env
    php artisan key:generate
    ```

    ## 2 Configure .env

    Set your database and session settings. Example:

    ```env
    APP_ENV=local
    APP_URL=http://localhost

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=xentrocine_db
    DB_USERNAME=root
    DB_PASSWORD=

    SESSION_DRIVER=database
    SESSION_LIFETIME=120
    ```

    ### 5. Generate Application Key

    ```bash
    php artisan key:generate
    ```

    ### 6. Create Database

    Create a MySQL database named `xentrocine_db`:

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

    For desktop development with hot reload (HMR):

    ```bash
    npm run dev
    ```

    For HTTPS/mobile via Cloudflare, disable hot and use built assets:

    ```bash
    npm run dev:cf   # turns off public/hot and runs build --watch
    ```

    ## 4 Run the app

    Start Laravel:

    ```bash
    php artisan serve
    ```

    Open http://127.0.0.1:8000 on your desktop.

    ## 5 Mobile testing through Cloudflare Tunnel

    Start a tunnel that points to your Laravel server:

    ```bash
    cloudflared tunnel --url http://localhost:8000
    ```

    Copy the generated https://<random>.trycloudflare.com URL and set it in your `.env`:

    ```env
    APP_URL=https://<your-tunnel>.trycloudflare.com
    ```

    Then apply and restart:

    ```bash
    php artisan config:clear
    php artisan cache:clear
    ```

    Open the Cloudflare URL on your phone. Because we’re serving built assets (npm run dev:cf), there’s no mixed‑content issue and CORS is already configured to allow `*.trycloudflare.com`.

    Tips
    - If you switch back to desktop HMR: stop `npm run dev:cf` and run `npm run dev`.
    - If you restart cloudflared, the URL changes—update `APP_URL` and clear config again.

    ## Staff portal and check‑in

    Open /staff, log in as staff or admin, and scan QR codes.

    - Paid (GCash): the first scan shows details and a Verify Entry button. Tap to check in.
    - Pending (cash): tap Accept Cash & Check‑in to mark Paid and check in atomically.
    - The scanner now prevents duplicate reads by pausing the camera during processing and letting you tap Scan Next to resume.

    ## Default accounts (after seeding)

    - Admin: `admin@admin` / `@dmin_123`
    - Staff: `staff@staff` / `St@ff_123`
    - Client: `client@client` / `P@ssword_123`

    ## Useful commands

    ```bash
    # Full reset with demo data
    php artisan migrate:fresh --seed

    # Clear caches
    php artisan opt:clear

    # Run tests
    composer test
    ```

    ## Troubleshooting

    - Blank/unstyled page on mobile: ensure `npm run dev:cf` is running and there is no `public/hot` file.
    - CORS errors in console: confirm APP_URL is your Cloudflare URL, then `php artisan config:clear`.
    - Camera doesn’t start on phone: grant camera permission in browser settings; Chrome/Firefox recommended.
    - “Ticket already checked in”: the system is idempotent—re‑scan of a checked‑in ticket returns 409 without changing data.

    **NOTES**

    How to run:
    php artisan serve
    npm run dev:cf
    <!-- brew install cloudflared -->
    cloudflared tunnel --url http://localhost:8000
    Update .env APP_URL to the link generated by cloudfared

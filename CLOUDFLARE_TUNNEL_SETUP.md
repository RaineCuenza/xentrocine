# Cloudflare Tunnel Setup for Mobile Testing

## Quick Setup

### 1. Update your `.env` file

Add/update these lines in your `.env`:

```env
# Update APP_URL to your Cloudflare tunnel URL
APP_URL=https://producing-run-dental-excessive.trycloudflare.com

# Optional: Add this line if you want to explicitly set it
CLOUDFLARE_TUNNEL_URL=https://producing-run-dental-excessive.trycloudflare.com

# Session settings for cross-origin (keep these as-is)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

**Important:** Replace `https://producing-run-dental-excessive.trycloudflare.com` with your actual Cloudflare tunnel URL. The URL changes each time you restart the tunnel.

### 2. Clear config cache

```powershell
php artisan config:clear
php artisan cache:clear
```

### 3. Restart your development servers

Stop your current servers (Ctrl+C) and restart:

```powershell
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Cloudflare tunnel (point to Laravel server)
cloudflared tunnel --url http://localhost:8000
```

Or if using the combined dev command:
```powershell
composer run dev
```

Then in a separate terminal:
```powershell
cloudflared tunnel --url http://localhost:8000
```

### 4. Access on mobile

Use the Cloudflare tunnel URL (e.g., `https://producing-run-dental-excessive.trycloudflare.com`) in your mobile browser.

## What Changed?

1. **CORS Configuration** (`config/cors.php`):
   - Added `APP_URL` and `CLOUDFLARE_TUNNEL_URL` to allowed origins
   - Added regex pattern to allow all `*.trycloudflare.com` domains
   - This fixes the CORS errors you were seeing

2. **Session Configuration**:
   - Ensured sessions work across the tunnel with proper `SESSION_SAME_SITE=lax`

## Troubleshooting

### Still getting CORS errors?

1. Make sure you updated `APP_URL` in `.env`
2. Clear config cache: `php artisan config:clear`
3. Restart Laravel server
4. Hard refresh browser (Ctrl+Shift+R) or clear mobile browser cache

### Tunnel URL keeps changing?

Free Cloudflare tunnels generate random URLs. Each time you restart, update `APP_URL` in `.env`:

```powershell
# Update .env with new URL
# Then clear cache
php artisan config:clear
```

### Alternative: Use ngrok

If Cloudflare tunnel is unstable, try ngrok:

```powershell
# Install ngrok from https://ngrok.com/download
# Then run:
ngrok http 8000
```

Update `APP_URL` with the ngrok URL (e.g., `https://abc123.ngrok.io`)

## Session Authentication Notes

- CSRF tokens work automatically with the updated CORS config
- Sessions are stored in database (`SESSION_DRIVER=database`)
- Mobile device will receive proper session cookies
- Login/logout works seamlessly across devices

## QR Code Scanning on Mobile

The staff portal's QR scanner (`/staff`) will work on mobile devices:

1. Access `https://your-tunnel-url.trycloudflare.com/staff`
2. Login with staff credentials
3. Browser will request camera permission
4. Scan QR codes directly from mobile camera

**Tip:** For best camera performance, use Chrome or Firefox on mobile (not Safari).

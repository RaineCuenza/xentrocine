# Mobile QR Scanning Setup Guide

## Problem
Browsers block camera access on HTTP connections (except `localhost`). Your development server runs on `http://192.168.0.187:8000`, which triggers this security restriction on mobile devices.

## Solution: Use HTTPS via Ngrok (Recommended)

### Step 1: Install Ngrok
1. Download from https://ngrok.com/download
2. Extract to a folder (e.g., `C:\ngrok`)
3. (Optional) Sign up for a free account to get an auth token for longer sessions

### Step 2: Start Your Laravel Server
```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

### Step 3: Start Ngrok Tunnel
```powershell
ngrok http 8000
```

You'll see output like:
```
Forwarding  https://abc123.ngrok-free.app -> http://localhost:8000
```

### Step 4: Access on Mobile
1. Open the **https** URL (e.g., `https://abc123.ngrok-free.app`) on your phone
2. Log in as staff (staff@staff / St@ff_123)
3. Navigate to the Staff portal
4. Grant camera permissions when prompted
5. QR scanner will start automatically

## Alternative: Cloudflare Tunnel (Free, No Signup)
```powershell
# Install Cloudflare Tunnel
winget install --id Cloudflare.cloudflared

# Start tunnel
cloudflared tunnel --url http://localhost:8000
```

## Hardware Scanner Option (Desktop)
For desktop cashiers with USB barcode/QR scanners:
- Scanner acts as keyboard input (called "HID wedge")
- Token auto-populates in manual input field
- Press Enter to verify (or scanner can be configured to auto-submit)
- No camera needed

## Cashier Dashboard Features

### Desktop View
- **Left Panel**: QR Scanner (camera + manual input)
- **Right Panel**: Walk-in Booking (select movie → pax → confirm)
- Both visible simultaneously for multitasking

### Mobile View
- **Stacked Layout**: Scanner first, then walk-in
- Camera starts automatically on scanner section
- Deep links work:
  - `/staff#scannerMode` → scroll to check-in
  - `/staff#walkinMode` → scroll to walk-in

## Workflow Examples

### Scenario 1: Pre-booked Customer (Online)
1. Customer arrives with QR code (from email)
2. Staff scans with phone camera OR enters token manually
3. If pending (cash): "Accept Cash & Check-in" button appears
4. If paid (digital): instant check-in

### Scenario 2: Walk-in Customer
1. Staff selects movie, showtime, cinema
2. Sets number of pax
3. Enters customer name (email/phone optional)
4. Selects payment method (cash or digital)
5. Confirms booking
6. System creates booking and shows QR for immediate check-in if needed

### Scenario 3: USB Scanner (Desktop)
1. Customer presents QR code
2. Staff points USB scanner at code
3. Token auto-types into manual input field
4. Press Enter (or scanner auto-submits)
5. Verification happens instantly

## Troubleshooting

### Camera not starting on mobile
- Ensure you're using HTTPS (ngrok/cloudflare)
- Check browser console for errors
- Try Chrome/Firefox (better camera API support than Safari)
- Verify camera permissions in browser settings

### "Camera access denied" on desktop
- Check browser security settings
- Allow camera for the site
- Try incognito/private mode to reset permissions

### USB scanner not working
- Ensure scanner is in "HID keyboard" mode (check manual)
- Test by scanning into Notepad first
- Configure scanner to send Enter key after scan (optional)

## Security Notes
- Ngrok free tier URLs change on each restart
- For production, use proper SSL certificate (Let's Encrypt + Caddy/NGINX)
- Don't share ngrok URLs publicly—they expose your development server
- For deployed production, ensure HTTPS is configured on your hosting

## Next Steps
1. Test scanner on desktop: open `/staff`, allow camera
2. Test on mobile: use ngrok HTTPS URL, log in as staff
3. For production: configure proper HTTPS on your web host

@extends('layouts.app')

@section('page', 'qr-test')

@section('title', 'QR Code Test - XentroCine')

@section('content')
<div class="qr-test-page">
    <div class="test-header">
        <h1>QR Code Test Page</h1>
        <p>Generate test QR codes for scanning practice</p>
    </div>

    <div class="test-container">
        <!-- Manual QR Generator -->
        <div class="test-section">
            <h3>Generate Test QR Code</h3>
            <p style="color:#7f8c8d;margin-bottom:15px">Enter any token to generate a QR code for testing</p>
            
            <div style="display:flex;gap:10px;margin-bottom:20px">
                <input type="text" id="testToken" placeholder="Enter token (e.g., et_abc123...)" style="flex:1;padding:10px;border:2px solid #ddd;border-radius:6px;font-size:1rem">
                <button id="generateQR" style="padding:10px 20px;background:#e74c3c;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer">Generate QR</button>
            </div>
            
            <div id="qrDisplay" style="text-align:center;padding:30px;background:#f8f9fa;border-radius:8px;display:none">
                <div id="qrCodeContainer"></div>
                <p id="qrTokenDisplay" style="margin-top:15px;font-family:monospace;color:#555;word-break:break-all"></p>
                <button id="printQR" style="margin-top:15px;padding:10px 20px;background:#3498db;color:white;border:none;border-radius:6px;cursor:pointer">Print QR Code</button>
            </div>
        </div>

        <!-- Recent Bookings QR Codes -->
        <div class="test-section">
            <h3>Recent Bookings (Your QR Codes)</h3>
            <p style="color:#7f8c8d;margin-bottom:15px">Click any booking to view its QR code</p>
            
            <div id="bookingsList" style="display:grid;gap:10px">
                <p style="text-align:center;color:#999">Loading bookings...</p>
            </div>
        </div>
    </div>
</div>

<style>
.qr-test-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.test-header {
    text-align: center;
    margin-bottom: 40px;
}

.test-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.test-header p {
    color: #7f8c8d;
}

.test-container {
    display: grid;
    gap: 30px;
}

.test-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.test-section h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.booking-item {
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.booking-item:hover {
    border-color: #e74c3c;
    background: #fff5f5;
}

.booking-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.booking-item-title {
    font-weight: 700;
    color: #2c3e50;
}

.booking-status {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-paid {
    background: #27ae60;
    color: white;
}

.status-pending {
    background: #f39c12;
    color: white;
}

.booking-item-details {
    font-size: 0.9rem;
    color: #7f8c8d;
}

@media print {
    body * {
        visibility: hidden;
    }
    #qrDisplay, #qrDisplay * {
        visibility: visible;
    }
    #qrDisplay {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }
    #printQR {
        display: none;
    }
}
</style>

<script type="module">
// Import QRCode library
import QRCode from 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/+esm';

document.addEventListener('DOMContentLoaded', async () => {
    const testTokenInput = document.getElementById('testToken');
    const generateQRBtn = document.getElementById('generateQR');
    const qrDisplay = document.getElementById('qrDisplay');
    const qrCodeContainer = document.getElementById('qrCodeContainer');
    const qrTokenDisplay = document.getElementById('qrTokenDisplay');
    const printQRBtn = document.getElementById('printQR');
    const bookingsList = document.getElementById('bookingsList');

    // Generate QR code from manual input
    generateQRBtn.addEventListener('click', async () => {
        const token = testTokenInput.value.trim();
        if (!token) {
            alert('Please enter a token');
            return;
        }
        
        await displayQRCode(token);
    });

    // Print QR code
    printQRBtn.addEventListener('click', () => {
        window.print();
    });

    // Function to display QR code
    async function displayQRCode(token, bookingInfo = null) {
        try {
            qrCodeContainer.innerHTML = '';
            
            const canvas = document.createElement('canvas');
            await QRCode.toCanvas(canvas, token, {
                width: 300,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#ffffff'
                }
            });
            
            qrCodeContainer.appendChild(canvas);
            
            if (bookingInfo) {
                qrTokenDisplay.innerHTML = `
                    <strong>${bookingInfo.movie}</strong><br>
                    ${bookingInfo.date} at ${bookingInfo.time}<br>
                    <span style="font-size:0.85rem">Token: ${token}</span>
                `;
            } else {
                qrTokenDisplay.textContent = `Token: ${token}`;
            }
            
            qrDisplay.style.display = 'block';
            qrDisplay.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (error) {
            alert('Failed to generate QR code: ' + error.message);
        }
    }

    // Load user bookings
    async function loadBookings() {
        try {
            const response = await fetch('/api/bookings', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load bookings');
            }
            
            const bookings = await response.json();
            
            if (bookings.length === 0) {
                bookingsList.innerHTML = '<p style="text-align:center;color:#999">No bookings yet. Create a booking to see QR codes here.</p>';
                return;
            }
            
            bookingsList.innerHTML = bookings.map(booking => {
                const firstItem = booking.items && booking.items[0];
                if (!firstItem) return '';
                
                return `
                    <div class="booking-item" data-token="${booking.et_qr_token || ''}" 
                         data-movie="${firstItem.movie_title}"
                         data-date="${firstItem.date}"
                         data-time="${firstItem.time}">
                        <div class="booking-item-header">
                            <span class="booking-item-title">${firstItem.movie_title}</span>
                            <span class="booking-status status-${booking.status.toLowerCase()}">${booking.status}</span>
                        </div>
                        <div class="booking-item-details">
                            ${firstItem.date} at ${firstItem.time} • ${firstItem.cinema}<br>
                            Seats: ${booking.items.map(i => i.seats[0]).join(', ')}
                        </div>
                    </div>
                `;
            }).join('');
            
            // Add click handlers
            document.querySelectorAll('.booking-item').forEach(item => {
                item.addEventListener('click', () => {
                    const token = item.dataset.token;
                    if (token) {
                        displayQRCode(token, {
                            movie: item.dataset.movie,
                            date: item.dataset.date,
                            time: item.dataset.time
                        });
                    }
                });
            });
            
        } catch (error) {
            bookingsList.innerHTML = '<p style="text-align:center;color:#e74c3c">Failed to load bookings. Please login first.</p>';
        }
    }
    
    loadBookings();
});
</script>
@endsection

@extends('layouts.app')

@section('page', 'staff')

@section('title', 'Cashier Dashboard - XentroCine')

@section('content')
<div class="staff-portal">
    <!-- Cashier workspace: responsive two-column grid (Scanner + Walk-in) -->
    <div class="cashier-grid">
    
    <!-- QR Scanner Mode -->
    <div class="staff-mode" id="scannerMode">
        <div class="scanner-container">
            <div class="scanner-header">
                <h2>Scan Customer QR Code</h2>
                <p>Position the QR code within the frame</p>
            </div>
            
            <!-- Camera Preview -->
            <div class="camera-preview">
                <!-- QR scanner will be injected here by html5-qrcode -->
                <div id="qr-reader"></div>
            </div>

            <!-- Manual Token Input (fallback) -->
            <div class="manual-input">
                <input type="text" id="manualToken" placeholder="Enter token manually (e.g., et_XXXXXXXX...)" autofocus>
                <button id="manualScanBtn" class="btn-primary">Verify</button>
            </div>

            <!-- Scan Result -->
            <div class="scan-result" id="scanResult" style="display: none;">
                <!-- Result will be injected here -->
            </div>
        </div>
    </div>

    <!-- Walk-in Booking Mode -->
    <div class="staff-mode" id="walkinMode">
        <div class="walkin-container">
            <div class="walkin-header">
                <h2>Create Walk-in Booking</h2>
                <p>Select movie, showtime, and pax for customer</p>
            </div>

            <!-- Movie Selection -->
            <div class="booking-step">
                <h3>1. Select Movie & Showtime</h3>
                <div class="movie-select-grid" id="walkinMovieGrid">
                    <!-- Movies will be loaded here -->
                </div>
            </div>

            <!-- Date & Cinema Selection -->
            <div class="booking-step" id="dateTimeCinemaStep" style="display: none;">
                <h3>2. Date, Showtime & Cinema</h3>
                <div class="selection-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" id="walkinDate" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Showtime</label>
                        <select id="walkinShowtime">
                            <option value="">Select time</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cinema</label>
                        <select id="walkinCinema">
                            <option value="Cinema 1">Cinema 1</option>
                            <option value="Cinema 2">Cinema 2</option>
                            <option value="Cinema 3">Cinema 3</option>
                        </select>
                    </div>
                </div>
                <button id="proceedToSeatsBtn" class="btn-primary">Continue</button>
            </div>

            <!-- Pax Selection -->
            <div class="booking-step" id="seatSelectionStep" style="display: none;">
                <h3>3. Pax</h3>
                <div class="seat-selection-info">
                    <div id="selectedMovieInfo"></div>
                    <div class="selected-seats-summary">
                        <span>Pax: <strong id="selectedSeatsCount">1</strong></span>
                        <span>Total: <strong id="totalAmount">₱0.00</strong></span>
                    </div>
                </div>

                <div class="form-group" style="max-width:200px;">
                    <label>Number of Pax</label>
                    <input type="number" id="walkinPax" min="1" value="1">
                </div>

                <!-- Customer Info -->
                <div class="customer-info-form">
                    <h4>Customer Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" id="customerName" placeholder="Full Name" required>
                        </div>
                        <div class="form-group">
                            <label>Email (Optional)</label>
                            <input type="email" id="customerEmail" placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label>Phone (Optional)</label>
                            <input type="tel" id="customerPhone" placeholder="09XX XXX XXXX">
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="payment-method-select">
                    <h4>Payment Method</h4>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="paymentMethod" value="cash" checked>
                            <span>Cash (Pay now)</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="paymentMethod" value="digital">
                            <span>Digital (Already paid)</span>
                        </label>
                    </div>
                </div>

                <div class="booking-actions">
                    <button id="backToMovieBtn" class="btn-secondary">Back</button>
                    <button id="confirmWalkinBookingBtn" class="btn-primary">Confirm Booking</button>
                </div>
            </div>
        </div>
    </div>
    
    </div><!-- end cashier-grid -->
</div>

<style>
.staff-portal {
    max-width: 100%;
    margin: 0;
    padding: 0;
    min-height: calc(100vh - 120px); /* Full viewport minus header/footer */
}

.cashier-grid {
    display: grid;
    grid-template-columns: 1fr 1fr; /* Equal columns */
    gap: 0;
    min-height: calc(100vh - 120px);
}

.staff-mode {
    padding: 32px;
    min-height: 100%;
}

#scannerMode {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-right: 2px solid #dee2e6;
}

#walkinMode {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.scanner-container,
.walkin-container {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    max-width: 700px;
    margin: 0 auto;
}

.scanner-header,
.walkin-header {
    text-align: center;
    margin-bottom: 32px;
}

.scanner-header h2,
.walkin-header h2 {
    color: #1a202c;
    font-size: 1.75rem;
    margin-bottom: 8px;
    font-weight: 700;
}

.scanner-header p,
.walkin-header p {
    color: #718096;
    font-size: 0.95rem;
}

.camera-preview {
    position: relative;
    width: 100%;
    max-width: 600px;
    margin: 0 auto 24px;
    border-radius: 16px;
    overflow: hidden;
    background: #000;
    min-height: 400px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.camera-preview video {
    width: 100%;
    height: auto;
    display: block;
}

/* Style the html5-qrcode elements */
#qr-reader {
    border-radius: 12px;
    overflow: hidden;
}

#qr-reader video {
    border-radius: 12px;
}

#qr-reader__dashboard_section {
    display: none !important; /* Hide the default controls */
}

.manual-input {
    display: flex;
    gap: 12px;
    max-width: 600px;
    margin: 0 auto 24px;
}

.manual-input input {
    flex: 1;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s;
}

.manual-input input:focus {
    outline: none;
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.manual-input button {
    padding: 14px 32px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.manual-input button:hover {
    background: #c0392b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.scan-result {
    margin-top: 30px;
    padding: 20px;
    border-radius: 8px;
}

.scan-result.success {
    background: #d4edda;
    border: 2px solid #28a745;
}

.scan-result.pending {
    background: #fff3cd;
    border: 2px solid #ffc107;
}

.scan-result.error {
    background: #f8d7da;
    border: 2px solid #dc3545;
}

.result-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    font-size: 1.2rem;
    font-weight: 600;
}

.result-details {
    margin: 15px 0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.detail-label {
    font-weight: 600;
    color: #555;
}

.seats-display {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.seat-tag {
    background: #ecf0f1;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 600;
}

.result-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

/* Walk-in Mode */
.walkin-container {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    max-width: 700px;
    margin: 0 auto;
}

.walkin-header {
    margin-bottom: 32px;
}

.walkin-header h2 {
    color: #1a202c;
    font-size: 1.75rem;
    margin-bottom: 8px;
    font-weight: 700;
}

.walkin-header p {
    color: #718096;
    font-size: 0.95rem;
}

.booking-step {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 2px solid #ecf0f1;
}

.booking-step:last-child {
    border-bottom: none;
}

.booking-step h3 {
    color: #1a202c;
    margin-bottom: 20px;
    font-size: 1.25rem;
    font-weight: 600;
}

.movie-select-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.movie-card-small {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    background: white;
}

.movie-card-small:hover {
    border-color: #e74c3c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.15);
}

.movie-card-small.selected {
    border-color: #e74c3c;
    background: #fff5f5;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.2);
}

.movie-card-small h4 {
    margin: 10px 0 8px;
    color: #1a202c;
    font-size: 1.1rem;
    font-weight: 600;
}

.movie-card-small .price {
    color: #e74c3c;
    font-weight: 700;
    font-size: 1.15rem;
}

.selection-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #555;
}

.form-group input,
.form-group select {
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.seat-selection-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.selected-seats-summary {
    display: flex;
    gap: 20px;
    font-size: 1.1rem;
}

.seat-map-container {
    margin: 30px 0;
}

.screen {
    background: #34495e;
    color: white;
    text-align: center;
    padding: 10px;
    border-radius: 8px 8px 0 0;
    margin-bottom: 30px;
    font-weight: 700;
    letter-spacing: 2px;
}

.seat-map {
    display: grid;
    grid-template-columns: repeat(14, 1fr);
    gap: 8px;
    max-width: 800px;
    margin: 0 auto 20px;
}

.seat {
    aspect-ratio: 1;
    border: 2px solid #95a5a6;
    border-radius: 8px 8px 0 0;
    background: #ecf0f1;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.seat:hover:not(.occupied):not(.aisle) {
    transform: scale(1.1);
    border-color: #3498db;
}

.seat.occupied {
    background: #e74c3c;
    border-color: #c0392b;
    cursor: not-allowed;
}

.seat.selected {
    background: #2ecc71;
    border-color: #27ae60;
}

.seat.aisle {
    background: transparent;
    border: none;
    cursor: default;
}

.seat-legend {
    display: flex;
    justify-content: center;
    gap: 30px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.legend-box {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-right: 5px;
    vertical-align: middle;
}

.legend-box.available {
    background: #ecf0f1;
    border: 2px solid #95a5a6;
}

.legend-box.occupied {
    background: #e74c3c;
    border: 2px solid #c0392b;
}

.legend-box.selected {
    background: #2ecc71;
    border: 2px solid #27ae60;
}

.customer-info-form,
.payment-method-select {
    margin: 30px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.customer-info-form h4,
.payment-method-select h4 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.payment-options {
    display: flex;
    gap: 20px;
}

.payment-option {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-option:has(input:checked) {
    border-color: #e74c3c;
    background: #fff5f5;
}

.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
}

.booking-actions {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 30px;
}

.btn-primary,
.btn-secondary {
    padding: 12px 30px;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: #e74c3c;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #c0392b;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
    border: none;
}

.btn-secondary:hover {
    background: #7f8c8d;
}
</style>
<style>
/* Visual refresh overrides for better readability and minimalist look */

/* Inputs */
.manual-input input {
    border: 1px solid #e5e7eb;
    background: #fff;
}
.manual-input input:focus {
    outline: none;
    border-color: #e74c3c;
    box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.08);
}

/* Scan result cards with higher contrast */
.scan-result {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}
.scan-result.success {
    background: #ecfdf5;            /* emerald-50 */
    border-left: 4px solid #10b981; /* emerald-500 */
    color: #064e3b;                 /* emerald-900 */
}
.scan-result.pending {
    background: #fffbeb;            /* amber-50 */
    border-left: 4px solid #f59e0b; /* amber-500 */
    color: #78350f;                 /* amber-900 */
}
.scan-result.error {
    background: #fef2f2;            /* red-50 */
    border-left: 4px solid #ef4444; /* red-500 */
    color: #7f1d1d;                 /* red-900 */
}
.scan-result .result-header { color: #111827; }
.scan-result .detail-label { color: #374151; }
.scan-result .detail-row { border-bottom: 1px dashed #e5e7eb; }
.scan-result .seats-display .seat-tag {
    background: #eef2f7;
    border: 1px solid #e5e7eb;
}

/* Buttons */
.btn-primary { background: #e74c3c; border: none; color: #fff; }
.btn-primary:hover { background: #c0392b; }
.btn-secondary { background: #111827; border: none; color: #fff; }
.btn-secondary:hover { background: #0b1220; }

/* --- Mobile-first responsive tweaks for staff portal --- */
@media (max-width: 1024px) {
    .cashier-grid {
        grid-template-columns: 1fr;
        min-height: auto;
    }
    
    #scannerMode {
        border-right: none;
        border-bottom: 2px solid #dee2e6;
    }
    
    .staff-mode {
        padding: 24px;
    }
}

@media (max-width: 720px) {
    .staff-mode {
        padding: 20px 16px;
    }
    
    .scanner-container, .walkin-container {
        padding: 24px 20px;
        border-radius: 12px;
    }
    
    .scanner-header h2, .walkin-header h2 {
        font-size: 1.5rem;
    }
    
    .camera-preview {
        max-width: none;
        min-height: 300px;
        border-radius: 12px;
    }
    
    .manual-input {
        flex-direction: column;
        max-width: none;
    }
    
    .manual-input button {
        width: 100%;
        padding: 14px;
    }
    
    .result-actions { flex-direction: column; }
    .seat-map { grid-template-columns: repeat(14, minmax(0, 1fr)); gap: 6px; }
    .selection-row, .form-row { grid-template-columns: 1fr; }
    .payment-options { flex-direction: column; }
    .booking-actions { flex-direction: column; }
    .btn-primary, .btn-secondary { width: 100%; }
}

@media (max-width: 480px) {
    .scanner-header h2, .walkin-header h2 { font-size: 1.25rem; }
    .seat-map { max-width: 100%; }
    /* Make seats size with viewport to fit 14 columns on small screens */
    .seat-map .seat { border-width: 1.5px; }
    .seat { aspect-ratio: 1 / 1; }
}
</style>
@endsection
@push('scripts')
<script>
// Unified cashier dashboard - both panels visible, hash used for smooth scrolling only
document.addEventListener('DOMContentLoaded', function () {
    // Smooth scroll to section if hash present
    if (location.hash) {
        const target = document.querySelector(location.hash);
        if (target) {
            setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
        }
    }
    
    // Handle hash navigation
    window.addEventListener('hashchange', () => {
        const target = document.querySelector(location.hash);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
@endpush

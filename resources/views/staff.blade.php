@extends('layouts.app')

@section('page', 'staff')

@section('title', 'Staff Portal - XentroCine')

@section('content')
<div class="staff-portal">
    <div class="portal-header">
        <h1>Staff Portal</h1>
        <div class="staff-info">
            <span id="staffName"></span>
            <span class="role-badge" id="staffRole"></span>
        </div>
    </div>

    <!-- Mode Selector -->
    <div class="mode-selector">
        <button class="mode-btn active" data-mode="scanner">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 10h18M10 3v18"/>
            </svg>
            QR Scanner
        </button>
        <button class="mode-btn" data-mode="walkin">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <path d="M20 8v6M23 11h-6"/>
            </svg>
            Walk-in Booking
        </button>
    </div>

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
            </div>

            <!-- Manual Token Input (fallback) -->
            <div class="manual-input">
                <input type="text" id="manualToken" placeholder="Enter token manually (e.g., et_XXXXXXXX...)">
                <button id="manualScanBtn" class="btn-primary">Verify</button>
            </div>

            <!-- Scan Result -->
            <div class="scan-result" id="scanResult" style="display: none;">
                <!-- Result will be injected here -->
            </div>
        </div>
    </div>

    <!-- Walk-in Booking Mode -->
    <div class="staff-mode" id="walkinMode" style="display: none;">
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
</div>

<style>
.staff-portal {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.portal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.portal-header h1 {
    font-size: 2rem;
    color: #2c3e50;
}

.staff-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.role-badge {
    background: #3498db;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.mode-selector {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}

.mode-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 15px 20px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1rem;
    font-weight: 600;
}

.mode-btn:hover {
    border-color: #e74c3c;
    background: #fff5f5;
}

.mode-btn.active {
    border-color: #e74c3c;
    background: #e74c3c;
    color: white;
}

/* Scanner Mode */
.scanner-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.scanner-header {
    text-align: center;
    margin-bottom: 30px;
}

.scanner-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.scanner-header p {
    color: #7f8c8d;
}

.camera-preview {
    position: relative;
    width: 100%;
    max-width: 500px;
    margin: 0 auto 30px;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    min-height: 300px;
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
    gap: 10px;
    max-width: 500px;
    margin: 0 auto 30px;
}

.manual-input input {
    flex: 1;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.manual-input button {
    padding: 12px 30px;
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
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.walkin-header {
    margin-bottom: 30px;
}

.walkin-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.walkin-header p {
    color: #7f8c8d;
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
    color: #2c3e50;
    margin-bottom: 20px;
}

.movie-select-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.movie-card-small {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.movie-card-small:hover {
    border-color: #e74c3c;
    transform: translateY(-2px);
}

.movie-card-small.selected {
    border-color: #e74c3c;
    background: #fff5f5;
}

.movie-card-small h4 {
    margin: 10px 0 5px;
    color: #2c3e50;
}

.movie-card-small .price {
    color: #e74c3c;
    font-weight: 700;
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
    .mode-selector { flex-wrap: wrap; }
    .mode-btn { flex: 1 1 48%; }
}

@media (max-width: 720px) {
    .portal-header { flex-direction: column; gap: 8px; align-items: flex-start; }
    .mode-selector { gap: 10px; }
    .mode-btn { flex: 1 1 100%; padding: 14px; }
    .scanner-container, .walkin-container { padding: 18px; }
    .camera-preview { max-width: none; min-height: 40vh; }
    .manual-input { flex-direction: column; max-width: none; }
    .result-actions { flex-direction: column; }
    .seat-map { grid-template-columns: repeat(14, minmax(0, 1fr)); gap: 6px; }
    .selection-row, .form-row { grid-template-columns: 1fr; }
    .payment-options { flex-direction: column; }
    .booking-actions { flex-direction: column; }
    .btn-primary, .btn-secondary { width: 100%; }
}

@media (max-width: 480px) {
    .scanner-header h2, .walkin-header h2 { font-size: 1.25rem; }
    .portal-header h1 { font-size: 1.4rem; }
    .seat-map { max-width: 100%; }
    /* Make seats size with viewport to fit 14 columns on small screens */
    .seat-map .seat { border-width: 1.5px; }
    .seat { aspect-ratio: 1 / 1; }
}
</style>
@endsection

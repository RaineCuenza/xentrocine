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

                @if(empty($movies) || $movies->count() === 0)
                    <div style="padding:2rem;text-align:center;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;margin-bottom:1rem;">
                        <p style="margin:0;color:#856404;font-weight:600;">No movies available for walk-in booking.</p>
                        <p style="margin:0.5rem 0 0;color:#856404;font-size:0.9rem;">
                            Please ensure movies are created in Admin with showtimes and valid listing dates.
                        </p>
                    </div>
                @endif

                <div class="movie-select-grid" id="walkinMovieGrid">
                    <!-- Server-rendered active movies -->
                    @if(!empty($movies))
                        @foreach($movies as $mv)
                            @php
                                $showtimes = [];
                                $mvShowtimes = isset($mv->showtimes) ? $mv->showtimes : [];
                                foreach ($mvShowtimes as $st) {
                                    $showtimes[] = [
                                        'id' => $st->id,
                                        'time' => date('H:i', strtotime($st->showtime)),
                                    ];
                                }
                            @endphp
                            <div
                                class="movie-card-small"
                                data-movie-id="{{ $mv->id }}"
                                data-price="{{ number_format((float)$mv->price, 2, '.', '') }}"
                                data-duration="{{ (int)($mv->duration ?? 120) }}"
                                data-listing-start="{{ optional($mv->listing_start)->toDateString() }}"
                                data-listing-end="{{ optional($mv->listing_end)->toDateString() }}"
                                data-showtimes='@json($showtimes)'
                            >
                                @if(!empty($mv->poster))
                                    <img
                                        src="{{ $mv->poster }}"
                                        alt="{{ $mv->title }} poster"
                                        width="300"
                                        height="450"
                                        style="width:100%;height:auto;border-radius:8px;object-fit:cover;"
                                    >
                                @endif

                                <h4>{{ $mv->title }}</h4>
                                <div class="price">₱{{ number_format((float)$mv->price, 2) }}</div>
                                <div class="audience" style="margin-top:6px;color:#6b7280;font-size:0.9rem;">
                                    Rated: {{ $mv->audience }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Step 1: Showtime & Cinema (Date fixed to today) -->
            <div class="booking-step" id="dateTimeCinemaStep" style="display: none;">
                <h3>2. Showtime & Cinema</h3>
                <div class="selection-row">
                    <div class="form-group">
                        <label>
                            Showtime
                            <div class="muted" style="font-size:.85rem; margin-top:4px;">Available showtimes (based on current time)</div>
                        </label>
                        <select id="walkinShowtime">
                            <option value="">Select time</option>
                        </select>
                        <small id="showtimeHelper" class="muted" style="display:none; margin-top:6px;">No available showtimes for this movie at the moment.</small>
                    </div>
                    <div class="form-group">
                        <label>Cinema</label>
                        <select id="walkinCinema">
                            <option value="Cinema 1">Cinema 1</option>
                            <option value="Cinema 2" @if(!config('app.cinema2_available')) disabled @endif>Cinema 2</option>
                        </select>
                        @if(!config('app.cinema2_available'))
                          <small class="muted">Cinema 2 is currently unavailable.</small>
                        @endif
                    </div>
                </div>
                <div id="selectionSummary" class="muted" style="display:none; margin:8px 0 0;">Selected: <span id="summaryText"></span></div>
                <div class="booking-actions" style="justify-content:flex-end; margin-top:10px;">
                    <button id="proceedToSeatsBtn" class="btn-primary" disabled>Continue</button>
                </div>
            </div>

            <!-- Step 2: Pax Selection -->
            <div class="booking-step" id="paxStep" style="display: none;">
                <h3>3. Pax / Ticket Quantity</h3>
                <!-- Pax info and breakdown -->
                <div class="seat-selection-info">
                    <div id="selectedMovieInfo"></div>
                    <div class="selected-seats-summary">
                        <span>Pax: <strong id="paxBreakdown">0 Regular, 0 Senior</strong></span>
                        <span>Discount: <strong id="discountAmount">₱0.00</strong></span>
                        <span>Total: <strong id="totalAmount">₱0.00</strong></span>
                    </div>
                </div>
                <div class="selection-row">
                    <div class="form-group" style="max-width:200px;">
                        <label for="regularPax">Regular Pax</label>
                        <input type="number" id="regularPax" min="0" value="1" class="form-control">
                    </div>
                    <div class="form-group" style="max-width:200px;">
                        <label for="seniorPax">Senior Pax</label>
                        <input type="number" id="seniorPax" min="0" value="0" class="form-control">
                        <small class="muted">20% discount applies to seniors only.</small>
                    </div>
                </div>
                <div class="booking-actions">
                    <button id="paxBackBtn" class="btn-secondary">Back</button>
                    <button id="paxContinueBtn" class="btn-primary" disabled>Continue</button>
                </div>
            </div>

            <!-- Step 3: Payment -->
            <div class="booking-step" id="paymentStep" style="display: none;">
                <h3>4. Payment</h3>
                <!-- Staff Walk-in Booking Form -->
                <form id="staffWalkinForm" method="POST" action="{{ route('bookings.place') }}">
                    @csrf
                                        <!-- Alerts: success and error messages -->
                                        @if(session('message'))
                                            <div class="scan-result success" style="margin-bottom:16px;">
                                                <div class="result-header">{{ session('message') }}</div>
                                            </div>
                                        @endif
                                        @if($errors->has('booking'))
                                            <div class="scan-result error" style="margin-bottom:16px;">
                                                <div class="result-header">{{ $errors->first('booking') }}</div>
                                            </div>
                                        @endif
                    <!-- Hidden fields populated by step 1/2 selections -->
                    <input type="hidden" name="movieId" id="formMovieId" value="">
                    <input type="hidden" name="show_date" id="formShowDate" value="{{ now()->toDateString() }}">
                    <input type="hidden" name="showtime_id" id="formShowtimeId" value="">
                    <input type="hidden" name="cinema" id="formCinema" value="">

                <!-- Payment Method -->
                <div class="payment-method-select">
                    <h4>Payment Method</h4>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash" checked>
                            <span>Cash (Pay now)</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="gcash">
                            <span>GCash (Already paid)</span>
                        </label>
                    </div>
                    @error('payment_method')
                      <small class="muted" style="color:#b91c1c">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Payment Reference (GCash only) -->
                <div class="payment-ref-form" id="gcashRefRow" style="display:none;">
                    <h4>GCash Reference</h4>
                    <div class="form-row">
                        <div class="form-group" style="max-width:320px;">
                            <label for="gcashRef">GCash Reference Number</label>
                            <input type="text" id="gcashRef" name="gcash_reference" placeholder="e.g., 1234 5678 9012">
                            @error('gcash_reference')
                              <small class="muted" style="color:#b91c1c">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="booking-actions">
                    <button id="backToPaymentPaxBtn" class="btn-secondary">Back</button>
                    <button id="confirmWalkinBookingBtn" type="submit" class="btn-primary">Confirm Booking</button>
                </div>
                </form>
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
    border-color: #7c5cff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(124, 92, 255, 0.2);
}

.movie-card-small.selected {
    border-color: #7c5cff;
    background: #f5f3ff; /* indigo-50 */
    box-shadow: 0 4px 12px rgba(124, 92, 255, 0.25);
}

.movie-card-small h4 {
    margin: 10px 0 8px;
    color: #1a202c;
    font-size: 1.1rem;
    font-weight: 600;
}

.movie-card-small .price {
    color: #7c5cff;
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
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    background: #ffffff;
}

/* High-contrast focus styles */
.form-group input:focus,
.form-group select:focus,
.payment-option:has(input:focus),
.btn-primary:focus,
.btn-secondary:focus {
    outline: none;
    border-color: #7c5cff;
    box-shadow: 0 0 0 3px rgba(124, 92, 255, 0.20);
    color: #111827;
}

/* Disabled select clarity and dark label color fix */
#walkinShowtime:disabled{
    background: #f3f4f6;
    color: #6b7280;
    border-color: #e5e7eb;
}
#showtimeHelper{ color:#6b7280; }

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
    border-color: #6366F1;
    background: #eef2ff; /* indigo-50 */
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
    background: #7c5cff; /* primary purple */
    color: #fff;
    border: 2px solid transparent;
}

.btn-primary:hover {
    background: #6b4ae6;
}

.btn-secondary {
    background: #111827;
    color: #fff;
    border: 2px solid transparent;
}

.btn-secondary:hover {
    background: #0b1220;
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
    border-color: #7c5cff;
    box-shadow: 0 0 0 4px rgba(124, 92, 255, 0.12);
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
.btn-primary { background: #7c5cff; border: none; color: #fff; }
.btn-primary:hover { background: #6b4ae6; }
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

    // --- Walk-in booking step management ---
    const dateTimeCinemaStep = document.getElementById('dateTimeCinemaStep');
    const paxStep = document.getElementById('paxStep');
    const paymentStep = document.getElementById('paymentStep');
    const proceedToSeatsBtn = document.getElementById('proceedToSeatsBtn');
    const paxBackBtn = document.getElementById('paxBackBtn');
    const paxContinueBtn = document.getElementById('paxContinueBtn');
    const backToPaymentPaxBtn = document.getElementById('backToPaymentPaxBtn');
    const walkinShowtime = document.getElementById('walkinShowtime');
    const walkinCinema = document.getElementById('walkinCinema');
    const showtimeHelper = document.getElementById('showtimeHelper');
    const selectionSummary = document.getElementById('selectionSummary');
    const summaryText = document.getElementById('summaryText');
    const formMovieId = document.getElementById('formMovieId');
    const formShowDate = document.getElementById('formShowDate');
    const formShowtimeId = document.getElementById('formShowtimeId');
    const formCinema = document.getElementById('formCinema');
    const regularPax = document.getElementById('regularPax');
    const seniorPax = document.getElementById('seniorPax');
    const paxBreakdown = document.getElementById('paxBreakdown');
    const discountAmountEl = document.getElementById('discountAmount');
    const totalAmountEl = document.getElementById('totalAmount');
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    const gcashRefInput = document.getElementById('gcashRef');
    const selectedMovieInfo = document.getElementById('selectedMovieInfo');

    // If a movie card is clicked, reveal step 2 (Showtime & Cinema)
    const movieGrid = document.getElementById('walkinMovieGrid');
    if (movieGrid) {
        movieGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.movie-card-small');
            if (!card) return;
            // Mark selection visually
            movieGrid.querySelectorAll('.movie-card-small').forEach(el => el.classList.remove('selected'));
            card.classList.add('selected');
            // Show step 2
            dateTimeCinemaStep.style.display = 'block';
            // Update selected movie info header
            const title = card.querySelector('h4')?.textContent || 'Selected Movie';
            selectedMovieInfo.textContent = title;
            // Store selected movieId into form hidden field
            formMovieId.value = card.dataset.movieId || card.getAttribute('data-movie-id') || '';
            // Populate showtime dropdown via API (source of truth: showtimes table)
            populateShowtimesFromApi(card);
            // Sync price for breakdown
            const price = parseFloat(card.dataset.price || '0') || 0;
            // Recompute totals immediately
            syncBreakdown();
            checkStep1Ready();
        });
    }

    // Compute which showtimes to show based on current time, listing window, and movie duration
    async function populateShowtimesFromApi(card){
        if(!walkinShowtime) return;
        const movieId = card?.dataset?.movieId || card?.getAttribute('data-movie-id');
        if(!movieId){ return; }
        walkinShowtime.innerHTML = '<option value="">Select time</option>';
        walkinShowtime.disabled = true;
        if(showtimeHelper){ showtimeHelper.style.display = 'none'; }

        try{
            const res = await fetch(`/api/movies/${movieId}/showtimes/today`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            const items = Array.isArray(data?.showtimes) ? data.showtimes : [];
            if(items.length === 0){
                walkinShowtime.disabled = true;
                if(showtimeHelper){ showtimeHelper.textContent = 'No showtimes available today for this movie.'; showtimeHelper.style.display = 'block'; }
                return;
            }
            items.forEach(it => {
                const opt = document.createElement('option');
                opt.value = it.id;
                opt.textContent = it.label || it.time;
                walkinShowtime.appendChild(opt);
            });
            walkinShowtime.disabled = false;
            walkinShowtime.removeAttribute('disabled');
            if(showtimeHelper){ showtimeHelper.style.display = 'none'; }
        }catch(err){
            console.error('Failed to load showtimes', err);
            walkinShowtime.disabled = true;
            if(showtimeHelper){ showtimeHelper.textContent = 'Failed to load showtimes.'; showtimeHelper.style.display = 'block'; }
        }
    }

    // Refresh showtimes when cinema changes
    if(walkinCinema){
        walkinCinema.addEventListener('change', ()=>{
            const selCard = document.querySelector('.movie-card-small.selected');
            if(selCard){ populateShowtimesFromApi(selCard); }
            checkStep1Ready();
        });
    }

    // Continue to Pax/Payment step with basic validation
    function checkStep1Ready(){
        const movieCard = document.querySelector('.movie-card-small.selected');
        const stVal = walkinShowtime?.value || '';
        const cnVal = walkinCinema?.value || '';
        const ready = !!movieCard && !!stVal && !!cnVal;
        if(proceedToSeatsBtn) proceedToSeatsBtn.disabled = !ready;
        if(ready){
            const title = movieCard.querySelector('h4')?.textContent || 'Selected Movie';
            const stLabel = walkinShowtime.options[walkinShowtime.selectedIndex].textContent;
            const cnLabel = walkinCinema.options[walkinCinema.selectedIndex].textContent;
            selectionSummary.style.display = 'block';
            summaryText.textContent = `${title} • ${stLabel} • ${cnLabel}`;
        } else {
            if(selectionSummary) selectionSummary.style.display = 'none';
        }
    }
    if (proceedToSeatsBtn) {
        proceedToSeatsBtn.addEventListener('click', () => {
            // Persist selections
            formShowtimeId.value = walkinShowtime.value;
            formCinema.value = walkinCinema.value;
            // Go to Pax step
            paxStep.style.display = 'block';
            paxStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // Back button returns to step 1
    // Step 2 navigation
    paxBackBtn?.addEventListener('click', () => {
        paxStep.style.display = 'none';
        dateTimeCinemaStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    paxContinueBtn?.addEventListener('click', () => {
        paymentStep.style.display = 'block';
        paymentStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Keep pax summary and pricing in sync (senior-only discount)
    const parseBasePrice = () => {
        // Prefer data-price from selected card for accuracy
        const selCard = document.querySelector('.movie-card-small.selected');
        if (selCard) {
            const dp = selCard.dataset.price || '';
            const p = parseFloat(dp);
            if (!isNaN(p)) return p;
        }
        // Fallback to text parsing
        const sel = document.querySelector('.movie-card-small.selected .price');
        if (sel) {
            const val = (sel.textContent || '').replace(/[^0-9.]/g,'');
            return parseFloat(val) || 0;
        }
        return 0;
    };
    const fmt = n => '₱' + (Math.round(n * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2 });
    const syncBreakdown = () => {
        const reg = parseInt(regularPax?.value || '0', 10) || 0;
        const sen = parseInt(seniorPax?.value || '0', 10) || 0;
        const base = parseBasePrice();
        const discountRate = 0.20;
        const regularTotal = reg * base;
        const seniorTotal = sen * base * (1 - discountRate);
        const discountAmt = sen * base * discountRate;
        const total = regularTotal + seniorTotal;
        if (paxBreakdown) paxBreakdown.textContent = `${reg} Regular, ${sen} Senior`;
        if (discountAmountEl) discountAmountEl.textContent = fmt(discountAmt);
        if (totalAmountEl) totalAmountEl.textContent = fmt(total);
        if(paxContinueBtn) paxContinueBtn.disabled = (reg + sen) < 1;
    };
    [regularPax, seniorPax].forEach(el => el && el.addEventListener('input', syncBreakdown));
    syncBreakdown();

    // Require GCash reference when payment is Digital
    // Payment: show/hide GCash reference when method selected
    const gcashRefRow = document.getElementById('gcashRefRow');
    if (paymentMethodRadios && gcashRefInput && gcashRefRow) {
        const updateRefRequirement = () => {
            const method = Array.from(paymentMethodRadios).find(r => r.checked)?.value;
            const isDigital = method === 'gcash';
            gcashRefInput.required = !!isDigital;
            gcashRefInput.disabled = !isDigital;
            gcashRefRow.style.display = isDigital ? 'block' : 'none';
            if(!isDigital){ gcashRefInput.value = ''; }
        };
        paymentMethodRadios.forEach(r => r.addEventListener('change', updateRefRequirement));
        updateRefRequirement();
    }
});
</script>
@endpush

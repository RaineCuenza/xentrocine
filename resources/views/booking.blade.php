@extends('layouts.app')

@section('title', 'XentroCine — Book')
@section('page', 'booking')

@section('content')
  @auth
    @if(auth()->user()->role === 'admin')
      <div class="alert alert--error" role="alert" style="margin-bottom:1rem">
        Admins cannot create bookings.
      </div>
    @endif
  @endauth

  <div id="verificationBanner" class="verification-banner" style="display:none">
    <div class="verification-banner__content">
      <strong>Please verify your email</strong>
      <p>You need to verify your email address before you can book tickets.</p>
      <button id="resendVerificationBtn" class="btn btn--primary">Resend Verification Email</button>
    </div>
  </div>

  @if(session('message'))
    @php
      $msg = session('message');

      if (stripos($msg, 'walk-in booking confirmed') !== false) {
        $msg = 'Booking placed successfully.';
      }
    @endphp
    <div class="alert alert--success" role="alert" style="margin-top:10px">{{ $msg }}</div>
  @endif
  @if(session('error') || $errors->has('booking'))
    <div class="alert alert--error" role="alert" style="margin-top:10px">
      {{ session('error') ?? $errors->first('booking') }}
    </div>
  @endif

  
  <div class="page-wrap">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
      <div class="lg:col-span-2">
        <section class="panel">
          <h1 class="mb-0">Book Now!</h1>
          <p class="muted">Select a movie, schedule, and number of pax. Payment options: <strong>Cash</strong> or <strong>GCash</strong>.</p>
          <form id="bookingForm" action="{{ route('bookings.place') }}" method="POST" enctype="multipart/form-data" data-authed="{{ Auth::check() ? '1' : '0' }}">
            @csrf
            <h2 class="panel__title">Booking Details</h2>
            <div class="filters booking-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start">
              <label class="field-label">Movie
                <select id="movieSelect" name="movieId" data-booking-url="{{ route('booking') }}" required>
                  @if($movies->isEmpty())
                    <option value="">No movies available</option>
                  @else
                    <option value="" disabled {{ isset($selectedMovie) ? '' : 'selected' }}>Select movie</option>
                    @foreach($movies as $movie)
                      <option value="{{ $movie->id }}" data-price="{{ $movie->price }}" {{ isset($selectedMovie) && $selectedMovie->id === $movie->id ? 'selected' : '' }}>
                        {{ $movie->title }} — ₱{{ $movie->price }}
                      </option>
                    @endforeach
                  @endif
                </select>
              </label>
              <label class="field-label">Date
                @php
                  $minDate = isset($selectedMovie) && $selectedMovie->listing_start ? $selectedMovie->listing_start->format('Y-m-d') : date('Y-m-d');
                  $maxDate = isset($selectedMovie) && $selectedMovie->listing_end ? $selectedMovie->listing_end->format('Y-m-d') : null;
                  $initVal = old('show_date', $minDate);
                @endphp
                <input
                  type="date"
                  name="show_date"
                  id="show_date"
                  min="{{ $minDate }}"
                  @if($maxDate) max="{{ $maxDate }}" @endif
                  value="{{ $initVal }}"
                />
              </label>
              <label class="field-label">Showtime
                <select id="showtimeSelect" name="showtime_id" required @if(!isset($selectedMovie)) disabled @endif>
                  @if(isset($selectedMovie))
                    <option value="" disabled selected>Select showtime</option>
                    @foreach($selectedMovie->showtimes as $st)
                      <option value="{{ $st->id }}">{{ $st->formatted_range }}</option>
                    @endforeach
                  @else
                    <option value="" disabled selected>Select showtime</option>
                  @endif
                </select>
              </label>
              <label class="field-label">Cinema
                <select id="cinemaSelect" name="cinema" required disabled>
                  <option value="" disabled selected>Select cinema</option>
                  @if(isset($cinemas) && $cinemas->count() > 0)
                    @foreach($cinemas as $cinema)
                      @php
                        $status = $cinema->status ?? 'available';
                        $isAvailable = $status === 'available';
                        $suffix = $isAvailable ? '' : ($status === 'maintenance' ? ' (Maintenance)' : ' (Inactive)');
                      @endphp
                      <option value="{{ $cinema->name }}" @if(!$isAvailable) disabled @endif>
                        {{ $cinema->name }}{{ $suffix }}
                      </option>
                    @endforeach
                  @endif
                </select>
              </label>
            </div>

            <div class="pax-field">
              <label class="pax-field__label">Pax</label>
              <div class="pax-split-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
                <div class="pax-card" style="padding:10px;border-radius:10px;background:var(--panel-bg)">
                  <label class="pax-field__label" for="regularCount">Regular</label>
                  <div class="pax-field__control" role="group" aria-label="Adjust regular pax" style="height:36px;display:flex;align-items:center;gap:6px">
                    <button type="button" class="pax-btn" data-dir="down" data-target="regularCount" aria-label="Decrease regular" style="height:36px;min-width:42px">
                      <span aria-hidden="true">−</span>
                    </button>
                    <input id="regularCount" class="pax-field__input" name="quantity" type="number" inputmode="numeric" min="0" value="1" style="flex:1;min-width:0;text-align:center" />
                    <button type="button" class="pax-btn" data-dir="up" data-target="regularCount" aria-label="Increase regular" style="height:36px;min-width:42px">
                      <span aria-hidden="true">+</span>
                    </button>
                  </div>
                </div>
                <div class="pax-card" style="padding:10px;border-radius:10px;background:var(--panel-bg)">
                  <label class="pax-field__label" for="seniorCount" style="display:flex;align-items:center;gap:8px">
                    <span>Senior</span>
                  </label>
                  <div class="pax-field__control" role="group" aria-label="Adjust senior pax" style="height:36px;display:flex;align-items:center;gap:6px">
                    <button type="button" class="pax-btn" data-dir="down" data-target="seniorCount" aria-label="Decrease senior" style="height:36px;min-width:42px">
                      <span aria-hidden="true">−</span>
                    </button>
                    <input id="seniorCount" class="pax-field__input" name="seniorQuantity" type="number" inputmode="numeric" min="0" value="0" style="flex:1;min-width:0;text-align:center" />
                    <button type="button" class="pax-btn" data-dir="up" data-target="seniorCount" aria-label="Increase senior" style="height:36px;min-width:42px">
                      <span aria-hidden="true">+</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            @if(isset($selectedMovie))
              <input type="hidden" name="title" value="{{ $selectedMovie->title }}" />
              <input id="seatPriceInput" type="hidden" name="seatPrice" value="{{ $selectedMovie->price }}" />
            @else
              <input id="seatPriceInput" type="hidden" name="seatPrice" value="0" />
            @endif

            <section id="payment" class="payment" style="display:none">
              <h2 class="panel__title">Payment &amp; Confirmation</h2>
              <aside class="summary summary--emphasis" aria-live="polite" style="display:grid;grid-template-columns:180px 1fr;gap:10px 18px;align-items:start">
                <div class="summary__row"><span class="muted">Movie:</span></div><div class="summary__val"><strong id="sumMovie">Not selected yet</strong></div>
                <div class="summary__row"><span class="muted">Date:</span></div><div class="summary__val"><strong id="sumDate">Not selected yet</strong></div>
                <div class="summary__row"><span class="muted">Showtime:</span></div><div class="summary__val"><strong id="sumTime" class="is-placeholder">Not selected yet</strong></div>
                <div class="summary__row"><span class="muted">Cinema:</span></div><div class="summary__val"><strong id="sumCinema" class="is-placeholder">Not selected yet</strong></div>
                <div class="summary__row"><span class="muted">Pax:</span></div><div class="summary__val"><strong id="selectedSeats" class="is-placeholder">0</strong></div>
                <div class="summary__row"><span class="muted">Breakdown:</span></div><div class="summary__val"><strong><span id="breakdownLabel" class="is-placeholder">0 Regular, 0 Senior</span></strong></div>
                <div class="summary__row"><span class="muted">Price / Pax:</span></div><div class="summary__val"><strong>₱<span id="seatPrice">{{ isset($selectedMovie) ? $selectedMovie->price : '—' }}</span></strong></div>
                <div class="summary__row" id="discountInfo" style="display:none"><span class="muted">Discount:</span></div><div class="summary__val" id="discountVal" style="display:none"><strong><span id="discountLabel">—</span></strong></div>
                <div class="summary__row" id="seniorVerifyRow" style="display:none"></div><div class="summary__val" id="seniorVerifyVal" style="display:none;margin-top:4px"><small class="muted">Senior ID verification required at cinema</small></div>
                <div class="summary__divider" style="grid-column:1/-1;margin:10px 0;border-top:1px solid var(--divider)"></div>
                <div class="summary__row summary__total" style="grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-radius:8px;background:var(--accent-bg);border-top:1px solid rgba(255,255,255,0.08)"><span class="muted">Total:</span><strong style="color:var(--accent-text);font-size:1.2rem">₱<span id="totalPrice">—</span></strong></div>
              </aside>
              <div class="payment__content">
                <div id="seniorConfirmWrap" style="display:none; margin:8px 0 12px;">
                  <input type="hidden" name="senior_confirm" id="seniorConfirmHidden" value="0" />
                  <label class="pmethod" style="display:flex; gap:8px; align-items:flex-start;">
                    <input type="checkbox" id="seniorConfirmChk" />
                    <span>I confirm that a valid Senior Citizen ID will be presented at ticket claim.</span>
                  </label>
                  <div id="seniorConfirmError" class="form-error" style="display:none;font-size:0.85rem">Required: Please confirm Senior ID verification at the cinema to apply the discount.</div>
                </div>
                <div class="payment__methods" style="border:1px solid var(--divider);padding:12px;border-radius:10px;display:flex;gap:18px;align-items:stretch;flex-wrap:wrap">
                  <label class="pmethod" style="display:flex;align-items:flex-start;gap:8px">
                    <input type="radio" name="payment_method" value="cash" checked />
                    <div>
                      <div>Cash</div>
                      <small class="muted small" style="margin-left:2px">Reservation held for 48 hours</small>
                    </div>
                  </label>
                  <label class="pmethod" style="display:flex;align-items:flex-start;gap:8px">
                    <input type="radio" name="payment_method" value="gcash" />
                    <div>
                      <div>GCash</div>
                      <small class="muted small" style="margin-left:2px">Instant confirmation</small>
                    </div>
                  </label>
                </div>
                <div id="gcashUpload" class="upload-field" style="display:none;margin:10px 0;">
                  <label for="gcashReceipt" style="font-weight:500;margin-bottom:6px;display:block;">GCash receipt (image) <span style="color:#fda4af">*</span></label>
                  <div class="file-input-wrapper" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <label for="gcashReceipt" class="btn btn--ghost" style="cursor:pointer;margin:0;">Choose File</label>
                    <input id="gcashReceipt" type="file" name="gcash_receipt" accept="image/*" style="display:none;">
                    <span id="gcashFileName" class="muted" style="font-size:0.9rem;">No file chosen</span>
                  </div>
                  <div id="gcashReceiptError" class="form-error" style="display:none;color:#fda4af;font-size:0.85rem;margin-top:6px">Please upload your GCash payment receipt to proceed.</div>
                  @error('gcash_receipt')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                  @enderror
                  <small class="muted" style="display:block;margin-top:6px;">Upload a clear screenshot/photo of your GCash payment.</small>
                </div>
                <div id="agreementWrap" style="display:none; margin:8px 0 12px;">
                  <input type="hidden" name="agreement_confirm" id="agreementHidden" value="0" />
                  <label class="pmethod" style="display:flex; align-items:center; gap:8px">
                    <input type="checkbox" id="agreementChk" />
                    <span id="agreementText">By checking this box, I confirm that I have paid the full amount via GCash, the booking details are correct, and I understand that the booking is non-refundable.</span>
                  </label>
                  <div id="agreementError" class="form-error" style="display:none;color:#fda4af;font-size:0.85rem;margin-top:4px">Please check this box to confirm you agree to the terms.</div>
                </div>
                @error('payment_method')
                  <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror

                <div style="display:flex;justify-content:center;margin-top:14px">
                  <button type="submit" class="btn btn-primary" disabled style="height:44px;box-shadow:0 0 0 rgba(0,0,0,0);transition:box-shadow 0.2s ease">Confirm &amp; Place Booking</button>
                </div>
            </section>
          </form>
        </section>
      </div>
      <aside>
        <section class="panel">
          <h3 class="panel__title" id="my-bookings">My Bookings</h3>
          <p class="muted">Your reservation history (signed-in users only).</p>
          <ul id="bookingList" class="booking-list">
            @if(!Auth::check())
              <li class="muted">Please log in to view your bookings.</li>
            @elseif($bookings->isEmpty())
              <li class="muted">No bookings yet.</li>
            @else
              @foreach($bookings as $booking)
                <li class="booking-item">
                  @php
                    $fullPrice = $booking->items->sum(function($item) {
                      return ($item->price ?? 0) * ($item->quantity ?? 0);
                    });
                    $hasSeniorFlag = $fullPrice > 0 && (float) $booking->total < $fullPrice;
                  @endphp
                  <div class="booking-item__header">
                    <strong>{{ $booking->items->first()->movie_title ?? 'Booking' }}</strong>
                    <span class="badge badge--{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
                  </div>
                  @foreach($booking->items as $item)
                    <div class="booking-item__detail">
                      {{ \Carbon\Carbon::parse($item->booking_date)->format('M d, Y') }} •
                      @php
                        $start = date('H:i', strtotime($item->showtime));
                        $duration = optional($item->movie)->duration ?? 90;
                        $end = \Carbon\Carbon::createFromFormat('H:i', $start)->addMinutes($duration)->format('H:i');
                      @endphp
                      {{ $start }}–{{ $end }} •
                      {{ $item->cinema }} •
                      {{ $item->quantity }} pax
                    </div>
                  @endforeach
                  @if($hasSeniorFlag)
                    <div class="booking-item__detail">Senior ID to be verified at cinema</div>
                  @endif
                  <div class="booking-item__footer">
                    <span>Total: ₱{{ number_format($booking->total, 2) }}</span>
                    <span>{{ $booking->created_at->format('M d, Y') }}</span>
                  </div>
                </li>
              @endforeach
            @endif
          </ul>
        </section>
      </aside>
    </div>
  </div>

@endsection

@push('scripts')
<script>
(function () {
  const form = document.getElementById('bookingForm');
  if (!form) return;
  const movieSel = document.getElementById('movieSelect');
  const timeSel = document.getElementById('showtimeSelect');
  const cinemaSel = document.getElementById('cinemaSelect');
  const regularInput = document.getElementById('regularCount');
  const seniorInput = document.getElementById('seniorCount');
  const seatPriceEl = document.getElementById('seatPrice');
  const seatPriceInput = document.getElementById('seatPriceInput');
  const selectedSeatsEl = document.getElementById('selectedSeats');
  const totalPriceEl = document.getElementById('totalPrice');
  const paymentSection = document.getElementById('payment');
  const confirmBtn = form.querySelector('button[type="submit"]');
  const gcashField = document.getElementById('gcashUpload');
  const gcashReceiptInput = document.getElementById('gcashReceipt');
  const gcashFileName = document.getElementById('gcashFileName');
  const gcashReceiptError = document.getElementById('gcashReceiptError');
  const agreementWrap = document.getElementById('agreementWrap');
  const agreementChk = document.getElementById('agreementChk');
  const agreementHidden = document.getElementById('agreementHidden');
  const agreementError = document.getElementById('agreementError');
  const gcashReference = document.getElementById('gcashReference');
  const sumMovie = document.getElementById('sumMovie');
  const sumDate = document.getElementById('sumDate');
  const sumTime = document.getElementById('sumTime');
  const sumCinema = document.getElementById('sumCinema');
  const seniorConfirmWrap = document.getElementById('seniorConfirmWrap');
  const seniorConfirmChk = document.getElementById('seniorConfirmChk');
  const seniorConfirmHidden = document.getElementById('seniorConfirmHidden');
  const seniorConfirmError = document.getElementById('seniorConfirmError');
  const seniorVerifyRow = document.getElementById('seniorVerifyRow');

  function numeric(text) {
    const n = parseFloat(String(text).replace(/[^0-9.]/g, ''));
    return isFinite(n) ? n : 0;
  }
  function allSelected() {
    return movieSel && movieSel.value && timeSel && timeSel.value && cinemaSel && cinemaSel.value;
  }
  function recalc() {
    let reg = Math.max(0, parseInt(regularInput?.value || '0', 10));
    let sen = Math.max(0, parseInt(seniorInput?.value || '0', 10));
    let qty = reg + sen;
    if (qty > 20) {
      const overflow = qty - 20;
      if (reg >= overflow) {
        reg = reg - overflow;
        regularInput.value = String(reg);
      } else {
        const remain = overflow - reg;
        reg = 0; regularInput.value = '0';
        sen = Math.max(0, sen - remain);
        seniorInput.value = String(sen);
      }
      qty = reg + sen;
    }
    const seatText = seatPriceEl && typeof seatPriceEl.textContent === 'string' ? seatPriceEl.textContent : '';
    const seatHidden = seatPriceInput && seatPriceInput.value ? seatPriceInput.value : '';
    const price = numeric(seatText || seatHidden || 0);

    if (selectedSeatsEl) {
      selectedSeatsEl.textContent = allSelected() ? String(qty) : '0';
      selectedSeatsEl.classList.toggle('is-placeholder', !allSelected());
    }
    const regularTotal = price * reg;
    const seniorTotal = price * sen * 0.8;
    const total = regularTotal + seniorTotal;
    if (totalPriceEl) {
      totalPriceEl.textContent = allSelected() && price > 0 ? total.toFixed(2).replace(/\.00$/, '') : '—';
    }
    const breakdownLabel = document.getElementById('breakdownLabel');
    if (breakdownLabel) {
      breakdownLabel.textContent = `${reg} Regular, ${sen} Senior`;
      breakdownLabel.classList.toggle('is-placeholder', !allSelected());
    }
    const discountInfo = document.getElementById('discountInfo');
    const discountLabel = document.getElementById('discountLabel');
    const discountVal = document.getElementById('discountVal');
    const discountAmt = price * sen * 0.2;
    if (discountInfo) {
      if (sen > 0) {
        discountInfo.style.display = 'flex';
        if (discountVal) discountVal.style.display = 'flex';
        if (discountLabel) discountLabel.textContent = `₱${discountAmt.toFixed(2).replace(/\.00$/, '')} (20% on seniors)`;
      } else {
        discountInfo.style.display = 'none';
        if (discountVal) discountVal.style.display = 'none';
      }
    }
    if (seniorVerifyRow) {
      seniorVerifyRow.style.display = sen > 0 ? 'flex' : 'none';
      const v = document.getElementById('seniorVerifyVal');
      if (v) v.style.display = sen > 0 ? 'flex' : 'none';
    }
    if (seniorConfirmWrap) {
      seniorConfirmWrap.style.display = sen > 0 ? 'block' : 'none';
      if (sen <= 0) {
        if (seniorConfirmError) seniorConfirmError.style.display = 'none';
        if (seniorConfirmHidden) seniorConfirmHidden.value = '0';
        if (seniorConfirmChk) seniorConfirmChk.checked = false;
      }
    }
    const authed = form && form.dataset.authed === '1';
    if (paymentSection) {
      const canShow = allSelected() && price > 0 && authed && qty > 0;
      paymentSection.style.display = canShow ? 'block' : 'none';
    }
    if (confirmBtn) {
      const method = document.querySelector('input[name="payment_method"]:checked')?.value;
      const hasReceipt = !!gcashReceiptInput?.files?.length;
      const receiptOk = method !== 'gcash' || hasReceipt;
      const seniorOk = (sen <= 0) || !!seniorConfirmChk?.checked;
      const hasAgreement = !!agreementChk?.checked;
      const gcashAgreeOk = method !== 'gcash' || hasAgreement;
      confirmBtn.disabled = !(paymentSection?.style.display === 'block' && method && receiptOk && qty > 0 && allSelected() && seniorOk && gcashAgreeOk);
      if (method === 'gcash' && !receiptOk && paymentSection?.style.display === 'block') {
        confirmBtn.textContent = 'Upload Receipt to Continue';
      } else {
        confirmBtn.textContent = 'Confirm & Place Booking';
      }
    }
    if (sumMovie) {
      const title = movieSel?.selectedOptions?.[0]?.text?.split(' — ')[0] || 'Not selected yet';
      sumMovie.textContent = title;
      sumMovie.classList.toggle('is-placeholder', !movieSel?.value);
    }
    if (sumDate) {
      const d = document.getElementById('show_date')?.value || 'Not selected yet';
      sumDate.textContent = d;
      sumDate.classList.toggle('is-placeholder', !document.getElementById('show_date')?.value);
    }
    if (sumTime) {
      const t = timeSel?.selectedOptions?.[0]?.text || 'Not selected yet';
      sumTime.textContent = t;
      sumTime.classList.toggle('is-placeholder', !timeSel?.value);
    }
    if (sumCinema) {
      const c = cinemaSel?.value || 'Not selected yet';
      sumCinema.textContent = c;
      sumCinema.classList.toggle('is-placeholder', !cinemaSel?.value);
    }
  }

  const paxButtons = document.querySelectorAll('.pax-btn');
  paxButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      const dir = button.getAttribute('data-dir') === 'up' ? 1 : -1;
      const targetId = button.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;
      const min = Number(input.min || 0);
      const max = Number(input.max || 20);
      const current = Number(input.value || min);
      const next = Math.max(min, Math.min(max, current + dir));
      input.value = String(next);
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  if (movieSel) {
    movieSel.addEventListener('change', function() {
      const url = movieSel.dataset.bookingUrl;
      const id = movieSel.value;
      if (url && id) {
        window.location.href = url + '?movie=' + encodeURIComponent(id);
      }
    });
  }

  [regularInput, seniorInput, timeSel, cinemaSel].forEach(function(el) {
    if(el) el.addEventListener('input', recalc);
  });
  [timeSel, cinemaSel, regularInput, seniorInput].forEach(function(el) {
    if(el) el.addEventListener('change', recalc);
  });
  recalc();

  function toggleReceipt() {
    try {
      const method = document.querySelector('input[name="payment_method"]:checked')?.value;
      const isGcash = method === 'gcash';
      if (gcashField) gcashField.style.display = isGcash ? 'block' : 'none';
      if (agreementWrap) {
        agreementWrap.style.display = isGcash ? 'block' : 'none';
        if (!isGcash) {
          if (agreementHidden) agreementHidden.value = '0';
          if (agreementChk) agreementChk.checked = false;
          if (agreementError) agreementError.style.display = 'none';
        }
      }
      if (gcashReference) {
        gcashReference.style.display = isGcash ? 'block' : 'none';
      }
      if (!isGcash && gcashReceiptInput) {
        gcashReceiptInput.value = '';
        if (gcashFileName) gcashFileName.textContent = 'No file chosen';
        if (gcashReceiptError) gcashReceiptError.style.display = 'none';
      }
    } catch (e) {}
  }

  if (gcashReceiptInput) {
    gcashReceiptInput.addEventListener('change', function() {
      const file = gcashReceiptInput.files?.[0];
      if (gcashFileName) {
        gcashFileName.textContent = file ? file.name : 'No file chosen';
        gcashFileName.style.color = file ? 'var(--accent-text)' : '';
      }
      if (gcashReceiptError) gcashReceiptError.style.display = 'none';
      recalc();
    });
  }

  document.querySelectorAll('input[name="payment_method"]').forEach(function(radio){
    radio.addEventListener('change', function(){ toggleReceipt(); recalc(); });
  });
  seniorConfirmChk?.addEventListener('change', function(){
    if (seniorConfirmHidden) seniorConfirmHidden.value = seniorConfirmChk.checked ? '1' : '0';
    recalc();
  });
  agreementChk?.addEventListener('change', function(){
    if (agreementHidden) agreementHidden.value = agreementChk.checked ? '1' : '0';
    recalc();
  });
  toggleReceipt();

  form.addEventListener('submit', function(e){
    const method = document.querySelector('input[name="payment_method"]:checked')?.value;
    const isGcash = method === 'gcash';
    const sen = Math.max(0, parseInt(seniorInput?.value || '0', 10));
    let firstErrorEl = null;
    let hasErrors = false;
    if (sen > 0 && !seniorConfirmChk?.checked) {
      if (seniorConfirmError) seniorConfirmError.style.display = 'block';
      firstErrorEl = firstErrorEl || seniorConfirmWrap;
      hasErrors = true;
    }
    if (isGcash) {
      const hasReceipt = !!gcashReceiptInput?.files?.length;
      const hasAgreement = !!agreementChk?.checked;
      if (!hasAgreement) {
        if (agreementError) agreementError.style.display = 'block';
        firstErrorEl = firstErrorEl || agreementWrap;
        hasErrors = true;
      }
      if (!hasReceipt) {
        if (gcashReceiptError) gcashReceiptError.style.display = 'block';
        firstErrorEl = firstErrorEl || gcashField;
        hasErrors = true;
      }
    }
    if (confirmBtn?.disabled) {
      hasErrors = true;
      firstErrorEl = firstErrorEl || paymentSection;
    }
    if (hasErrors) {
      e.preventDefault();
      firstErrorEl?.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
      return false;
    }
  });

  confirmBtn?.addEventListener('mouseenter', function(){
    confirmBtn.style.boxShadow = '0 0 10px rgba(124,58,237,0.45)';
  });
  confirmBtn?.addEventListener('mouseleave', function(){
    confirmBtn.style.boxShadow = 'none';
  });

  function gateSelectors(){
    const hasMovie = !!movieSel?.value;
    const hasDate = !!document.getElementById('show_date')?.value;
    timeSel.disabled = !(hasMovie && hasDate);
    const shouldEnableCinema = !!timeSel.value;
    cinemaSel.disabled = !shouldEnableCinema;
    if (shouldEnableCinema) {
      reloadCinemas();
    } else {
      cinemaSel.innerHTML = '<option value="" disabled selected>Select cinema</option>';
    }
  }
  gateSelectors();
  ['change','input'].forEach(function(ev){
    document.getElementById('show_date')?.addEventListener(ev, function(){ gateSelectors(); recalc(); });
    movieSel?.addEventListener(ev, function(){ gateSelectors(); });
    timeSel?.addEventListener(ev, function(){ gateSelectors(); recalc(); });
  });

  async function reloadCinemas() {
    try {
      const prev = cinemaSel.value;
      const resp = await fetch('/api/cinemas', { headers: { 'Accept': 'application/json' } });
      if (!resp.ok) return;
      const list = await resp.json();
      const opts = [
        { value: '', label: 'Select cinema', disabled: true, selected: true }
      ];
      list.forEach(function(c){
        const isAvail = (c.status === 'available');
        const suffix = isAvail ? '' : (c.status === 'maintenance' ? ' (Maintenance)' : (c.status === 'unavailable' ? ' (Unavailable)' : ' (Inactive)'));
        opts.push({ value: c.name, label: c.name + suffix, disabled: !isAvail });
      });
      cinemaSel.innerHTML = '';
      opts.forEach(function(o){
        const opt = document.createElement('option');
        opt.value = o.value;
        opt.textContent = o.label;
        if (o.disabled) opt.disabled = true;
        if (o.selected) opt.selected = true;
        cinemaSel.appendChild(opt);
      });
      if (prev) {
        const match = Array.from(cinemaSel.options).find(op => op.value === prev && !op.disabled);
        if (match) cinemaSel.value = prev;
      }
      recalc();
    } catch (e) {}
  }
})();
</script>
@endpush

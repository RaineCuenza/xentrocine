@extends('layouts.app')

@section('title', 'XentroCine — Book')
@section('page', 'booking')

@section('content')
  <!-- No app-wide JS needed here; all actions are standard form posts. -->

  <!-- Email Verification Banner -->
  <div id="verificationBanner" class="verification-banner" style="display:none">
    <div class="verification-banner__content">
      <strong>⚠️ Please verify your email</strong>
      <p>You need to verify your email address before you can book tickets.</p>
      <button id="resendVerificationBtn" class="btn btn--primary">Resend Verification Email</button>
    </div>
  </div>

  <!-- Two-Column Layout: Book Now (Left) | My Bookings (Right) -->
  <div class="booking-layout">
    <!-- Left Column: Booking Form -->
    <div class="booking-main">
      <h1 class="mb-0">Book Now!</h1>
      <p class="muted">Select a movie, schedule, and number of pax. Payment options: <strong>Cash</strong> or
        <strong>GCash</strong>.</p>

      <form id="bookingForm" action="{{ route('cart.add') }}" method="POST" data-authed="{{ Auth::check() ? '1' : '0' }}">
        @csrf
        <div class="filters">
          <label>Movie
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
          <label>Date
            <input name="date" type="date" min="{{ date('Y-m-d') }}" value="{{ old('date', date('Y-m-d')) }}" />
          </label>
          <label>Showtime
            <select id="showtimeSelect" name="time" required>
              @if(isset($selectedMovie))
                <option value="" disabled selected>Select showtime</option>
                @foreach($selectedMovie->showtimes as $st)
                  <option value="{{ $st->showtime }}">{{ date('H:i', strtotime($st->showtime)) }}</option>
                @endforeach
              @else
                <option value="" disabled selected>Select showtime</option>
              @endif
            </select>
          </label>
          <label>Cinema
            <select id="cinemaSelect" name="cinema" required>
              <option value="" disabled selected>Select cinema</option>
              <option value="Cinema 1">Cinema 1</option>
              <option value="Cinema 2">Cinema 2</option>
            </select>
          </label>
        </div>

        <div class="pax-field">
          <label class="pax-field__label">Pax</label>
          <div class="pax-split-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
            <div>
              <label class="pax-field__label" for="regularCount">Regular</label>
              <div class="pax-field__control" role="group" aria-label="Adjust regular pax">
                <button type="button" class="pax-btn" data-dir="down" data-target="regularCount" aria-label="Decrease regular">
                  <span aria-hidden="true">−</span>
                </button>
                <input id="regularCount" class="pax-field__input" name="quantity" type="number" inputmode="numeric" min="0" max="20" value="1" />
                <button type="button" class="pax-btn" data-dir="up" data-target="regularCount" aria-label="Increase regular">
                  <span aria-hidden="true">+</span>
                </button>
              </div>
            </div>
            <div>
              <label class="pax-field__label" for="seniorCount">Senior</label>
              <div class="pax-field__control" role="group" aria-label="Adjust senior pax">
                <button type="button" class="pax-btn" data-dir="down" data-target="seniorCount" aria-label="Decrease senior">
                  <span aria-hidden="true">−</span>
                </button>
                <input id="seniorCount" class="pax-field__input" name="seniorQuantity" type="number" inputmode="numeric" min="0" max="20" value="0" />
                <button type="button" class="pax-btn" data-dir="up" data-target="seniorCount" aria-label="Increase senior">
                  <span aria-hidden="true">+</span>
                </button>
              </div>
            </div>
          </div>
          <small class="booking-field__hint">Max 20 seats total per reservation. Senior gets 20% discount.</small>
        </div>

        

        @if(isset($selectedMovie))
          <input type="hidden" name="title" value="{{ $selectedMovie->title }}" />
          <input id="seatPriceInput" type="hidden" name="seatPrice" value="{{ $selectedMovie->price }}" />
        @else
          <input id="seatPriceInput" type="hidden" name="seatPrice" value="0" />
        @endif

        <aside class="summary">
          <div class="summary__row"><span>Pax:</span><strong id="selectedSeats">—</strong></div>
          <div class="summary__row"><span>Breakdown:</span><strong><span id="breakdownLabel">—</span></strong></div>
          <div class="summary__row"><span>Price per pax:</span><strong>₱<span
                id="seatPrice">{{ isset($selectedMovie) ? $selectedMovie->price : '—' }}</span></strong></div>
          <div class="summary__row summary__total"><span>Total:</span><strong>₱<span id="totalPrice">—</span></strong>
          </div>
          <div class="summary__row" id="discountInfo" style="display:none"><span>Discount:</span><strong><span id="discountLabel">—</span></strong></div>
          <button id="addToCartBtn" class="btn" disabled>Add to cart</button>
          {{-- Auth hint without inline CSS to avoid parser issues --}}
          @auth
            <div id="authHint" class="form-help" style="display:none">Log in as Client to proceed.</div>
          @endauth
          @guest
            <div id="authHint" class="form-help">Log in as Client to proceed.</div>
          @endguest
        </aside>
      </form>


      <section class="cart">
        <h3>Cart</h3>
        <ul class="cart__list">
          @if(count($cart) === 0)
            <li class="cart__empty">Cart is empty</li>
          @else
            @foreach($cart as $index => $item)
              <li class="cart__item" data-index="{{ $index }}">
                <div class="cart__main">
                  <strong class="cart__movie">{{ $item['title'] }}</strong>
                  <span class="cart__meta">{{ \Carbon\Carbon::parse($item['date'])->format('M d, Y') }} •
                    {{ date('g:i A', strtotime($item['time'])) }} • {{ $item['cinema'] }}</span>
                  @php
                    $reg = ($item['quantity'] ?? 0) - ($item['seniorQuantity'] ?? 0);
                    $sen = ($item['seniorQuantity'] ?? 0);
                    if ($reg < 0) $reg = 0;
                  @endphp
                  <span class="cart__seats">{{ $reg + $sen }} pax ({{ $reg }} Regular / {{ $sen }} Senior)</span>
                </div>
                @php
                  $lineTotal = ($item['seatPrice'] * $reg) + ($item['seatPrice'] * $sen * 0.8);
                @endphp
                <div class="cart__price">₱<span>{{ $lineTotal }}</span></div>
                <form action="{{ route('cart.remove', $index) }}" method="POST">
                  @csrf
                  <button class="cart__remove" title="Remove">✕</button>
                </form>
              </li>
            @endforeach
          @endif
        </ul>
        @if(count($cart) > 0)
          <div class="summary__row"
            style="margin-top:6px; margin-bottom: 15px; padding-top:8px; border-top:1px solid var(--outline)">
            <span>Cart total:</span>
            <strong>₱{{ $cartTotal }}</strong>
          </div>
        @endif
        <div class="row">
          <button id="proceedPaymentBtn" type="button" class="btn btn--primary" {{ count($cart) > 0 ? '' : 'disabled' }}>Proceed to Payment</button>
          <form action="{{ route('cart.clear') }}" method="POST">
            @csrf
            <button class="btn btn--ghost" {{ count($cart) > 0 ? '' : 'disabled' }}>Clear Cart</button>
          </form>
        </div>
      </section>

      @if(count($cart) > 0)
        <section id="payment" class="payment" style="display:none">
          <h2>Payment</h2>
          <form action="{{ route('bookings.place') }}" method="POST">
            @csrf
            <div class="payment__methods">
              <label class="pmethod">
                <input type="radio" name="paymentMethod" value="cash" checked /> Cash (hold for 48 hours)
              </label>
              <label class="pmethod">
                <input type="radio" name="paymentMethod" value="digital" /> GCash (instant confirmation)
              </label>
            </div>

            <ul class="summary-list">
              @php $total = 0; @endphp
              @foreach($cart as $item)
                @php 
                  $reg = ($item['quantity'] ?? 0) - ($item['seniorQuantity'] ?? 0);
                  $sen = ($item['seniorQuantity'] ?? 0);
                  if ($reg < 0) $reg = 0;
                  $line = ($item['seatPrice'] * $reg) + ($item['seatPrice'] * $sen * 0.8);
                $total += $line; @endphp
                <li class="summary-list__item">
                  <div>
                    <strong>{{ $item['title'] }}</strong>
                    <div style="font-size:.85rem;color:var(--muted)">
                      {{ \Carbon\Carbon::parse($item['date'])->format('M d, Y') }} •
                      {{ date('g:i A', strtotime($item['time'])) }} • {{ $item['cinema'] }}</div>
                    <div style="font-size:.85rem;color:var(--muted)">{{ $reg + $sen }} pax ({{ $reg }} Regular / {{ $sen }} Senior)</div>
                  </div>
                  <div style="font-weight:600">₱{{ $line }}</div>
                </li>
              @endforeach
            </ul>

            <div class="summary__row summary__total">
              <span>Total:</span>
              <strong>₱{{ $total }}</strong>
            </div>
            <button class="btn btn--primary" {{ count($cart) > 0 ? '' : 'disabled' }}>Confirm & Place Order</button>
            <div class="form-help">Unpaid Cash reservations auto-cancel after 48 hours.</div>
          </form>
        </section>
      @endif
    </div>

    <!-- Right Column: My Bookings (Sticky Sidebar) -->
    <aside class="booking-sidebar">
      <section class="my-bookings">
        <h3>My Bookings</h3>
        <p class="muted">Your reservation history (signed-in users only).</p>
        <ul id="bookingList" class="booking-list">
          @if(!Auth::check())
            <li class="muted">Please log in to view your bookings.</li>
          @elseif($bookings->isEmpty())
            <li class="muted">No bookings yet.</li>
          @else
            @foreach($bookings as $booking)
              <li class="booking-item">
                <div class="booking-item__header">
                  <strong>{{ $booking->items->first()->movie_title ?? 'Booking' }}</strong>
                  <span class="badge badge--{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
                </div>
                @foreach($booking->items as $item)
                  <div class="booking-item__detail">
                    {{ \Carbon\Carbon::parse($item->booking_date)->format('M d, Y') }} •
                    {{ date('g:i A', strtotime($item->showtime)) }} •
                    {{ $item->cinema }} •
                    {{ $item->quantity }} pax
                  </div>
                @endforeach
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
  @if(count($cart) > 0)
    <div class="mobile-checkout-spacer"></div>
    <div class="mobile-checkout" role="region" aria-label="Mobile checkout bar">
      <div class="mobile-checkout__total">Total: <strong>₱{{ $cartTotal }}</strong></div>
      <button id="mobileProceedBtn" type="button" class="btn btn--primary">Proceed to Payment</button>
    </div>
  @endif
  {{-- No JS templates needed in non-JS flow --}}
@endsection

@push('scripts')
  <script>

    (function () {
      const form = document.getElementById('bookingForm');
      const movieSel = document.getElementById('movieSelect');
      const timeSel = document.getElementById('showtimeSelect');
      const cinemaSel = document.getElementById('cinemaSelect');
    const regularInput = document.getElementById('regularCount');
    const seniorInput = document.getElementById('seniorCount');
      const seatPriceEl = document.getElementById('seatPrice');
      const seatPriceInput = document.getElementById('seatPriceInput');
      const selectedSeatsEl = document.getElementById('selectedSeats');
      const totalPriceEl = document.getElementById('totalPrice');
      const addBtn = document.getElementById('addToCartBtn');
      const proceedBtn = document.getElementById('proceedPaymentBtn');
      const mobileProceedBtn = document.getElementById('mobileProceedBtn');
      const paymentSection = document.getElementById('payment');

      if (!form) return;

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
        // enforce 20 total seats
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
          selectedSeatsEl.textContent = allSelected() ? String(qty) : '—';
        }

        const regularTotal = price * reg;
        const seniorTotal = price * sen * 0.8; // 20% discount for seniors
        const total = regularTotal + seniorTotal;
        if (totalPriceEl) {
          totalPriceEl.textContent = allSelected() && price > 0 ? total.toFixed(2).replace(/\.00$/, '') : '—';
        }
        const breakdownLabel = document.getElementById('breakdownLabel');
        if (breakdownLabel) breakdownLabel.textContent = `${reg} Regular, ${sen} Senior`;
        const discountInfo = document.getElementById('discountInfo');
        const discountLabel = document.getElementById('discountLabel');
        const discountAmt = price * sen * 0.2;
        if (discountInfo) {
          if (sen > 0) {
            discountInfo.style.display = 'flex';
            if (discountLabel) discountLabel.textContent = `₱${discountAmt.toFixed(2).replace(/\.00$/, '')} (20% on seniors)`;
          } else {
            discountInfo.style.display = 'none';
          }
        }

        if (addBtn) {
          const authed = form && form.dataset.authed === '1';
          addBtn.disabled = !(allSelected() && price > 0 && authed && qty > 0);
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

      // When movie changes, navigate with ?movie=<id> to fetch showtimes server-side
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

      // Expand payment on demand for a cleaner flow
      function openPayment(e) {
        e.preventDefault();
        if(paymentSection){
          paymentSection.style.display = 'block';
          paymentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
          // focus the first payment radio for accessibility
          const firstRadio = paymentSection.querySelector('input[type="radio"]');
          if (firstRadio) firstRadio.focus({ preventScroll: true });
        }
      }
      if (proceedBtn && paymentSection) {
        proceedBtn.addEventListener('click', openPayment);
      }
      if (mobileProceedBtn && paymentSection) {
        mobileProceedBtn.addEventListener('click', openPayment);
      }
    })();
  </script>
@endpush

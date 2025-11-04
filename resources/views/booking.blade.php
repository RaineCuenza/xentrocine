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

      <form id="bookingForm" action="{{ route('cart.add') }}" method="POST">
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

        <div class="pax-input">
          <label for="paxInput">Number of Pax</label>
          <input id="paxInput" name="quantity" type="number" min="1" max="20" value="1" style="width: 120px;">
        </div>

        @if(isset($selectedMovie))
          <input type="hidden" name="title" value="{{ $selectedMovie->title }}" />
          <input id="seatPriceInput" type="hidden" name="seatPrice" value="{{ $selectedMovie->price }}" />
        @else
          <input id="seatPriceInput" type="hidden" name="seatPrice" value="0" />
        @endif

        <aside class="summary">
          <div class="summary__row"><span>Pax:</span><strong id="selectedSeats">—</strong></div>
          <div class="summary__row"><span>Price per pax:</span><strong>₱<span
                id="seatPrice">{{ isset($selectedMovie) ? $selectedMovie->price : '—' }}</span></strong></div>
          <div class="summary__row summary__total"><span>Total:</span><strong>₱<span id="totalPrice">—</span></strong>
          </div>
          <button id="addToCartBtn" class="btn" disabled>Add to cart</button>
          <div id="authHint" class="form-help" style="{{ Auth::check() ? 'display:none' : '' }}">Log in as Client to
            proceed.</div>
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
                  <span class="cart__seats">{{ $item['quantity'] }} pax</span>
                </div>
                <div class="cart__price">₱<span>{{ $item['seatPrice'] * $item['quantity'] }}</span></div>
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
                @php $line = $item['seatPrice'] * $item['quantity'];
                $total += $line; @endphp
                <li class="summary-list__item">
                  <div>
                    <strong>{{ $item['title'] }}</strong>
                    <div style="font-size:.85rem;color:var(--muted)">
                      {{ \Carbon\Carbon::parse($item['date'])->format('M d, Y') }} •
                      {{ date('g:i A', strtotime($item['time'])) }} • {{ $item['cinema'] }}</div>
                    <div style="font-size:.85rem;color:var(--muted)">{{ $item['quantity'] }} pax</div>
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
    // Lightweight total calculator for the booking form (no Node/SPA needed)
    (function () {
      const form = document.getElementById('bookingForm');
      const movieSel = document.getElementById('movieSelect');
      const timeSel = document.getElementById('showtimeSelect');
      const cinemaSel = document.getElementById('cinemaSelect');
      const paxInput = document.getElementById('paxInput');
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
        const qty = Math.max(1, parseInt(paxInput?.value || '1', 10));
        const price = numeric(seatPriceEl?.textContent || seatPriceInput?.value || 0);
        selectedSeatsEl.textContent = allSelected() ? String(qty) : '—';
        const total = (price * qty) || 0;
        totalPriceEl.textContent = allSelected() && price > 0 ? total.toFixed(2).replace(/\.00$/, '') : '—';

        // Enable Add to cart only when fields are selected and user is authenticated
        if (addBtn) {
          const authed = {{ Auth::check() ? 'true' : 'false' }};
          addBtn.disabled = !(allSelected() && price > 0 && authed);
        }
      }

      // When movie changes, navigate with ?movie=<id> to fetch showtimes server-side
      if (movieSel) {
        movieSel.addEventListener('change', () => {
          const url = movieSel.dataset.bookingUrl;
          const id = movieSel.value;
          if (url && id) {
            window.location.href = ${ url }?movie = ${ encodeURIComponent(id) };
          }
        });
      }

      [paxInput, timeSel, cinemaSel].forEach(el => el && el.addEventListener('input', recalc));
      [timeSel, cinemaSel].forEach(el => el && el.addEventListener('change', recalc));
      recalc();

      // Expand payment on demand for a cleaner flow
      function openPayment(e) {
        e.preventDefault();
        paymentSection.style.display = 'block';
        paymentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // focus the first payment radio for accessibility
        const firstRadio = paymentSection.querySelector('input[type="radio"]');
        if (firstRadio) firstRadio.focus({ preventScroll: true });
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
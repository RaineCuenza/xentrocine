@extends('layouts.app')

@section('title', 'Admin — XentroCine')
@section('page', 'admin')

@section('content')
<h1>Admin Dashboard</h1>
<p class="admin-intro">Monitor cinema performance and keep your catalogue up to date.</p>
<div class="admin-kpis">
  <div class="kpi kpi--movies">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="16" rx="2.5" />
        <line x1="9" y1="4" x2="9" y2="20" />
        <line x1="15" y1="4" x2="15" y2="20" />
        <line x1="3" y1="10" x2="21" y2="10" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Total Movies</span>
      <span class="kpi__value">{{ $kpis['movies'] }}</span>
      <span class="kpi__hint">Active titles</span>
    </div>
  </div>
  <div class="kpi kpi--bookings">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 6h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4V6z" />
        <path d="M12 10h4" />
        <path d="M12 14h4" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Total Bookings</span>
      <span class="kpi__value">{{ $kpis['bookings'] }}</span>
      <span class="kpi__hint">Reservations recorded</span>
    </div>
  </div>
  <div class="kpi kpi--paid">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 6.5a8 8 0 1 1-8-4.5" />
        <polyline points="20 2 20 6.5 15.5 6.5" />
        <polyline points="9.5 12 11.5 14 15 10.5" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Paid</span>
      <span class="kpi__value">{{ $kpis['paid'] }}</span>
      <span class="kpi__hint">Settled payments</span>
    </div>
  </div>
  <div class="kpi kpi--pending">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="8" />
        <polyline points="12 8 12 12 15 13.5" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Pending</span>
      <span class="kpi__value">{{ $kpis['pending'] }}</span>
      <span class="kpi__hint">Awaiting payment</span>
    </div>
  </div>
</div>

<div class="admin-grid">
  <section class="admin-panel">
    <header class="admin-panel__header">
      <div>
        <h2>Manage Movies</h2>
        <p class="admin-panel__hint">Create, update, or retire titles across your catalogue.</p>
      </div>
      <span class="admin-panel__tag">{{ $movies->count() }} active</span>
    </header>
    <div class="admin-panel__body">
      <form action="{{ url('/admin/movies') }}" method="POST" class="admin-form" novalidate>
        @csrf
        <div class="admin-form__grid">
          <label class="admin-field">
            <span class="admin-field__label">Title</span>
            <input name="title" type="text" placeholder="e.g., Hidden Tide" required/>
          </label>
          <label class="admin-field admin-field--compact">
            <span class="admin-field__label">Price (₱)</span>
            <input name="price" type="number" min="100" step="0.01" placeholder="350" required/>
          </label>
          <label class="admin-field">
            <span class="admin-field__label">Audience</span>
            <select name="audience" required>
              <option value="PG" selected>PG</option>
              <option value="G">G</option>
              <option value="R-13">R-13</option>
              <option value="R-16">R-16</option>
              <option value="R-18">R-18</option>
            </select>
          </label>
          <label class="admin-field">
            <span class="admin-field__label">Poster</span>
            <input name="poster" type="text" placeholder="/images/movie.jpg or full URL" />
          </label>
          <label class="admin-field admin-field--wide">
            <span class="admin-field__label">Showtimes</span>
            <input name="times" type="text" placeholder="Example: 13:00, 15:30, 20:00" required/>
          </label>
        </div>
        <div class="admin-form__actions">
          <button class="btn btn--primary" type="submit">Add Movie</button>
        </div>
      </form>
      
      <ul id="adminMovieList" class="admin-collection">
        @forelse($movies as $movie)
          <li class="admin-collection__item">
            <div class="admin-collection__meta">
              <h3 class="admin-collection__title">{{ $movie->title }}</h3>
              <p class="admin-collection__subtitle">
                ₱{{ number_format($movie->price, 2) }} • {{ $movie->audience }} • {{ $movie->booking_items_count ?? 0 }} bookings
              </p>
            </div>
            <form action="{{ url('/admin/movies/'.$movie->id.'/delete') }}" method="POST" class="admin-collection__action">
              @csrf
              <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
            </form>
          </li>
        @empty
          <li class="admin-collection__empty">No movies yet</li>
        @endforelse
      </ul>
    </div>
  </section>

  <section class="admin-panel">
    <header class="admin-panel__header">
      <div>
        <h2>All Bookings</h2>
        <p class="admin-panel__hint">Recent reservations with current payment status.</p>
      </div>
      <span class="admin-panel__tag">{{ $bookings->count() }} on record</span>
    </header>
    <div class="admin-panel__body">
      <ul id="adminBookingList" class="booking-list booking-list--dense">
        @forelse($bookings as $booking)
          <li class="booking-item booking-item--admin">
            <div class="booking-item__header">
              <div>
                <strong class="booking-item__title">{{ $booking->name }}</strong>
                <span class="booking-item__subtitle">{{ $booking->email }}</span>
              </div>
              <span class="badge badge--{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
            </div>
            @foreach($booking->items as $item)
              <div class="booking-item__detail">
                <span>{{ $item->movie_title }}</span>
                <span>{{ \Carbon\Carbon::parse($item->booking_date)->format('M d, Y') }}</span>
                <span>{{ date('g:i A', strtotime($item->showtime)) }}</span>
                <span>{{ $item->cinema }}</span>
                <span>{{ $item->quantity }} pax</span>
              </div>
            @endforeach
            <div class="booking-item__footer">
              <span class="booking-item__total">Total: ₱{{ number_format($booking->total, 2) }}</span>
              <span class="booking-item__timestamp">{{ $booking->created_at->format('M d, Y g:i A') }}</span>
            </div>
          </li>
        @empty
          <li class="booking-item booking-item--empty">No bookings yet</li>
        @endforelse
      </ul>
    </div>
  </section>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const showtimeInput = document.querySelector('input[name="times"]');
            if (!showtimeInput || typeof flatpickr === 'undefined') return;

            const normalized = showtimeInput.value
                .replace(/[\u2012-\u2015]/g, '-') // normalize dash variants
                .replace(/-/g, ',');

            const presetTimes = normalized
                .split(',')
                .map(time => time.trim())
                .filter(Boolean);

            const updateFromPicker = pickerInstance => {
                const values = (pickerInstance.selectedDates || [])
                    .map(date => pickerInstance.formatDate(date, 'H:i'));
                showtimeInput.value = values.join(', ');
            };

            const picker = flatpickr(showtimeInput, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                minuteIncrement: 15,
                allowInput: true,
                mode: 'multiple',
                defaultDate: presetTimes,
                time_24hr: true,
                conjunction: ', ',
                onValueUpdate: updateFromPicker,
                onClose: updateFromPicker
            });

            if(presetTimes.length){
                showtimeInput.value = presetTimes.join(', ');
            }

            const normalizeInput = () => {
                const raw = showtimeInput.value
                    .split(',')
                    .map(entry => entry.trim())
                    .filter(Boolean);

                if(raw.length){
                    const formatted = raw
                        .map(entry => picker.parseDate(entry, 'H:i') || picker.parseDate(entry, 'G:i') || picker.parseDate(entry, 'h:i K'))
                        .filter(Boolean)
                        .map(date => picker.formatDate(date, 'H:i'));

                    picker.setDate(formatted, false);
                    showtimeInput.value = formatted.join(', ');
                }else{
                    picker.clear();
                    showtimeInput.value = '';
                }
            };

            showtimeInput.addEventListener('blur', () => updateFromPicker(picker));

            showtimeInput.addEventListener('change', normalizeInput);

            showtimeInput.addEventListener('keydown', (event) => {
                if(event.key === 'Enter'){
                    event.preventDefault();
                    normalizeInput();
                    picker.open();
                }
            });
        });
    </script>
@endpush

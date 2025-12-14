@extends('layouts.app')

@section('title', 'Admin Manage — XentroCine')
@section('page', 'admin-manage')

@php
  $activeTab = request('tab', 'movies');
@endphp

@push('styles')
<style>
.manage-shell {max-width: 1100px; margin: 0 auto; padding: 0 1.25rem 3rem;}
.manage-header {margin: 1.5rem 0;}
.manage-header h1 {margin: 0 0 .35rem 0;}
.manage-header p {margin: 0; color: rgba(231,237,243,.7);}
.manage-kpis {margin-bottom: 1.5rem;}
.manage-card {background: rgba(17,20,29,.8); border: 1px solid rgba(255,255,255,.05); border-radius: 18px; overflow: hidden;}
.manage-tabs {display: flex; gap: .5rem; padding: .85rem; border-bottom: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.02);}
.manage-tab {border: 1px solid transparent; background: transparent; color: rgba(231,237,243,.65); padding: .7rem 1.2rem; border-radius: 12px; font-weight: 700; cursor: pointer; display: inline-flex; gap: .5rem; align-items: center;}
.manage-tab:hover {color: #fff; border-color: rgba(255,255,255,.08);}
.manage-tab--active {background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; border-color: rgba(124,92,255,.4); box-shadow: 0 0 20px rgba(124,92,255,.25);}
.manage-content {padding: 1.5rem;}
.manage-section-header {display:flex; align-items:center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;}
.manage-section-title {margin:0; font-size: 1.2rem;}
.manage-section-subtitle {margin:0; color: rgba(231,237,243,.65);}
.manage-movies-list {display: grid; gap: 1rem;}
.manage-movie-item, .manage-booking-card, .manage-cinema-card {background: rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 1rem;}
.manage-movie-header, .manage-booking-header, .manage-cinema-header {display:flex; align-items:flex-start; justify-content: space-between; gap: .75rem;}
.manage-movie-title {margin:0;}
.manage-movie-meta {margin: .2rem 0 0 0; color: rgba(231,237,243,.7); font-size: .95rem;}
.manage-status {padding: .3rem .75rem; border-radius: 999px; font-weight: 700; font-size: .8rem;}
.manage-status--active {background: rgba(34,197,94,.15); color:#34d399; border:1px solid rgba(34,197,94,.4);}
.manage-status--inactive {background: rgba(148,163,184,.18); color:#cbd5e1; border:1px solid rgba(148,163,184,.3);}
.manage-movie-showtimes {display:flex; flex-wrap:wrap; gap: .4rem; margin: .75rem 0;}
.manage-time-chip-display {padding: .35rem .7rem; border-radius: 8px; background: rgba(124,92,255,.12); border:1px solid rgba(124,92,255,.25); color:#d8b4fe; font-weight:700; font-size:.82rem;}
.manage-movie-actions {display:flex; gap:.5rem; flex-wrap:wrap;}
.manage-archived-list {display:grid; gap:.75rem;}
.manage-cinema-grid {display:grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap:1rem;}
.manage-cinema-title {margin:0;}
.manage-cinema-capacity {margin:.2rem 0 0 0; color: rgba(231,237,243,.7);}
.manage-badge {padding:.45rem .9rem; border-radius:999px; background: rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.12); color: rgba(231,237,243,.85); font-weight:700;}
.manage-badge--blue {background: rgba(59,130,246,.15); border-color: rgba(59,130,246,.35); color:#bfdbfe;}
.manage-filters {display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom: 1rem;}
.manage-search {flex:1; min-width:240px;}
.manage-search-input {width:100%; padding:.65rem .85rem; border-radius:10px; border:1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.03); color:#fff;}
.manage-booking-card {display:grid; grid-template-columns: 1fr auto; gap:1rem;}
.manage-booking-main {display:grid; gap:.5rem;}
.manage-booking-grid {display:grid; grid-template-columns: repeat(auto-fit,minmax(140px,1fr)); gap:.6rem;}
.manage-booking-status {padding:.3rem .7rem; border-radius:999px; font-weight:700; font-size:.78rem; text-transform: uppercase;}
.manage-booking-status--paid {background:#10b981; color:#fff;}
.manage-booking-status--pending {background:#f59e0b; color:#fff;}
.manage-booking-status--cancelled {background:#ef4444; color:#fff;}
.manage-booking-actions {display:flex; gap:.5rem; align-items:flex-start;}
.manage-booking-email, .manage-booking-timestamp {margin:0; color: rgba(231,237,243,.6);}
.manage-detail-label {color: rgba(231,237,243,.6); font-size:.78rem; margin:0;}
.manage-detail-value {margin:0; font-weight:700;}
.manage-detail-sub {margin:0; color: rgba(231,237,243,.55); font-size:.8rem;}
.manage-modal {background: rgba(13,15,22,.92); border:1px solid rgba(124,92,255,.35); border-radius:18px; padding:1.25rem; box-shadow:0 24px 60px rgba(0,0,0,.55); max-width:700px; width:100%;}
.manage-modal-overlay {position:fixed; inset:0; background:rgba(0,0,0,.75); display:none; align-items:center; justify-content:center; padding:1rem; z-index:1200;}
.manage-form__grid {display:grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap:1rem;}
.manage-field {display:grid; gap:.35rem;}
.manage-field__label {font-size:.85rem; text-transform: uppercase; letter-spacing:.05em; color:rgba(231,237,243,.7);}
.manage-input, .manage-select {width:100%; padding:.75rem .85rem; border-radius:10px; border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.04); color:#fff;}
.manage-price-control {display:flex; align-items:center; gap:.35rem; border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:.2rem; background: rgba(255,255,255,.04);}
.manage-price-input {flex:1; background:transparent; border:none; color:#fff; text-align:center; font-weight:700;}
.manage-price-btn {width:38px; height:38px; border:none; border-radius:8px; background:rgba(255,255,255,.06); color:#fff; cursor:pointer;}
.manage-showtime-presets {display:flex; flex-wrap:wrap; gap:.35rem;}
.manage-preset-btn {padding:.55rem .9rem; border-radius:9px; background: rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.12); color:rgba(231,237,243,.8); cursor:pointer; font-weight:700;}
.manage-preset-btn.active {background: linear-gradient(135deg,#7c3aed,#6d28d9); border-color: transparent; color:#fff;}
.manage-time-chips {display:flex; flex-wrap:wrap; gap:.4rem; min-height:2rem;}
.manage-time-chip {display:flex; align-items:center; gap:.35rem; padding:.4rem .7rem; border-radius:9px; background: rgba(124,92,255,.18); border:1px solid rgba(124,92,255,.35); color:#e9d5ff; font-weight:700;}
.manage-time-chip button {border:none; background:rgba(0,0,0,.2); color:#fff; border-radius:50%; width:20px; height:20px; cursor:pointer;}
.manage-form__actions {display:flex; justify-content:flex-end; gap:.6rem; margin-top:.5rem;}
@media (max-width: 720px){
  .manage-booking-card{grid-template-columns:1fr;}
  .manage-tabs{flex-wrap:wrap;}
}
</style>
@endpush

@section('content')
<div class="manage-shell" data-active-tab="{{ $activeTab }}">
  @if(session('message'))
    <div class="alert alert--success">{{ session('message') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert--error">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert--error">
      <ul style="margin:0; padding-left:1rem;">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="manage-header">
    <h1>Manage Movies</h1>
    <p>Monitor cinema performance and keep your catalogue up to date.</p>
  </div>

  <div class="analytics-kpis manage-kpis">
    <div class="kpi kpi--purple">
      <div class="kpi__meta">
        <span class="kpi__label">Total Movies</span>
        <span class="kpi__value">{{ $kpis['movies'] ?? 0 }}</span>
        <span class="kpi__hint">Active titles</span>
      </div>
    </div>
    <div class="kpi kpi--blue">
      <div class="kpi__meta">
        <span class="kpi__label">Total Bookings</span>
        <span class="kpi__value">{{ $kpis['bookings'] ?? 0 }}</span>
        <span class="kpi__hint">Reservations recorded</span>
      </div>
    </div>
    <div class="kpi kpi--green">
      <div class="kpi__meta">
        <span class="kpi__label">Paid</span>
        <span class="kpi__value">{{ $kpis['paid'] ?? 0 }}</span>
        <span class="kpi__hint">Settled payments</span>
      </div>
    </div>
    <div class="kpi kpi--orange">
      <div class="kpi__meta">
        <span class="kpi__label">Pending</span>
        <span class="kpi__value">{{ $kpis['pending'] ?? 0 }}</span>
        <span class="kpi__hint">Awaiting payment</span>
      </div>
    </div>
  </div>

  <div class="manage-card">
    <div class="manage-tabs">
      <button class="manage-tab {{ $activeTab === 'movies' ? 'manage-tab--active' : '' }}" data-tab="movies">Manage Movies</button>
      <button class="manage-tab {{ $activeTab === 'archived' ? 'manage-tab--active' : '' }}" data-tab="archived">Archived</button>
      <button class="manage-tab {{ $activeTab === 'cinemas' ? 'manage-tab--active' : '' }}" data-tab="cinemas">Manage Cinemas</button>
      <button class="manage-tab {{ $activeTab === 'bookings' ? 'manage-tab--active' : '' }}" data-tab="bookings">All Bookings</button>
    </div>

    <div class="manage-content">
      {{-- Movies Tab --}}
      <div class="manage-tab-content {{ $activeTab === 'movies' ? 'manage-tab-content--active' : '' }}" data-content="movies" {{ $activeTab === 'movies' ? '' : 'hidden' }}>
        <div class="manage-section-header">
          <div>
            <h3 class="manage-section-title">Movie Catalogue</h3>
            <p class="manage-section-subtitle">Active titles with showtimes</p>
          </div>
          <button class="btn btn--primary" id="addMovieBtn">+ Add Movie</button>
        </div>

        <div class="manage-movies-list">
          @forelse($moviesActive as $movie)
            @php
              $showtimes = $movie->showtimes->map(fn($st) => ['showtime' => $st->showtime]);
              $moviePayload = [
                'id' => $movie->id,
                'title' => $movie->title,
                'price' => $movie->price,
                'poster' => $movie->poster,
                'audience' => $movie->audience,
                'listing_start' => $movie->listing_start ? \Carbon\Carbon::parse($movie->listing_start)->toDateString() : null,
                'listing_end' => $movie->listing_end ? \Carbon\Carbon::parse($movie->listing_end)->toDateString() : null,
                'showtimes' => $showtimes,
              ];
            @endphp
            <article class="manage-movie-item">
              <div class="manage-movie-header">
                <div>
                  <h4 class="manage-movie-title">{{ $movie->title }}</h4>
                  <p class="manage-movie-meta">
                    ₱{{ number_format($movie->price, 2) }} • {{ $movie->audience }} • {{ $movie->booking_items_count ?? 0 }} bookings
                  </p>
                </div>
                <span class="manage-status manage-status--active">Active</span>
              </div>
              <div class="manage-movie-showtimes">
                @foreach($movie->showtimes as $st)
                  @php
                    $time = $st->showtime;
                    try { $time = \Carbon\Carbon::createFromFormat('H:i', $st->showtime)->format('g:i A'); } catch (\Exception $e) {}
                  @endphp
                  <span class="manage-time-chip-display">{{ $time }}</span>
                @endforeach
              </div>
              <div class="manage-movie-actions">
                <button type="button" class="btn btn--ghost btn--sm edit-movie-btn" data-movie='@json($moviePayload)'>Edit</button>
                <form action="{{ url('/admin/movies/' . $movie->id . '/status') }}" method="POST" style="display:inline">
                  @csrf
                  <input type="hidden" name="status" value="inactive">
                  <button type="submit" class="btn btn--ghost btn--sm" style="color:#fda4af;border-color:rgba(244,63,94,.35)">Archive</button>
                </form>
              </div>
            </article>
          @empty
            <p class="muted">No active movies yet.</p>
          @endforelse
        </div>
        @if(method_exists($moviesActive, 'links'))
          <div class="manage-pagination">{{ $moviesActive->appends(['tab'=>'movies'])->links() }}</div>
        @endif
      </div>

      {{-- Archived Tab --}}
      <div class="manage-tab-content {{ $activeTab === 'archived' ? 'manage-tab-content--active' : '' }}" data-content="archived" {{ $activeTab === 'archived' ? '' : 'hidden' }}>
        <div class="manage-section-header">
          <div>
            <h3 class="manage-section-title">Archived Movies</h3>
            <p class="manage-section-subtitle">Inactive titles you can bring back.</p>
          </div>
        </div>
        <div class="manage-archived-list">
          @forelse($moviesArchived as $movie)
            <article class="manage-movie-item">
              <div class="manage-movie-header">
                <div>
                  <h4 class="manage-movie-title">{{ $movie->title }}</h4>
                  <p class="manage-movie-meta">₱{{ number_format($movie->price,2) }} • {{ $movie->audience }} • {{ $movie->booking_items_count ?? 0 }} bookings</p>
                </div>
                <span class="manage-status manage-status--inactive">Inactive</span>
              </div>
              <div class="manage-movie-actions">
                <form action="{{ url('/admin/movies/' . $movie->id . '/status') }}" method="POST">
                  @csrf
                  <input type="hidden" name="status" value="active">
                  <button type="submit" class="btn btn--primary btn--sm">Unarchive</button>
                </form>
              </div>
            </article>
          @empty
            <p class="muted">No archived titles.</p>
          @endforelse
        </div>
        @if(method_exists($moviesArchived, 'links'))
          <div class="manage-pagination">{{ $moviesArchived->appends(['tab'=>'archived'])->links() }}</div>
        @endif
      </div>

      {{-- Cinemas Tab --}}
      <div class="manage-tab-content {{ $activeTab === 'cinemas' ? 'manage-tab-content--active' : '' }}" data-content="cinemas" {{ $activeTab === 'cinemas' ? '' : 'hidden' }}>
        <div class="manage-section-header">
          <div>
            <h3 class="manage-section-title">Cinema Management</h3>
            <p class="manage-section-subtitle">Control availability for customer bookings</p>
          </div>
          <span class="manage-badge">{{ $cinemas->count() }} Cinemas</span>
        </div>
        <div class="manage-cinema-grid">
          @foreach($cinemas as $cinema)
            <div class="manage-cinema-card">
              <div class="manage-cinema-header">
                <div>
                  <h4 class="manage-cinema-title">{{ $cinema->name }}</h4>
                  <p class="manage-cinema-capacity">Capacity: {{ $cinema->capacity }}</p>
                </div>
                <span class="manage-status {{ $cinema->status === 'available' ? 'manage-status--active' : 'manage-status--inactive' }}">{{ ucfirst($cinema->status) }}</span>
              </div>
              <form action="{{ url('/admin/cinemas/' . $cinema->id) }}" method="POST" class="manage-cinema-form">
                @csrf
                <div class="manage-field">
                  <label class="manage-field__label">Status</label>
                  <select name="status" class="manage-select" required>
                    <option value="available" {{ $cinema->status === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="maintenance" {{ $cinema->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="unavailable" {{ $cinema->status === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                    <option value="inactive" {{ $cinema->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                  </select>
                </div>
                <div class="manage-field">
                  <label class="manage-field__label">Capacity</label>
                  <input type="number" name="capacity" class="manage-input" min="1" max="500" value="{{ $cinema->capacity }}">
                </div>
                <button type="submit" class="btn btn--primary btn--full">Save</button>
              </form>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Bookings Tab --}}
      <div class="manage-tab-content {{ $activeTab === 'bookings' ? 'manage-tab-content--active' : '' }}" data-content="bookings" {{ $activeTab === 'bookings' ? '' : 'hidden' }}>
        <div class="manage-section-header">
          <div>
            <h3 class="manage-section-title">All Bookings</h3>
            <p class="manage-section-subtitle">Recent reservations with current payment status</p>
          </div>
          <span class="manage-badge manage-badge--blue">{{ $bookings->total() }} On Record</span>
        </div>
        <form method="GET" action="{{ route('admin.manage') }}" class="manage-filters">
          <input type="hidden" name="tab" value="bookings">
          <div class="manage-search">
            <input type="text" name="b_q" value="{{ $filters['b_q'] ?? '' }}" placeholder="Search movie title..." class="manage-search-input" />
          </div>
          <select name="b_status" class="manage-select">
            <option value="all" {{ ($filters['b_status'] ?? 'all') === 'all' ? 'selected' : '' }}>All Status</option>
            <option value="Paid" {{ ($filters['b_status'] ?? '') === 'Paid' ? 'selected' : '' }}>Paid</option>
            <option value="Pending" {{ ($filters['b_status'] ?? '') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Cancelled" {{ ($filters['b_status'] ?? '') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
          <input type="date" name="b_from" class="manage-input" value="{{ $filters['b_from'] ?? '' }}">
          <input type="date" name="b_to" class="manage-input" value="{{ $filters['b_to'] ?? '' }}">
          <button type="submit" class="btn btn--ghost">Apply</button>
          <a href="{{ route('admin.manage', ['tab'=>'bookings']) }}" class="btn btn--ghost">Clear Filters</a>
        </form>

        <div class="manage-bookings">
          @forelse($bookings as $booking)
            @php
              $item = $booking->items->first();
              $movieTitle = $item?->movie_title ?? 'Movie';
              $dateStr = $item?->booking_date ? \Carbon\Carbon::parse($item->booking_date)->format('M d, Y') : $booking->created_at->format('M d, Y');
              $timeStr = $item?->showtime ? date('H:i', strtotime($item->showtime)) : '—';
              $cinemaName = $item?->cinema ?? '—';
              $seats = $item?->quantity ?? 0;
            @endphp
            <article class="manage-booking-card">
              <div class="manage-booking-main">
                <div class="manage-booking-header">
                  <div>
                    <h4 class="manage-booking-customer">{{ $booking->name ?? 'Guest' }}</h4>
                    <p class="manage-booking-email">{{ $booking->email }}</p>
                  </div>
                  <span class="manage-booking-status manage-booking-status--{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
                </div>
                <div class="manage-booking-grid">
                  <div>
                    <p class="manage-detail-label">Movie</p>
                    <p class="manage-detail-value">{{ $movieTitle }}</p>
                  </div>
                  <div>
                    <p class="manage-detail-label">Date & Time</p>
                    <p class="manage-detail-value">{{ $dateStr }}</p>
                    <p class="manage-detail-sub">{{ $timeStr }}</p>
                  </div>
                  <div>
                    <p class="manage-detail-label">Cinema</p>
                    <p class="manage-detail-value">{{ $cinemaName }}</p>
                    <p class="manage-detail-sub">{{ $seats }} seat(s)</p>
                  </div>
                  <div>
                    <p class="manage-detail-label">Total</p>
                    <p class="manage-detail-value">₱{{ number_format($booking->total, 2) }}</p>
                  </div>
                </div>
                <p class="manage-booking-timestamp">Booked: {{ $booking->created_at->format('M d, Y g:i A') }}</p>
              </div>
              <div class="manage-booking-actions">
                <form action="{{ url('/admin/bookings/' . $booking->id . '/cancel') }}" method="POST" onsubmit="return confirm('Cancel this booking?')" style="margin:0">
                  @csrf
                  <button type="submit" class="btn btn--ghost btn--sm" style="color:#fda4af;border-color:rgba(244,63,94,.35)">Cancel</button>
                </form>
              </div>
            </article>
          @empty
            <p class="muted">No bookings found.</p>
          @endforelse
        </div>
        <div class="manage-pagination">{{ $bookings->appends(['tab'=>'bookings'])->links() }}</div>
      </div>
    </div>
  </div>
</div>

{{-- Movie Modal --}}
<div class="manage-modal-overlay" id="movieModalOverlay">
  <div class="manage-modal" id="movieModal" role="dialog" aria-modal="true" aria-labelledby="movieModalTitle">
    <div class="manage-modal__header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
      <h3 class="manage-modal__title" id="movieModalTitle">Add New Movie</h3>
      <button class="manage-modal__close" id="closeMovieModal" aria-label="Close">×</button>
    </div>
    <form id="movieForm" action="{{ url('/admin/movies') }}" method="POST" class="manage-form">
      @csrf
      <div class="manage-form__grid">
        <div class="manage-field">
          <label class="manage-field__label">Title</label>
          <input name="title" type="text" placeholder="e.g., Hidden Tide" required class="manage-input" />
        </div>
        <div class="manage-field">
          <label class="manage-field__label">Price (₱)</label>
          <div class="manage-price-control">
            <button type="button" class="manage-price-btn" data-price-dec>-</button>
            <input name="price" type="number" min="100" step="50" value="350" required class="manage-price-input" />
            <button type="button" class="manage-price-btn" data-price-inc>+</button>
          </div>
        </div>
        <div class="manage-field">
          <label class="manage-field__label">Audience Rating</label>
          <select name="audience" required class="manage-select">
            <option value="G">G</option>
            <option value="PG" selected>PG</option>
            <option value="R-13">R-13</option>
            <option value="R-16">R-16</option>
            <option value="R-18">R-18</option>
          </select>
        </div>
        <div class="manage-field">
          <label class="manage-field__label">Poster URL</label>
          <input name="poster" type="text" placeholder="/images/movie.jpg or full URL" class="manage-input" />
        </div>
        <div class="manage-field">
          <label class="manage-field__label">Listing Start</label>
          <input name="listing_start" type="date" value="{{ now()->toDateString() }}" required class="manage-input" />
        </div>
        <div class="manage-field">
          <label class="manage-field__label">Listing End</label>
          <input name="listing_end" type="date" value="{{ now()->toDateString() }}" required class="manage-input" />
        </div>
        <div class="manage-field manage-field--wide">
          <label class="manage-field__label">Showtimes</label>
          <div class="manage-showtimes">
            <div class="manage-showtime-presets">
              <button type="button" class="manage-preset-btn" data-preset="Morning">Morning</button>
              <button type="button" class="manage-preset-btn" data-preset="Matinee">Matinee</button>
              <button type="button" class="manage-preset-btn" data-preset="Evening">Evening</button>
              <button type="button" class="manage-preset-btn" data-preset="Late Night">Late Night</button>
            </div>
            <div class="manage-custom-time" style="display:flex;gap:.5rem;">
              <input type="time" id="customTime" class="manage-input" />
              <button type="button" class="btn btn--ghost btn--sm" id="addCustomTime">+ Add Custom</button>
            </div>
            <div class="manage-time-chips" id="timeChips"></div>
            <input type="hidden" name="times" id="showtimesInput" required />
          </div>
        </div>
      </div>
      <div class="manage-form__actions">
        <button type="button" class="btn btn--ghost" id="cancelMovieBtn">Cancel</button>
        <button type="submit" class="btn btn--primary" id="movieSubmitBtn">Add Movie</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
let editingMovieId = null;
const presetTimes = {
  'Morning': '10:00',
  'Matinee': '13:00',
  'Evening': '16:00',
  'Late Night': '19:30'
};
window.selectedShowtimes = new Set();

function renderTimeChips() {
  const timeChips = document.getElementById('timeChips');
  const showtimesInput = document.getElementById('showtimesInput');
  if (!timeChips) return;
  timeChips.innerHTML = '';
  Array.from(selectedShowtimes).sort().forEach(time => {
    const chip = document.createElement('div');
    chip.className = 'manage-time-chip';
    chip.innerHTML = `<span>${time}</span><button type="button" aria-label="Remove ${time}">×</button>`;
    chip.querySelector('button').onclick = () => {
      selectedShowtimes.delete(time);
      renderTimeChips();
    };
    timeChips.appendChild(chip);
  });
  if (showtimesInput) {
    showtimesInput.value = Array.from(selectedShowtimes).join(',');
  }
}

function resetMovieForm() {
  editingMovieId = null;
  const overlay = document.getElementById('movieModalOverlay');
  const modal = document.getElementById('movieModal');
  const form = document.getElementById('movieForm');
  const submitBtn = document.getElementById('movieSubmitBtn');
  const titleEl = document.getElementById('movieModalTitle');
  form.action = '/admin/movies';
  form.querySelector('input[name="_method"]')?.remove();
  titleEl.textContent = 'Add New Movie';
  submitBtn.textContent = 'Add Movie';
  form.reset();
  selectedShowtimes.clear();
  renderTimeChips();
  document.querySelectorAll('.manage-preset-btn').forEach(btn => btn.classList.remove('active'));
  overlay.style.display = 'none';
  modal.hidden = true;
}

function editMovie(movieData) {
  const overlay = document.getElementById('movieModalOverlay');
  const modal = document.getElementById('movieModal');
  const form = document.getElementById('movieForm');
  const submitBtn = document.getElementById('movieSubmitBtn');
  const titleEl = document.getElementById('movieModalTitle');
  editingMovieId = movieData.id;
  form.action = `/admin/movies/${movieData.id}/update`;
  form.querySelector('input[name="_method"]')?.remove();
  titleEl.textContent = 'Edit Movie';
  submitBtn.textContent = 'Update Movie';

  form.querySelector('[name="title"]').value = movieData.title || '';
  form.querySelector('[name="price"]').value = movieData.price || 350;
  form.querySelector('[name="audience"]').value = movieData.audience || 'PG';
  form.querySelector('[name="poster"]').value = movieData.poster || '';
  if (movieData.listing_start) form.querySelector('[name="listing_start"]').value = movieData.listing_start.substr(0,10);
  if (movieData.listing_end) form.querySelector('[name="listing_end"]').value = movieData.listing_end.substr(0,10);

  selectedShowtimes.clear();
  document.querySelectorAll('.manage-preset-btn').forEach(btn => btn.classList.remove('active'));
  if (movieData.showtimes && Array.isArray(movieData.showtimes)) {
    movieData.showtimes.forEach(st => {
      const t = (st.showtime || '').substr(0,5);
      if (t) {
        selectedShowtimes.add(t);
        Object.entries(presetTimes).forEach(([label, val]) => {
          if (val === t) document.querySelector(`.manage-preset-btn[data-preset="${label}"]`)?.classList.add('active');
        });
      }
    });
  }
  renderTimeChips();
  overlay.style.display = 'flex';
  modal.hidden = false;
}

document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.manage-shell');
  const currentTab = shell?.dataset.activeTab || 'movies';

  // Tabs
  document.querySelectorAll('.manage-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const targetTab = tab.dataset.tab;
      document.querySelectorAll('.manage-tab').forEach(t => t.classList.remove('manage-tab--active'));
      tab.classList.add('manage-tab--active');
      document.querySelectorAll('.manage-tab-content').forEach(content => {
        if (content.dataset.content === targetTab) {
          content.hidden = false;
          content.classList.add('manage-tab-content--active');
        } else {
          content.hidden = true;
          content.classList.remove('manage-tab-content--active');
        }
      });
      const url = new URL(window.location.href);
      url.searchParams.set('tab', targetTab);
      window.history.replaceState({}, '', url);
    });
  });

  // Movie modal open/close
  const overlay = document.getElementById('movieModalOverlay');
  document.getElementById('addMovieBtn')?.addEventListener('click', () => {
    resetMovieForm();
    overlay.style.display = 'flex';
    document.getElementById('movieModal').hidden = false;
  });
  document.getElementById('closeMovieModal')?.addEventListener('click', resetMovieForm);
  document.getElementById('cancelMovieBtn')?.addEventListener('click', resetMovieForm);
  overlay?.addEventListener('click', (e) => {
    if (e.target === overlay) resetMovieForm();
  });

  // Price controls
  document.querySelector('[data-price-dec]')?.addEventListener('click', () => {
    const input = document.querySelector('.manage-price-input');
    const v = parseInt(input.value) || 0;
    input.value = Math.max(100, v - 50);
  });
  document.querySelector('[data-price-inc]')?.addEventListener('click', () => {
    const input = document.querySelector('.manage-price-input');
    const v = parseInt(input.value) || 0;
    input.value = v + 50;
  });

  // Preset showtimes
  document.querySelectorAll('.manage-preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const preset = btn.dataset.preset;
      const time = presetTimes[preset];
      if (selectedShowtimes.has(time)) {
        selectedShowtimes.delete(time);
        btn.classList.remove('active');
      } else {
        selectedShowtimes.add(time);
        btn.classList.add('active');
      }
      renderTimeChips();
    });
  });

  document.getElementById('addCustomTime')?.addEventListener('click', () => {
    const customTimeInput = document.getElementById('customTime');
    const timeValue = customTimeInput.value;
    if (timeValue && !selectedShowtimes.has(timeValue)) {
      selectedShowtimes.add(timeValue);
      renderTimeChips();
      customTimeInput.value = '';
    }
  });

  // Bind edit buttons
  document.querySelectorAll('.edit-movie-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      try {
        const payload = JSON.parse(btn.dataset.movie);
        editMovie(payload);
      } catch (e) {
        console.error('Unable to parse movie payload', e);
      }
    });
  });

  // Initialize chips and tab state
  renderTimeChips();
  // Ensure correct tab visible on load
  document.querySelectorAll('.manage-tab').forEach(tab => {
    if (tab.dataset.tab === currentTab) tab.click();
  });
});
</script>
@endpush

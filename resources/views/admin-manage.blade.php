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
.manage-modal {background: rgba(13,15,22,.92); border:1px solid rgba(124,92,255,.35); border-radius:18px; padding:1.5rem; box-shadow:0 24px 60px rgba(0,0,0,.55); max-width:700px; width:100%;}
.manage-modal-overlay {position:fixed; inset:0; background:rgba(0,0,0,.75); display:none; align-items:center; justify-content:center; padding:1rem; z-index:1200;}
.manage-modal__header {display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid rgba(255,255,255,.08);}
.manage-modal__title {margin:0; font-size:1.4rem; font-weight:700;}
.manage-modal__close {background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); color:rgba(231,237,243,.8); width:36px; height:36px; border-radius:8px; cursor:pointer; font-size:1.5rem; line-height:1; display:flex; align-items:center; justify-content:center; transition:all .2s;}
.manage-modal__close:hover {background:rgba(244,63,94,.15); border-color:rgba(244,63,94,.3); color:#fda4af;}
.manage-form__grid {display:grid; grid-template-columns: repeat(2, 1fr); gap:1rem;}
.manage-field {display:flex; flex-direction:column; gap:.4rem;}
.manage-field--wide {grid-column: 1 / -1;}
.manage-field--error .manage-input, .manage-field--error .manage-select, .manage-field--error .manage-price-control {border-color:rgba(244,63,94,.5); background:rgba(244,63,94,.08);}
/* .manage-field__label {font-size:.85rem; text-transform: uppercase; letter-spacing:.05em; color:rgba(231,237,243,.75); font-weight:700; text-align:left; line-height:1.3; margin-bottom:.15rem;} */
.manage-field__label {
  display: inline-flex ;
  flex-direction: row ;
  align-items: center;
  justify-content:left; 
  gap: 0.3rem;
  text-align: center;
}

.manage-field__label--required::after {
  content: "*";
  color: #ff4d4f;
  font-weight: 600;
  display: inline;  
  line-height: 1;
}
.manage-field--filled .manage-field__label--required::after {opacity:0;}
.manage-field__error {color:#fda4af; font-size:.8rem; margin-top:-.25rem; display:none;}
.manage-field--error .manage-field__error {display:block;}
.manage-input, .manage-select {width:100%; padding:.75rem .85rem; border-radius:10px; border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.04); color:#fff; font-size:.95rem; transition:all .2s; line-height:1.5;}
.manage-input:focus, .manage-select:focus {outline:none; border-color:rgba(124,92,255,.5); background:rgba(255,255,255,.06);}
.manage-price-control {display:flex; align-items:stretch; gap:.35rem; border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:.25rem; background: rgba(255,255,255,.04);}
.manage-price-input {flex:1; background:transparent; border:none; color:#fff; text-align:center; font-weight:700; font-size:.95rem; padding:.5rem;}
.manage-price-btn {min-width:40px; height:40px; border:none; border-radius:8px; background:rgba(255,255,255,.08); color:#fff; cursor:pointer; font-weight:700; transition:all .2s;}
.manage-price-btn:hover {background:rgba(124,92,255,.25); color:#e9d5ff;}
.manage-showtime-presets {display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;}
.manage-preset-btn {padding:.65rem 1rem; border-radius:9px; background: rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.12); color:rgba(231,237,243,.8); cursor:pointer; font-weight:700; transition:all .2s;}
.manage-preset-btn:hover {background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.18);}
.manage-preset-btn.active {background: linear-gradient(135deg,#7c3aed,#6d28d9); border-color: transparent; color:#fff; box-shadow:0 4px 12px rgba(124,92,255,.3);}
.manage-custom-time {display:flex; gap:.5rem; margin-bottom:.75rem;}
.manage-time-chips {display:flex; flex-wrap:wrap; gap:.5rem; min-height:3rem; padding:.75rem; background:rgba(255,255,255,.02); border:1px solid rgba(255,255,255,.06); border-radius:10px;}
.manage-time-chip {display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .8rem; border-radius:9px; background: rgba(124,92,255,.18); border:1px solid rgba(124,92,255,.35); color:#e9d5ff; font-weight:700; font-size:.9rem;}
.manage-time-chip button {border:none; background:rgba(0,0,0,.3); color:#fff; border-radius:50%; width:22px; height:22px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; font-size:1rem; line-height:1;}
.manage-time-chip button:hover {background:rgba(244,63,94,.5);}
.manage-form__actions {display:flex; justify-content:flex-end; gap:.75rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,.06);}
.manage-duration-hint {display:flex; align-items:center; gap:.5rem; color:rgba(231,237,243,.75); font-weight:700; line-height:1.3;}
.manage-duration-hint svg {width:18px; height:18px; flex-shrink:0;}
.manage-showtime-preview {display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.75rem; padding:.75rem; background:rgba(59,130,246,.05); border:1px solid rgba(59,130,246,.15); border-radius:10px; min-height:2.5rem; align-items:center;}
.manage-end-chip {padding:.4rem .75rem; border-radius:8px; background:rgba(59,130,246,.18); border:1px solid rgba(59,130,246,.35); color:#bfdbfe; font-weight:700; font-size:.85rem;}
@media (max-width: 720px){
  .manage-booking-card{grid-template-columns:1fr;}
  .manage-tabs{flex-wrap:wrap;}
  .manage-form__grid{grid-template-columns:1fr;}
  .manage-modal{padding:1.25rem; max-width:95vw;}
  .manage-modal__title{font-size:1.2rem;}
  .manage-showtime-presets{gap:.4rem;}
  .manage-preset-btn{padding:.55rem .8rem; font-size:.85rem;}
  .manage-custom-time{flex-direction:column;}
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
                    ₱{{ number_format($movie->price, 2) }} • {{ $movie->audience }} • {{ $movie->duration ?? 0 }} mins • {{ $movie->booking_items_count ?? 0 }} bookings
                  </p>
                </div>
                <span class="manage-status manage-status--active">Active</span>
              </div>
              <div class="manage-movie-showtimes">
                @foreach($movie->showtimes as $st)
                  @php
                    $time = $st->showtime;
                    $end = null;
                    try {
                      $startObj = \Carbon\Carbon::createFromFormat('H:i', $st->showtime);
                      $time = $startObj->format('g:i A');
                      $end = (clone $startObj)->addMinutes(($movie->duration ?? 90) + 15)->format('g:i A');
                    } catch (\Exception $e) {}
                  @endphp
                  <span class="manage-time-chip-display">{{ $time }}</span>
                @endforeach
              </div>
              @if($movie->showtimes->count())
                <div class="manage-showtime-preview" aria-label="End times">
                  @foreach($movie->showtimes as $st)
                    @php
                      $end = null;
                      try {
                        $end = \Carbon\Carbon::createFromFormat('H:i', $st->showtime)->addMinutes(($movie->duration ?? 90) + 15)->format('g:i A');
                      } catch (\Exception $e) {}
                    @endphp
                    @if($end)
                      <span class="manage-end-chip">ends {{ $end }}</span>
                    @endif
                  @endforeach
                </div>
              @endif
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
                  <p class="manage-movie-meta">₱{{ number_format($movie->price,2) }} • {{ $movie->audience }} • {{ $movie->duration ?? 0 }} mins • {{ $movie->booking_items_count ?? 0 }} bookings</p>
                </div>
                <span class="manage-status manage-status--inactive">Inactive</span>
              </div>
              @if($movie->showtimes->count())
                <div class="manage-movie-showtimes">
                  @foreach($movie->showtimes as $st)
                    @php
                      $time = $st->showtime;
                      try { $time = \Carbon\Carbon::createFromFormat('H:i', $st->showtime)->format('g:i A'); } catch (\Exception $e) {}
                    @endphp
                    <span class="manage-time-chip-display">{{ $time }}</span>
                  @endforeach
                </div>
                <div class="manage-showtime-preview" aria-label="End times">
                  @foreach($movie->showtimes as $st)
                    @php
                      $end = null;
                      try {
                        $end = \Carbon\Carbon::createFromFormat('H:i', $st->showtime)->addMinutes(($movie->duration ?? 90) + 15)->format('g:i A');
                      } catch (\Exception $e) {}
                    @endphp
                    @if($end)
                      <span class="manage-end-chip">ends {{ $end }}</span>
                    @endif
                  @endforeach
                </div>
              @endif
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
    <div class="manage-modal__header">
      <h3 class="manage-modal__title" id="movieModalTitle">Add New Movie</h3>
      <button class="manage-modal__close" id="closeMovieModal" aria-label="Close">×</button>
    </div>
    <form id="movieForm" action="{{ url('/admin/movies') }}" method="POST" class="manage-form">
      @csrf
      <div class="manage-form__grid">
        <div class="manage-field" data-field="title">
          <label class="manage-field__label manage-field__label--required">
  Title
</label>
          <input name="title" type="text" placeholder="e.g., Hidden Tide" required class="manage-input" />
          <span class="manage-field__error">Title is required</span>
        </div>
        <div class="manage-field" data-field="price">
          <label class="manage-field__label manage-field__label--required">Price (₱)</label>
          <div class="manage-price-control">
            <button type="button" class="manage-price-btn" data-price-dec>-</button>
            <input name="price" type="number" min="100" step="50" value="350" required class="manage-price-input" />
            <button type="button" class="manage-price-btn" data-price-inc>+</button>
          </div>
          <span class="manage-field__error">Price must be at least ₱100</span>
        </div>
        <div class="manage-field" data-field="duration">
          <label class="manage-field__label manage-duration-hint manage-field__label--required">
            <!-- <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <path d="M12 7v5l3 2"/>
            </svg> -->
            Duration (minutes)
          </label>
          <input name="duration" type="number" min="30" max="300" step="5" value="90" required class="manage-input" />
          <span class="manage-field__error">Duration must be between 30-300 minutes</span>
        </div>
        <div class="manage-field" data-field="audience">
          <label class="manage-field__label manage-field__label--required">Audience Rating</label>
          <select name="audience" required class="manage-select">
            <option value="G">G</option>
            <option value="PG" selected>PG</option>
            <option value="R-13">R-13</option>
            <option value="R-16">R-16</option>
            <option value="R-18">R-18</option>
          </select>
          <span class="manage-field__error">Audience rating is required</span>
        </div>
        <div class="manage-field" data-field="poster">
          <label class="manage-field__label">Poster URL</label>
          <input name="poster" type="text" placeholder="/images/movie.jpg or full URL" class="manage-input" />
          <span class="manage-field__error">Invalid poster URL</span>
        </div>
        <div class="manage-field" data-field="listing_start">
          <label class="manage-field__label manage-field__label--required">Listing Start</label>
          <input name="listing_start" type="date" value="{{ now()->toDateString() }}" required class="manage-input" />
          <span class="manage-field__error">Listing start date is required</span>
        </div>
        <div class="manage-field" data-field="listing_end">
          <label class="manage-field__label manage-field__label--required">Listing End</label>
          <input name="listing_end" type="date" value="{{ now()->toDateString() }}" required class="manage-input" />
          <span class="manage-field__error">Listing end date is required</span>
        </div>
        <div class="manage-field manage-field--wide" data-field="times">
          <label class="manage-field__label manage-field__label--required">Showtimes</label>
          <p class="manage-section-subtitle" style="margin:0 0 .75rem 0; color:rgba(231,237,243,.6); font-size:.9rem;">Movie runs <span id="durationHintVal">90</span> mins + 15 min buffer</p>
          <div class="manage-showtimes">
            <div class="manage-showtime-presets">
              <button type="button" class="manage-preset-btn" data-preset="Morning">Morning</button>
              <button type="button" class="manage-preset-btn" data-preset="Matinee">Matinee</button>
              <button type="button" class="manage-preset-btn" data-preset="Evening">Evening</button>
              <button type="button" class="manage-preset-btn" data-preset="Late Night">Late Night</button>
            </div>
            <div class="manage-custom-time">
              <input type="time" id="customTime" class="manage-input" style="flex:1;" />
              <button type="button" class="btn btn--ghost btn--sm" id="addCustomTime">+ Add Custom</button>
            </div>
            <div class="manage-time-chips" id="timeChips"></div>
            <div class="manage-showtime-preview" id="showtimePreview"></div>
            <input type="hidden" name="times" id="showtimesInput" required />
          </div>
          <span class="manage-field__error">At least one showtime is required</span>
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

// Update field filled state
function updateFieldFilledState(fieldWrapper) {
  if (!fieldWrapper) return;

  const input = fieldWrapper.querySelector('input:not([type="hidden"]), select');
  if (!input) return;

  const isFilled = input.value && input.value.trim().length > 0;
  if (isFilled) {
    fieldWrapper.classList.add('manage-field--filled');
  } else {
    fieldWrapper.classList.remove('manage-field--filled');
  }
}

// Check all fields
function updateAllFieldStates() {
  document.querySelectorAll('.manage-field[data-field]').forEach(field => {
    // Special handling for showtimes field
    if (field.dataset.field === 'times') {
      if (selectedShowtimes.size > 0) {
        field.classList.add('manage-field--filled');
      } else {
        field.classList.remove('manage-field--filled');
      }
    } else {
      updateFieldFilledState(field);
    }
  });
}

function formatTime12(timeStr) {
  const [h,m] = (timeStr || '').split(':').map(v => parseInt(v,10));
  if (isNaN(h) || isNaN(m)) return timeStr;
  const d = new Date(1970,0,1,h,m);
  return d.toLocaleTimeString('en-US',{hour:'numeric', minute:'2-digit', hour12:true});
}

function computeEndTime(start, minutesToAdd) {
  const [h,m] = (start || '').split(':').map(v => parseInt(v,10));
  if (isNaN(h) || isNaN(m)) return null;
  const d = new Date(1970,0,1,h,m);
  d.setMinutes(d.getMinutes() + minutesToAdd);
  return d.toLocaleTimeString('en-US',{hour:'numeric', minute:'2-digit', hour12:true});
}

function renderTimeChips() {
  const timeChips = document.getElementById('timeChips');
  const showtimesInput = document.getElementById('showtimesInput');
  const preview = document.getElementById('showtimePreview');
  const durationField = document.querySelector('[name="duration"]');
  const durationHintVal = document.getElementById('durationHintVal');
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

  // Validate showtimes field and update filled state
  const timesField = document.querySelector('.manage-field[data-field="times"]');
  if (timesField) {
    if (selectedShowtimes.size > 0) {
      timesField.classList.remove('manage-field--error');
      timesField.classList.add('manage-field--filled');
    } else {
      timesField.classList.remove('manage-field--filled');
      // Only show error if user has interacted with the form
      if (document.getElementById('movieForm')?.classList.contains('was-validated')) {
        timesField.classList.add('manage-field--error');
      }
    }
  }

  if (preview) {
    preview.innerHTML = '';
    const runtimeBase = parseInt(durationField?.value, 10) || 90;
    const runtime = runtimeBase + 15; // add 15 min buffer
    if (durationHintVal) durationHintVal.textContent = runtimeBase;
    const times = Array.from(selectedShowtimes).sort();
    if (!times.length) {
      preview.innerHTML = `<span class="muted" style="color:rgba(231,237,243,.55);font-size:.9rem;">Add showtimes to see end-time preview</span>`;
    } else {
      times.forEach(t => {
        const end = computeEndTime(t, runtime);
        const label = end ? `${formatTime12(t)} → ${end}` : `${t}`;
        const chip = document.createElement('span');
        chip.className = 'manage-end-chip';
        chip.textContent = label;
        preview.appendChild(chip);
      });
    }
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
  if (form.querySelector('[name="duration"]')) {
    form.querySelector('[name="duration"]').value = 90;
  }
  selectedShowtimes.clear();
  renderTimeChips();
  document.querySelectorAll('.manage-preset-btn').forEach(btn => btn.classList.remove('active'));

  // Clear validation errors and filled states
  form.classList.remove('was-validated');
  document.querySelectorAll('.manage-field--error').forEach(field => {
    field.classList.remove('manage-field--error');
  });
  document.querySelectorAll('.manage-field--filled').forEach(field => {
    field.classList.remove('manage-field--filled');
  });

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

  // Clear validation errors
  form.classList.remove('was-validated');
  document.querySelectorAll('.manage-field--error').forEach(field => {
    field.classList.remove('manage-field--error');
  });

  form.querySelector('[name="title"]').value = movieData.title || '';
  form.querySelector('[name="price"]').value = movieData.price || 350;
  form.querySelector('[name="audience"]').value = movieData.audience || 'PG';
  form.querySelector('[name="duration"]').value = movieData.duration || 90;
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

  // Update all field filled states
  setTimeout(updateAllFieldStates, 0);

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

  document.querySelector('[name="duration"]')?.addEventListener('input', () => {
    renderTimeChips();
  });

  // Form validation
  function validateField(fieldName) {
    const fieldWrapper = document.querySelector(`.manage-field[data-field="${fieldName}"]`);
    if (!fieldWrapper) return true;

    let isValid = true;
    fieldWrapper.classList.remove('manage-field--error');

    switch(fieldName) {
      case 'title':
        const titleInput = fieldWrapper.querySelector('[name="title"]');
        isValid = titleInput.value.trim().length > 0;
        break;
      case 'price':
        const priceInput = fieldWrapper.querySelector('[name="price"]');
        isValid = priceInput.value >= 100;
        break;
      case 'duration':
        const durationInput = fieldWrapper.querySelector('[name="duration"]');
        isValid = durationInput.value >= 30 && durationInput.value <= 300;
        break;
      case 'audience':
        const audienceSelect = fieldWrapper.querySelector('[name="audience"]');
        isValid = audienceSelect.value.length > 0;
        break;
      case 'listing_start':
        const startInput = fieldWrapper.querySelector('[name="listing_start"]');
        isValid = startInput.value.length > 0;
        break;
      case 'listing_end':
        const endInput = fieldWrapper.querySelector('[name="listing_end"]');
        isValid = endInput.value.length > 0;
        break;
      case 'times':
        isValid = selectedShowtimes.size > 0;
        break;
    }

    if (!isValid) {
      fieldWrapper.classList.add('manage-field--error');
    }

    return isValid;
  }

  function validateForm() {
    const fields = ['title', 'price', 'duration', 'audience', 'listing_start', 'listing_end', 'times'];
    let allValid = true;

    fields.forEach(field => {
      if (!validateField(field)) {
        allValid = false;
      }
    });

    return allValid;
  }

  // Remove error state on input and update filled state
  document.querySelectorAll('.manage-field[data-field] input, .manage-field[data-field] select').forEach(input => {
    input.addEventListener('input', (e) => {
      const fieldWrapper = e.target.closest('.manage-field[data-field]');
      if (fieldWrapper) {
        // Remove error state
        if (fieldWrapper.classList.contains('manage-field--error')) {
          fieldWrapper.classList.remove('manage-field--error');
        }
        // Update filled state
        updateFieldFilledState(fieldWrapper);
      }
    });

    // Check initial state
    const fieldWrapper = input.closest('.manage-field[data-field]');
    if (fieldWrapper) {
      updateFieldFilledState(fieldWrapper);
    }
  });

  // Form submit validation
  document.getElementById('movieForm')?.addEventListener('submit', (e) => {
    const form = e.target;
    form.classList.add('was-validated');

    if (!validateForm()) {
      e.preventDefault();
      e.stopPropagation();

      // Scroll to first error
      const firstError = document.querySelector('.manage-field--error');
      if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      return false;
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

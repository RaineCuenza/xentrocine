@extends('layouts.app')

@section('title', 'Admin — XentroCine')
@section('page', 'admin')

@section('content')
<div class="admin-container">
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

<div class="admin-wrap">
  <!-- First Row: Manage Movies + All Bookings -->
  <div class="admin-row admin-row--two-cols">
    <!-- Left: Manage Movies -->
    <section class="admin-panel">
        <header class="admin-panel__header">
          <div>
            <h2>Manage Movies</h2>
            <p class="admin-panel__hint">Create, update, or retire titles across your catalogue.</p>
          </div>
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
            <div class="admin-price-control" data-price-control role="group" aria-label="Adjust ticket price">
              <button type="button" class="admin-price-btn" data-price-dir="down" aria-label="Decrease price"><span aria-hidden="true">−</span></button>
              <input name="price" type="number" min="100" step="10" value="{{ old('price', 350) }}" placeholder="350" required data-price-input/>
              <button type="button" class="admin-price-btn" data-price-dir="up" aria-label="Increase price"><span aria-hidden="true">+</span></button>
            </div>
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
          <label class="admin-field">
            <span class="admin-field__label">Listing start</span>
            <input name="listing_start" id="listing_start" type="date" value="{{ old('listing_start', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required />
          </label>
          <div class="admin-field">
            <div style="display:flex;align-items:flex-end;gap:.5rem;">
              <label style="flex:1">
                <span class="admin-field__label">Listing end</span>
                <input name="listing_end" id="listing_end" type="date" value="{{ old('listing_end', date('Y-m-d')) }}" required />
              </label>
              <div class="showtime-presets" style="margin-bottom:2px">
                <button type="button" class="preset-btn" data-duration="14">2 weeks</button>
                <button type="button" class="preset-btn" data-duration="21">3 weeks</button>
                <button type="button" class="preset-btn" data-duration="30">1 month</button>
              </div>
            </div>
            <small id="listing_error" class="muted" style="color:#fda4af;display:none">A movie can only run for a maximum of 1 month from its start date.</small>
          </div>
          <div class="admin-field admin-field--wide">
            <span class="admin-field__label">Showtimes</span>
            <div class="showtime-builder" data-showtime-builder data-builder-context="create">
              <div class="showtime-presets">
                <button type="button" class="preset-btn" data-times="09:00,12:00">Morning</button>
                <button type="button" class="preset-btn" data-times="13:00,15:30">Matinee</button>
                <button type="button" class="preset-btn" data-times="18:00,20:30">Evening</button>
                <button type="button" class="preset-btn" data-times="22:00,23:30">Late Night</button>
              </div>
              <div class="showtime-input-group">
                <input type="text" placeholder="Add custom (e.g., 3:00 PM or 15:00)" data-builder-input />
                <button type="button" class="btn btn--sm btn--ghost" data-builder-add>+ Add</button>
              </div>
              <div class="showtime-chips" data-builder-chips></div>
              <input name="times" type="hidden" data-builder-hidden required/>
              <p class="showtime-hint">Click presets or add custom times. Times will auto-sort.</p>
            </div>
          </div>
        </div>
          <div class="admin-form__actions">
            <button class="btn btn--primary" type="submit">Add Movie</button>
          </div>
        </form>
        </div>
      </section>

    <section class="admin-panel" style="min-height: 100%;">
    <header class="admin-panel__header">
      <div>
        <h2>All Bookingss</h2>
        <p class="admin-panel__hint">Recent reservations with current payment status.</p>
      </div>
      <span class="admin-panel__tag">{{ method_exists($bookings,'total') ? $bookings->total() : $bookings->count() }} on record</span>
    </header>
        <div class="admin-panel__body">
      <!-- Bookings Filters -->
      <form method="GET" action="{{ route('admin') }}" class="filters" style="margin-bottom:1rem">
        <input type="text" name="b_q" value="{{ $filters['b_q'] ?? '' }}" placeholder="Search movie title" />
        @php $bs = $filters['b_status'] ?? 'all'; @endphp
        <select name="b_status">
          <option value="all" {{ ($bs==='all')?'selected':'' }}>All</option>
          <option value="Paid" {{ ($bs==='Paid')?'selected':'' }}>Paid</option>
          <option value="Pending" {{ ($bs==='Pending')?'selected':'' }}>Pending</option>
          <option value="Cancelled" {{ ($bs==='Cancelled')?'selected':'' }}>Cancelled</option>
        </select>
        <input type="date" name="b_from" value="{{ $filters['b_from'] ?? '' }}" />
        <input type="date" name="b_to" value="{{ $filters['b_to'] ?? '' }}" />
        <div class="row" style="grid-template-columns:auto auto auto;gap:.5rem">
          <button type="submit" class="btn btn--ghost">Apply</button>
          <a href="{{ route('admin') }}" class="btn btn--ghost">Clear filters</a>
          @if(($filters['b_q']??'')!=='' || ($filters['b_status']??'all')!=='all' || ($filters['b_from']??null) || ($filters['b_to']??null))
            <span class="admin-panel__tag">Filters applied</span>
          @endif
        </div>
      </form>

          <ul id="adminBookingList" class="booking-list booking-list--dense panel-scroll">
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
                <span>
                  @php
                    $start = date('H:i', strtotime($item->showtime));
                    $duration = optional($item->movie)->duration ?? 90;
                    $end = \Carbon\Carbon::createFromFormat('H:i', $start)->addMinutes($duration)->format('H:i');
                  @endphp
                  {{ $start }}–{{ $end }}
                </span>
                <span>{{ $item->cinema }}</span>
                <span>{{ $item->quantity }} pax</span>
              </div>
            @endforeach
            <div class="booking-item__footer">
              <span class="booking-item__total">Total: ₱{{ number_format($booking->total, 2) }}</span>
              <span class="booking-item__timestamp">{{ $booking->created_at->format('M d, Y g:i A') }}</span>
                  @if($booking->payment_method === 'digital' && $booking->gcash_receipt_path)
                    <span><a href="{{ asset('storage/' . $booking->gcash_receipt_path) }}" target="_blank">View GCash receipt</a></span>
                  @endif
            </div>
          </li>
        @empty
          <li class="booking-item booking-item--empty">No bookings yet</li>
        @endforelse
          </ul>
          @if(method_exists($bookings,'links'))
            <div class="pager mt-4">
              @php
                $bStart = max(1, $bookings->currentPage() - 2);
                $bEnd = min($bookings->lastPage(), $bookings->currentPage() + 2);
              @endphp
              <div class="pager__inner">
                @if($bookings->onFirstPage())
                  <span class="pager__link is-disabled">« Previous</span>
                @else
                  <a class="pager__link" href="{{ $bookings->previousPageUrl() }}">« Previous</a>
                @endif

                @for($i = $bStart; $i <= $bEnd; $i++)
                  @if($i === $bookings->currentPage())
                    <span class="pager__page is-active">{{ $i }}</span>
                  @else
                    <a class="pager__page" href="{{ $bookings->url($i) }}">{{ $i }}</a>
                  @endif
                @endfor

                @if($bookings->hasMorePages())
                  <a class="pager__link" href="{{ $bookings->nextPageUrl() }}">Next »</a>
                @else
                  <span class="pager__link is-disabled">Next »</span>
                @endif
              </div>
            </div>
          @endif
        </div>
      </section>
  </div>

  <!-- Second Row: Active Movies (full width) -->
  <div class="admin-row admin-row--full">
      <section class="admin-panel">
        <header class="admin-panel__header">
          <div>
            <h2>Active Movies</h2>
            <p class="admin-panel__hint">Browse and manage current listings.</p>
          </div>
          <div class="admin-panel__tags">
            <span class="admin-panel__tag"><span id="adminActiveCount">{{ $kpis['movies'] }}</span> Active</span>
          </div>
        </header>
        <div class="admin-panel__body">
          <!-- Movies Filters -->
          <form method="GET" action="{{ route('admin') }}" class="filters" style="margin-top:0.25rem">
            <input type="text" name="m_q_a" value="{{ $filters['m_q_a'] ?? '' }}" placeholder="Search title" />
            <select name="m_audience_a">
              @php $aud = $filters['m_audience_a'] ?? 'all'; @endphp
              <option value="all" {{ ($aud==='all')?'selected':'' }}>All audiences</option>
              <option value="G" {{ ($aud==='G')?'selected':'' }}>G</option>
              <option value="PG" {{ ($aud==='PG')?'selected':'' }}>PG</option>
              <option value="R-13" {{ ($aud==='R-13')?'selected':'' }}>R-13</option>
              <option value="R-16" {{ ($aud==='R-16')?'selected':'' }}>R-16</option>
              <option value="R-18" {{ ($aud==='R-18')?'selected':'' }}>R-18</option>
            </select>
            <div class="row" style="grid-template-columns:auto auto auto;gap:.5rem">
              <button type="submit" class="btn btn--ghost">Apply</button>
              <a href="{{ route('admin') }}" class="btn btn--ghost">Clear filters</a>
              @if(($filters['m_q_a']??'')!=='' || ($filters['m_audience_a']??'all')!=='all')
                <span class="admin-panel__tag">Filters applied</span>
              @endif
            </div>
          </form>

          <ul id="adminMovieList" class="admin-collection panel-scroll">
        @forelse($moviesActive as $movie)
          @php
            $rawTimes = $movie->showtimes->pluck('showtime')
              ->map(fn($time) => \Carbon\Carbon::parse($time)->format('H:i'))
              ->unique()
              ->sort()
              ->values();
            $showtimeValues = $rawTimes->toArray();
            $showtimeDisplay = $movie->showtimes->map(function($st){ return $st->formatted_range; })->toArray();
            $ls = $movie->listing_start ? \Carbon\Carbon::parse($movie->listing_start) : null;
            $le = $movie->listing_end ? \Carbon\Carbon::parse($movie->listing_end) : null;
            $rangeText = '';
            if ($ls && $le) {
              $days = $ls->diffInDays($le);
              $label = $days === 30 ? '1 month' : ($days % 7 === 0 ? ($days/7).' week'.(($days/7)>1?'s':'') : $days.' days');
              $rangeText = 'Showing: '.$ls->format('M d, Y').' – '.$le->format('M d, Y').' ('.$label.')';
            }
          @endphp
          <li class="admin-collection__item {{ $movie->is_active ? '' : 'admin-collection__item--inactive' }}"
              data-movie-id="{{ $movie->id }}"
              data-title="{{ $movie->title }}"
              data-price="{{ $movie->price }}"
              data-audience="{{ $movie->audience }}"
              data-poster="{{ $movie->poster }}"
              data-bookings="{{ $movie->booking_items_count ?? 0 }}"
              data-is-active="{{ $movie->is_active ? '1' : '0' }}"
              data-showtimes='@json($showtimeValues)'>
            <div class="admin-movie__header">
              <div>
                <h3 class="admin-collection__title">{{ $movie->title }}</h3>
                <p class="admin-collection__subtitle">
                  ₱<span class="admin-movie__price">{{ number_format($movie->price, 2) }}</span> • 
                  <span class="admin-movie__audience">{{ $movie->audience }}</span> • 
                  <span class="admin-movie__bookings">{{ $movie->booking_items_count ?? 0 }}</span> bookings
                  @if($rangeText)
                    <br/><span class="muted">{{ $rangeText }}</span>
                  @endif
                </p>
              </div>
              <span class="badge {{ $movie->is_active ? 'badge--active' : 'badge--inactive' }} admin-movie__status">
                {{ $movie->is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="admin-movie__showtimes">
              @if(count($showtimeDisplay))
                @foreach($showtimeDisplay as $displayTime)
                  <span class="showtime-chip showtime-chip--static">{{ $displayTime }}</span>
                @endforeach
              @else
                <span class="admin-movie__empty">No showtimes scheduled</span>
              @endif
            </div>
            <div class="admin-collection__actions" role="group" aria-label="Manage movie actions">
              <button type="button" class="btn btn--ghost btn--sm admin-movie__edit">Edit</button>
              <button type="button" class="btn btn--ghost btn--sm admin-movie__toggle">
                {{ $movie->is_active ? 'Set Inactive' : 'Set Active' }}
              </button>
              @if($movie->is_active)
                <form action="{{ url('/admin/movies/'.$movie->id.'/status') }}" method="POST" style="display:inline">
                  @csrf
                  <input type="hidden" name="status" value="inactive">
                  <button type="submit" class="btn btn--ghost btn--sm admin-movie__archive">Archive</button>
                </form>
              @else
                <form action="{{ url('/admin/movies/'.$movie->id.'/status') }}" method="POST" style="display:inline">
                  @csrf
                  <input type="hidden" name="status" value="active">
                  <button type="submit" class="btn btn--ghost btn--sm admin-movie__unarchive">Unarchive</button>
                </form>
              @endif
            </div>
            <div class="admin-movie__editor" hidden>
              <form class="admin-edit-form" data-movie-id="{{ $movie->id }}">
                <div class="admin-edit-grid">
                  <label class="admin-field admin-field--compact">
                    <span class="admin-field__label">Ticket Price (₱)</span>
                    <div class="admin-price-control" data-price-control>
                      <button type="button" class="admin-price-btn" data-price-dir="down" aria-label="Decrease price"><span aria-hidden="true">−</span></button>
                      <input type="number" min="100" step="10" value="{{ number_format($movie->price, 2, '.', '') }}" data-price-input required>
                      <button type="button" class="admin-price-btn" data-price-dir="up" aria-label="Increase price"><span aria-hidden="true">+</span></button>
                    </div>
                  </label>
                  <label class="admin-field">
                    <span class="admin-field__label">Audience Rating</span>
                    <select data-edit-audience required>
                      <option value="G" @selected($movie->audience === 'G')>G</option>
                      <option value="PG" @selected($movie->audience === 'PG')>PG</option>
                      <option value="R-13" @selected($movie->audience === 'R-13')>R-13</option>
                      <option value="R-16" @selected($movie->audience === 'R-16')>R-16</option>
                      <option value="R-18" @selected($movie->audience === 'R-18')>R-18</option>
                    </select>
                  </label>
                  <label class="admin-field admin-field--wide">
                    <span class="admin-field__label">Poster URL</span>
                    <input type="text" value="{{ $movie->poster }}" placeholder="/images/movie.jpg or full URL" data-edit-poster>
                  </label>
                  <div class="admin-field admin-field--wide">
                    <span class="admin-field__label">Showtimes</span>
                    <div class="showtime-builder" data-showtime-builder data-initial-times='@json($showtimeValues)'>
                      <div class="showtime-presets">
                        <button type="button" class="preset-btn" data-times="09:00,12:00">Morning</button>
                        <button type="button" class="preset-btn" data-times="13:00,15:30">Matinee</button>
                        <button type="button" class="preset-btn" data-times="18:00,20:30">Evening</button>
                        <button type="button" class="preset-btn" data-times="22:00,23:30">Late Night</button>
                      </div>
                      <div class="showtime-input-group">
                        <input type="text" placeholder="Add custom (e.g., 3:00 PM or 15:00)" data-builder-input />
                        <button type="button" class="btn btn--sm btn--ghost" data-builder-add>+ Add</button>
                      </div>
                      <div class="showtime-chips" data-builder-chips></div>
                      <input type="hidden" data-builder-hidden>
                      <p class="showtime-hint">Click presets or add custom times. Times will auto-sort.</p>
                    </div>
                  </div>
                </div>
                <div class="admin-edit-actions">
                  <button type="button" class="btn btn--ghost btn--sm admin-edit-cancel">Cancel</button>
                  <button type="submit" class="btn btn--primary btn--sm admin-edit-save">Save Changes</button>
                </div>
              </form>
            </div>
          </li>
        @empty
          <li class="admin-collection__empty">No active movies</li>
        @endforelse
          </ul>

          @if(method_exists($moviesActive,'links'))
            <div class="pager mt-4">
              @php
                $mStart = max(1, $moviesActive->currentPage() - 2);
                $mEnd = min($moviesActive->lastPage(), $moviesActive->currentPage() + 2);
              @endphp
              <div class="pager__inner">
                @if($moviesActive->onFirstPage())
                  <span class="pager__link is-disabled">« Previous</span>
                @else
                  <a class="pager__link" href="{{ $moviesActive->previousPageUrl() }}">« Previous</a>
                @endif

                @for($i = $mStart; $i <= $mEnd; $i++)
                  @if($i === $moviesActive->currentPage())
                    <span class="pager__page is-active">{{ $i }}</span>
                  @else
                    <a class="pager__page" href="{{ $moviesActive->url($i) }}">{{ $i }}</a>
                  @endif
                @endfor

                @if($moviesActive->hasMorePages())
                  <a class="pager__link" href="{{ $moviesActive->nextPageUrl() }}">Next »</a>
                @else
                  <span class="pager__link is-disabled">Next »</span>
                @endif
              </div>
            </div>
          @endif
        </div>
      </section>

      <!-- Archived Movies Card -->
      <section class="admin-panel">
        <header class="admin-panel__header">
          <div>
            <h2>Archived Movies</h2>
            <p class="admin-panel__hint">Previously listed titles. Unarchive to make active.</p>
          </div>
          <div class="admin-panel__tags">
            <span class="admin-panel__tag"><span id="adminArchivedCount">{{ $kpis['movies_archived'] }}</span> Archived</span>
          </div>
        </header>
        <div class="admin-panel__body">
          <!-- Archived Movies Filters -->
          <form method="GET" action="{{ route('admin') }}" class="filters" style="margin-top:0.25rem">
            <input type="text" name="m_q_r" value="{{ $filters['m_q_r'] ?? '' }}" placeholder="Search title" />
            <select name="m_audience_r">
              @php $audR = $filters['m_audience_r'] ?? 'all'; @endphp
              <option value="all" {{ ($audR==='all')?'selected':'' }}>All audiences</option>
              <option value="G" {{ ($audR==='G')?'selected':'' }}>G</option>
              <option value="PG" {{ ($audR==='PG')?'selected':'' }}>PG</option>
              <option value="R-13" {{ ($audR==='R-13')?'selected':'' }}>R-13</option>
              <option value="R-16" {{ ($audR==='R-16')?'selected':'' }}>R-16</option>
              <option value="R-18" {{ ($audR==='R-18')?'selected':'' }}>R-18</option>
            </select>
            <div class="row" style="grid-template-columns:auto auto auto;gap:.5rem">
              <button type="submit" class="btn btn--ghost">Apply</button>
              <a href="{{ route('admin') }}" class="btn btn--ghost">Clear filters</a>
              @if(($filters['m_q_r']??'')!=='' || ($filters['m_audience_r']??'all')!=='all')
                <span class="admin-panel__tag">Filters applied</span>
              @endif
            </div>
          </form>

          <ul class="admin-collection panel-scroll">
            @forelse($moviesArchived as $movie)
              @php
                $rawTimes = $movie->showtimes->pluck('showtime')
                  ->map(fn($time) => \Carbon\Carbon::parse($time)->format('H:i'))
                  ->unique()
                  ->sort()
                  ->values();
                $showtimeValues = $rawTimes->toArray();
                $showtimeDisplay = $movie->showtimes->map(function($st){ return $st->formatted_range; })->toArray();
                $ls = $movie->listing_start ? \Carbon\Carbon::parse($movie->listing_start) : null;
                $le = $movie->listing_end ? \Carbon\Carbon::parse($movie->listing_end) : null;
                $rangeText = '';
                if ($ls && $le) {
                  $days = $ls->diffInDays($le);
                  $label = $days === 30 ? '1 month' : ($days % 7 === 0 ? ($days/7).' week'.(($days/7)>1?'s':'') : $days.' days');
                  $rangeText = 'Showing: '.$ls->format('M d, Y').' – '.$le->format('M d, Y').' ('.$label.')';
                }
              @endphp
              <li class="admin-collection__item admin-collection__item--inactive"
                  data-movie-id="{{ $movie->id }}"
                  data-title="{{ $movie->title }}"
                  data-price="{{ $movie->price }}"
                  data-audience="{{ $movie->audience }}"
                  data-poster="{{ $movie->poster }}"
                  data-bookings="{{ $movie->booking_items_count ?? 0 }}"
                  data-is-active="0"
                  data-showtimes='@json($showtimeValues)'>
                <div class="admin-movie__header">
                  <div>
                    <h3 class="admin-collection__title">{{ $movie->title }}</h3>
                    <p class="admin-collection__subtitle">
                      ₱<span class="admin-movie__price">{{ number_format($movie->price, 2) }}</span> • 
                      <span class="admin-movie__audience">{{ $movie->audience }}</span> • 
                      <span class="admin-movie__bookings">{{ $movie->booking_items_count ?? 0 }}</span> bookings
                      @if($rangeText)
                        <br/><span class="muted">{{ $rangeText }}</span>
                      @endif
                    </p>
                  </div>
                  <span class="badge badge--inactive admin-movie__status">Inactive</span>
                </div>
                <div class="admin-movie__showtimes">
                  @if(count($showtimeDisplay))
                    @foreach($showtimeDisplay as $displayTime)
                      <span class="showtime-chip showtime-chip--static">{{ $displayTime }}</span>
                    @endforeach
                  @else
                    <span class="admin-movie__empty">No showtimes scheduled</span>
                  @endif
                </div>
                <div class="admin-collection__actions" role="group" aria-label="Manage movie actions">
                  <form action="{{ url('/admin/movies/'.$movie->id.'/status') }}" method="POST" style="display:inline">
                    @csrf
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn btn--primary btn--sm">Unarchive</button>
                  </form>
                </div>
              </li>
            @empty
              <li class="admin-collection__empty">No archived movies</li>
            @endforelse
          </ul>

          @if(method_exists($moviesArchived,'links'))
            <div class="pager mt-4">
              @php
                $rStart = max(1, $moviesArchived->currentPage() - 2);
                $rEnd = min($moviesArchived->lastPage(), $moviesArchived->currentPage() + 2);
              @endphp
              <div class="pager__inner">
                @if($moviesArchived->onFirstPage())
                  <span class="pager__link is-disabled">« Previous</span>
                @else
                  <a class="pager__link" href="{{ $moviesArchived->previousPageUrl() }}">« Previous</a>
                @endif

                @for($i = $rStart; $i <= $rEnd; $i++)
                  @if($i === $moviesArchived->currentPage())
                    <span class="pager__page is-active">{{ $i }}</span>
                  @else
                    <a class="pager__page" href="{{ $moviesArchived->url($i) }}">{{ $i }}</a>
                  @endif
                @endfor

                @if($moviesArchived->hasMorePages())
                  <a class="pager__link" href="{{ $moviesArchived->nextPageUrl() }}">Next »</a>
                @else
                  <span class="pager__link is-disabled">Next »</span>
                @endif
              </div>
            </div>
          @endif
        </div>
      </section>
  </div>

  <!-- Third Row: Archived Movies (full width) -->
  <div class="admin-row admin-row--full">
      <section class="admin-panel">
        <header class="admin-panel__header">
          <div>
            <h2>Archived Movies</h2>
            <p class="admin-panel__hint">Previously listed titles. Unarchive to make active.</p>
          </div>
          <div class="admin-panel__tags">
            <span class="admin-panel__tag"><span id="adminArchivedCount">{{ $kpis['movies_archived'] }}</span> Archived</span>
          </div>
        </header>
        <div class="admin-panel__body">
          <!-- Archived Movies Filters -->
          <form method="GET" action="{{ route('admin') }}" class="filters" style="margin-top:0.25rem">
            <input type="text" name="m_q_r" value="{{ $filters['m_q_r'] ?? '' }}" placeholder="Search title" />
            <select name="m_audience_r">
              @php $audR = $filters['m_audience_r'] ?? 'all'; @endphp
              <option value="all" {{ ($audR==='all')?'selected':'' }}>All audiences</option>
              <option value="G" {{ ($audR==='G')?'selected':'' }}>G</option>
              <option value="PG" {{ ($audR==='PG')?'selected':'' }}>PG</option>
              <option value="R-13" {{ ($audR==='R-13')?'selected':'' }}>R-13</option>
              <option value="R-16" {{ ($audR==='R-16')?'selected':'' }}>R-16</option>
              <option value="R-18" {{ ($audR==='R-18')?'selected':'' }}>R-18</option>
            </select>
            <div class="row" style="grid-template-columns:auto auto auto;gap:.5rem">
              <button type="submit" class="btn btn--ghost">Apply</button>
              <a href="{{ route('admin') }}" class="btn btn--ghost">Clear filters</a>
              @if(($filters['m_q_r']??'')!=='' || ($filters['m_audience_r']??'all')!=='all')
                <span class="admin-panel__tag">Filters applied</span>
              @endif
            </div>
          </form>

          <ul class="admin-collection panel-scroll">
            @forelse($moviesArchived as $movie)
              @php
                $rawTimes = $movie->showtimes->pluck('showtime')
                  ->map(fn($time) => \Carbon\Carbon::parse($time)->format('H:i'))
                  ->unique()
                  ->sort()
                  ->values();
                $showtimeValues = $rawTimes->toArray();
                $showtimeDisplay = $movie->showtimes->map(function($st){ return $st->formatted_range; })->toArray();
                $ls = $movie->listing_start ? \Carbon\Carbon::parse($movie->listing_start) : null;
                $le = $movie->listing_end ? \Carbon\Carbon::parse($movie->listing_end) : null;
                $rangeText = '';
                if ($ls && $le) {
                  $days = $ls->diffInDays($le);
                  $label = $days === 30 ? '1 month' : ($days % 7 === 0 ? ($days/7).' week'.(($days/7)>1?'s':'') : $days.' days');
                  $rangeText = 'Showing: '.$ls->format('M d, Y').' – '.$le->format('M d, Y').' ('.$label.')';
                }
              @endphp
              <li class="admin-collection__item admin-collection__item--inactive"
                  data-movie-id="{{ $movie->id }}"
                  data-title="{{ $movie->title }}"
                  data-price="{{ $movie->price }}"
                  data-audience="{{ $movie->audience }}"
                  data-poster="{{ $movie->poster }}"
                  data-bookings="{{ $movie->booking_items_count ?? 0 }}"
                  data-is-active="0"
                  data-showtimes='@json($showtimeValues)'>
                <div class="admin-movie__header">
                  <div>
                    <h3 class="admin-collection__title">{{ $movie->title }}</h3>
                    <p class="admin-collection__subtitle">
                      ₱<span class="admin-movie__price">{{ number_format($movie->price, 2) }}</span> •
                      <span class="admin-movie__audience">{{ $movie->audience }}</span> •
                      <span class="admin-movie__bookings">{{ $movie->booking_items_count ?? 0 }}</span> bookings
                      @if($rangeText)
                        <br/><span class="muted">{{ $rangeText }}</span>
                      @endif
                    </p>
                  </div>
                  <span class="badge badge--inactive admin-movie__status">Inactive</span>
                </div>
                <div class="admin-movie__showtimes">
                  @if(count($showtimeDisplay))
                    @foreach($showtimeDisplay as $displayTime)
                      <span class="showtime-chip showtime-chip--static">{{ $displayTime }}</span>
                    @endforeach
                  @else
                    <span class="admin-movie__empty">No showtimes scheduled</span>
                  @endif
                </div>
                <div class="admin-collection__actions" role="group" aria-label="Manage movie actions">
                  <form action="{{ url('/admin/movies/'.$movie->id.'/status') }}" method="POST" style="display:inline">
                    @csrf
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn btn--primary btn--sm">Unarchive</button>
                  </form>
                </div>
              </li>
            @empty
              <li class="admin-collection__empty">No archived movies</li>
            @endforelse
          </ul>

          @if(method_exists($moviesArchived,'links'))
            <div class="pager mt-4">
              @php
                $rStart = max(1, $moviesArchived->currentPage() - 2);
                $rEnd = min($moviesArchived->lastPage(), $moviesArchived->currentPage() + 2);
              @endphp
              <div class="pager__inner">
                @if($moviesArchived->onFirstPage())
                  <span class="pager__link is-disabled">« Previous</span>
                @else
                  <a class="pager__link" href="{{ $moviesArchived->previousPageUrl() }}">« Previous</a>
                @endif

                @for($i = $rStart; $i <= $rEnd; $i++)
                  @if($i === $moviesArchived->currentPage())
                    <span class="pager__page is-active">{{ $i }}</span>
                  @else
                    <a class="pager__page" href="{{ $moviesArchived->url($i) }}">{{ $i }}</a>
                  @endif
                @endfor

                @if($moviesArchived->hasMorePages())
                  <a class="pager__link" href="{{ $moviesArchived->nextPageUrl() }}">Next »</a>
                @else
                  <span class="pager__link is-disabled">Next »</span>
                @endif
              </div>
            </div>
          @endif
        </div>
      </section>
  </div>
</div>
@endsection

@push('styles')
<style>
  /* Admin page container - clean, centered */
  .admin-container {
    max-width: 90rem;
    margin: 0 auto;
    padding: 0 1.5rem;
  }

  /* Admin page wrapper with consistent spacing */
  .admin-wrap {
    width: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  /* Admin row containers */
  .admin-row {
    display: grid;
    gap: 1.5rem;
    align-items: start;
    width: 100%;
  }

  /* Two-column row: Manage Movies + All Bookings */
  .admin-row--two-cols {
    grid-template-columns: 1fr;
  }

  @media (min-width: 1024px) {
    .admin-row--two-cols {
      grid-template-columns: 58fr 42fr;
      gap: 1.5rem;
    }
  }

  /* Full-width row: Active Movies, Archived Movies */
  .admin-row--full {
    grid-template-columns: 1fr;
  }

  /* Responsive stacking on mobile/tablet */
  @media (max-width: 1023px) {
    .admin-row--two-cols {
      grid-template-columns: 1fr;
    }
  }

  /* Scrollable lists inside cards */
  .panel-scroll{max-height:800px;overflow-y:auto;padding-right:6px}
  .panel-scroll::-webkit-scrollbar{width:8px}
  .panel-scroll::-webkit-scrollbar-thumb{background:#242a38;border-radius:8px}

  /* Fix All Bookings panel spacing and height */
  .admin-panel {
    height: auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
  }

  .booking-list.panel-scroll {
    margin-bottom: 0;
    flex: 1;
    min-height: 0;
  }

  .booking-list--dense {
    gap: 1rem;
    margin: 0;
    padding: 0;
  }

  /* Remove extra bottom spacing from pagination */
  .pager.mt-4 {
    margin-top: 0.75rem !important;
    margin-bottom: 0 !important;
  }

  /* Ensure admin panel body doesn't add extra space */
  .admin-panel__body {
    padding-bottom: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .admin-panel__body > *:last-child {
    margin-bottom: 0;
  }

  /* Ensure forms don't add extra margin */
  .admin-panel__body > form {
    margin-bottom: 0;
  }

  /* Fix inline form margin in bookings filter */
  .admin-panel__body form[method="GET"] {
    margin-bottom: 1rem !important;
  }

  /* Compact pagination UI */
  .pager{margin-top:1rem}
  .pager__inner{display:flex;align-items:center;justify-content:center;gap:.4rem;color:#9aa3b2;font-size:.78rem}
  .pager__link,.pager__page{display:inline-flex;align-items:center;justify-content:center;padding:.25rem .5rem;border-radius:.5rem;border:1px solid rgba(255,255,255,.08);background:#111827;color:#e5e7eb;text-decoration:none}
  .pager__page.is-active{background:#1f2937;color:#fff;border-color:#334155;font-weight:700}
  .pager__link.is-disabled{opacity:.45;pointer-events:none}
  /* Patch default Laravel paginator SVG chevrons if any appear */
  nav[role="navigation"] svg,.pagination svg{width:1rem;height:1rem}

    .showtime-builder {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .showtime-presets {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .preset-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #334155;
        background: #1e293b;
        color: #cbd5e1;
        border-radius: 0.375rem;
        cursor: pointer;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    
    .preset-btn:hover {
        background: #334155;
        border-color: #475569;
    }
    
    .preset-btn:active {
        transform: scale(0.95);
    }
    
    .showtime-input-group {
        display: flex;
        gap: 0.5rem;
    }
    
    .showtime-input-group input {
        flex: 1;
        padding: 0.5rem 0.75rem;
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 0.375rem;
        color: #e2e8f0;
        font-size: 0.875rem;
    }
    
    .showtime-input-group input:focus {
        outline: none;
        border-color: #6366f1;
    }
    
    .showtime-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 2.5rem;
        padding: 0.5rem;
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 0.375rem;
        pointer-events: none;
    }
    
    .showtime-chips:empty::before {
        content: 'No showtimes added yet';
        color: #64748b;
        font-size: 0.875rem;
        font-style: italic;
    }
    
    .time-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        animation: chipIn 0.2s ease-out;
        pointer-events: auto;
    }
    
    @keyframes chipIn {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .time-chip button {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0;
        width: 1.25rem;
        height: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
        font-size: 1.25rem;
        line-height: 1;
    }
    
    .time-chip button:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .time-chip button:active {
        transform: scale(0.9);
    }
    
    .showtime-hint {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0;
    }

    /* Booking List Item Alignment */
    .booking-item--admin .booking-item__detail {
        display: grid !important;
        grid-template-columns: 2fr 1.5fr 1.5fr 1fr 0.8fr !important;
        gap: 12px !important;
        color: rgba(231,237,243,.7);
        font-size: .9rem;
        align-items: center !important;
    }

    .booking-item--admin .booking-item__footer {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid rgba(255,255,255,.08);
        color: rgba(231,237,243,.65);
        font-size: .85rem;
        display: flex !important;
        flex-wrap: wrap;
        gap: 12px !important;
        align-items: center !important;
    }

    /* Mobile responsive */
    @media (max-width: 720px) {
        .booking-item--admin .booking-item__detail {
            grid-template-columns: 1fr !important;
            gap: 8px !important;
        }
    }
</style>
@endpush

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const movieList = document.getElementById('adminMovieList');
            const activeCountEl = document.getElementById('adminActiveCount');
            const builders = new WeakMap();
      const startEl = document.getElementById('listing_start');
      const endEl = document.getElementById('listing_end');
      const errEl = document.getElementById('listing_error');

      // Date range logic (max 30 days)
      const setEndBounds = () => {
        if (!startEl || !endEl) return;
        const start = new Date(startEl.value || new Date());
        const min = new Date(start);
        const max = new Date(start);
        max.setDate(max.getDate() + 30);
        const toYMD = (d) => d.toISOString().slice(0,10);
        endEl.min = toYMD(min);
        endEl.max = toYMD(max);
        if (!endEl.value || endEl.value < endEl.min) endEl.value = endEl.min;
        if (endEl.value > endEl.max) endEl.value = endEl.max;
        if (errEl) errEl.style.display = 'none';
      };
      startEl?.addEventListener('change', setEndBounds);
      setEndBounds();

      // Quick duration presets for end date
      document.querySelectorAll('[data-duration]')?.forEach(btn => {
        btn.addEventListener('click', () => {
          if (!startEl || !endEl) return;
          const days = parseInt(btn.getAttribute('data-duration') || '14', 10);
          const start = new Date(startEl.value || new Date());
          const max = new Date(start); max.setDate(max.getDate() + 30);
          const end = new Date(start); end.setDate(end.getDate() + days);
          const toYMD = (d) => d.toISOString().slice(0,10);
          if (end > max) {
            endEl.value = toYMD(max);
            if (errEl) errEl.style.display = 'block';
          } else {
            endEl.value = toYMD(end);
            if (errEl) errEl.style.display = 'none';
          }
        });
      });

            const formatCurrency = (value) => {
                const amount = Number(value) || 0;
                return amount.toFixed(2);
            };

            const formatCurrencyDisplay = (value) => {
                const amount = Number(value) || 0;
                return new Intl.NumberFormat('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(amount);
            };

            const formatTime12Hour = (time24) => {
                const [hours24, minutes] = time24.split(':').map(Number);
                const period = hours24 >= 12 ? 'PM' : 'AM';
                const hours12 = hours24 % 12 || 12;
                return `${hours12}:${String(minutes).padStart(2, '0')} ${period}`;
            };

            const parseTime = (timeStr) => {
                if (!timeStr) return null;
                let match = timeStr.trim().match(/^(\d{1,2}):(\d{2})$/);
                if (match) {
                    const hours = Number(match[1]);
                    const minutes = Number(match[2]);
                    if (hours >= 0 && hours < 24 && minutes >= 0 && minutes < 60) {
                        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                    }
                }
                match = timeStr.trim().match(/^(\d{1,2}):(\d{2})\s*(am|pm)$/i);
                if (match) {
                    let hours = Number(match[1]);
                    const minutes = Number(match[2]);
                    const period = match[3].toLowerCase();
                    if (hours < 1 || hours > 12 || minutes < 0 || minutes >= 60) return null;
                    if (period === 'pm' && hours !== 12) hours += 12;
                    if (period === 'am' && hours === 12) hours = 0;
                    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                }
                return null;
            };

            class ShowtimeBuilder {
                constructor(root, initialTimes = []) {
                    this.root = root;
                    this.input = root.querySelector('[data-builder-input]');
                    this.addBtn = root.querySelector('[data-builder-add]');
                    this.chips = root.querySelector('[data-builder-chips]');
                    this.hidden = root.querySelector('[data-builder-hidden]');
                    this.selectedTimes = new Set();
                    this.onUpdateCallbacks = [];

                    const presetButtons = root.querySelectorAll('.preset-btn');
                    presetButtons.forEach(btn => {
                        btn.addEventListener('click', () => {
                            const presetTimes = btn.dataset.times || '';
                            presetTimes.split(',').map(t => t.trim()).filter(Boolean).forEach(time => this.addTime(time));
                        });
                    });

                    this.addBtn?.addEventListener('click', () => this.commitInput());
                    this.input?.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            this.commitInput();
                        }
                    });

                    this.chips?.addEventListener('click', (event) => {
                        const target = event.target.closest('[data-remove-time]');
                        if (target) {
                            const time = target.dataset.removeTime;
                            this.removeTime(time);
                        }
                    });

                    this.setTimes(initialTimes);
                }

                commitInput() {
                    if (!this.input) return;
                    const raw = this.input.value.trim();
                    if (!raw) return;
                    const candidateTimes = raw.split(',').map(t => t.trim()).filter(Boolean);
                    let hasInvalid = false;
                    candidateTimes.forEach(timeString => {
                        const parsed = parseTime(timeString);
                        if (parsed) {
                            this.selectedTimes.add(parsed);
                        } else {
                            hasInvalid = true;
                        }
                    });
                    if (hasInvalid) {
                        this.input.classList.add('input--error');
                        setTimeout(() => this.input.classList.remove('input--error'), 1200);
                    } else {
                        this.input.value = '';
                    }
                    this.render();
                }

                addTime(time) {
                    const parsed = parseTime(time);
                    if (!parsed) return;
                    this.selectedTimes.add(parsed);
                    this.render();
                }

                removeTime(time) {
                    this.selectedTimes.delete(time);
                    this.render();
                }

                render() {
                    if (this.hidden) {
                        this.hidden.value = this.getTimes().join(',');
                    }

                    if (this.chips) {
                        this.chips.innerHTML = '';
                        this.getTimes().forEach(time => {
                            const chip = document.createElement('span');
                            chip.className = 'time-chip';
                            chip.setAttribute('data-time', time);
                            chip.innerHTML = `
                                <span>${formatTime12Hour(time)}</span>
                                <button type="button" data-remove-time="${time}" aria-label="Remove ${formatTime12Hour(time)}">×</button>
                            `;
                            this.chips.appendChild(chip);
                        });
                    }

                    this.onUpdateCallbacks.forEach(cb => cb(this.getTimes()));
                }

                getTimes() {
                    return Array.from(this.selectedTimes).sort((a, b) => a.localeCompare(b));
                }

                setTimes(times = []) {
                    this.selectedTimes = new Set(times.filter(Boolean).map(parseTime).filter(Boolean));
                    this.render();
                }

                onUpdate(callback) {
                    if (typeof callback === 'function') {
                        this.onUpdateCallbacks.push(callback);
                    }
                }
            }

            const initPriceControl = (control) => {
                const input = control.querySelector('[data-price-input]');
                const buttons = control.querySelectorAll('[data-price-dir]');
                if (!input) return;
                buttons.forEach(button => {
                    button.addEventListener('click', () => {
                        const direction = button.dataset.priceDir === 'up' ? 1 : -1;
                        const step = Number(input.step || 10);
                        const min = Number(input.min || 0);
                        const max = input.max ? Number(input.max) : null;
                        const current = Number(input.value || min || 0);
                        let next = current + (direction * step);
                        if (!Number.isFinite(next)) next = min || 0;
                        if (max !== null && Number.isFinite(max)) next = Math.min(max, next);
                        next = Math.max(min, next);
                        const hasDecimals = step % 1 !== 0;
                        input.value = hasDecimals ? next.toFixed(2) : String(Math.round(next));
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            };

            document.querySelectorAll('[data-price-control]').forEach(initPriceControl);

            document.querySelectorAll('[data-showtime-builder]').forEach(root => {
                let initial = [];
                const attr = root.dataset.initialTimes;
                if (attr) {
                    try {
                        initial = JSON.parse(attr);
                    } catch (_) {
                        initial = [];
                    }
                } else if (root.dataset.builderContext === 'create') {
                    initial = [];
                }
                const builder = new ShowtimeBuilder(root, initial);
                builders.set(root, builder);
            });

      const updateActiveCount = () => { /* counts displayed from KPIs; no-op */ };

            const ensureEmptyState = () => {
                if (!movieList) return;
                const hasItems = movieList.querySelector('.admin-collection__item');
                let emptyEl = movieList.querySelector('.admin-collection__empty');
                if (!hasItems && !emptyEl) {
                    emptyEl = document.createElement('li');
                    emptyEl.className = 'admin-collection__empty';
                    emptyEl.textContent = 'No movies yet';
                    movieList.appendChild(emptyEl);
                } else if (hasItems && emptyEl) {
                    emptyEl.remove();
                }
            };

            const toggleEditor = (card, open) => {
                const editor = card.querySelector('.admin-movie__editor');
                if (!editor) return;
                if (open) {
                    editor.hidden = false;
                    card.classList.add('admin-collection__item--editing');
                } else {
                    editor.hidden = true;
                    card.classList.remove('admin-collection__item--editing');
                }
            };

            const updateToggleButton = (button, isActive) => {
                if (!button) return;
                button.textContent = isActive ? 'Set Inactive' : 'Set Active';
                if (isActive) {
                    button.classList.remove('btn--primary');
                    if (!button.classList.contains('btn--ghost')) {
                        button.classList.add('btn--ghost');
                    }
                } else {
                    button.classList.add('btn--primary');
                    button.classList.remove('btn--ghost');
                }
            };

            const updateStatusBadge = (badge, isActive) => {
                if (!badge) return;
                badge.textContent = isActive ? 'Active' : 'Inactive';
                badge.classList.toggle('badge--active', isActive);
                badge.classList.toggle('badge--inactive', !isActive);
            };

            const refreshShowtimePills = (container, times) => {
                if (!container) return;
                container.innerHTML = '';
                if (!times.length) {
                    const empty = document.createElement('span');
                    empty.className = 'admin-movie__empty';
                    empty.textContent = 'No showtimes scheduled';
                    container.appendChild(empty);
                    return;
                }
                times.forEach(time => {
                    const pill = document.createElement('span');
                    pill.className = 'showtime-chip showtime-chip--static';
                    pill.textContent = formatTime12Hour(time);
                    container.appendChild(pill);
                });
            };

            const handleMovieCard = (card) => {
                const editBtn = card.querySelector('.admin-movie__edit');
                const toggleBtn = card.querySelector('.admin-movie__toggle');
                const deleteBtn = card.querySelector('.admin-movie__delete');
                const form = card.querySelector('.admin-edit-form');
                const builderRoot = form?.querySelector('[data-showtime-builder]');
                const builder = builderRoot ? builders.get(builderRoot) : null;

                const priceEl = card.querySelector('.admin-movie__price');
                const audienceEl = card.querySelector('.admin-movie__audience');
                const statusBadge = card.querySelector('.admin-movie__status');
                const showtimeContainer = card.querySelector('.admin-movie__showtimes');
                const activeToggleBtn = toggleBtn;

                const priceInput = form?.querySelector('[data-price-input]');
                const audienceSelect = form?.querySelector('[data-edit-audience]');
                const posterInput = form?.querySelector('[data-edit-poster]');
                const cancelBtn = form?.querySelector('.admin-edit-cancel');

                editBtn?.addEventListener('click', () => {
                    const isOpen = !card.querySelector('.admin-movie__editor')?.hidden;
                    toggleEditor(card, !isOpen);
                });

                cancelBtn?.addEventListener('click', () => {
                    if (!form) return;
                    priceInput.value = formatCurrency(card.dataset.price);
                    audienceSelect.value = card.dataset.audience;
                    posterInput.value = card.dataset.poster || '';
                    const showtimes = card.dataset.showtimes ? JSON.parse(card.dataset.showtimes) : [];
                    builder?.setTimes(showtimes);
                    toggleEditor(card, false);
                });

                form?.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!builder || !priceInput || !audienceSelect) return;

                    const movieId = card.dataset.movieId;
                    const payload = {
                        title: card.dataset.title,
                        price: Number(priceInput.value || 0),
                        poster: posterInput.value.trim() || null,
                        audience: audienceSelect.value,
                        showtimes: builder.getTimes(),
                        is_active: card.dataset.isActive === '1',
                    };

                    try {
                        const response = await fetch(`/api/movies/${movieId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            alert(data?.error || 'Failed to update movie. Please try again.');
                            return;
                        }

                        const updated = data.movie;
                        if (updated) {
                            card.dataset.price = formatCurrency(updated.price);
                            card.dataset.audience = updated.audience;
                            card.dataset.poster = (updated.poster || '').trim();
                            card.dataset.showtimes = JSON.stringify(updated.showtimes || []);

                            priceEl.textContent = formatCurrencyDisplay(updated.price);
                            audienceEl.textContent = updated.audience;
                            refreshShowtimePills(showtimeContainer, updated.showtimes || []);
                            builder.setTimes(updated.showtimes || []);
                        }

                        toggleEditor(card, false);
                    } catch (error) {
                        console.error(error);
                        alert('Network error while updating the movie.');
                    }
                });

                toggleBtn?.addEventListener('click', async () => {
                    const movieId = card.dataset.movieId;
                    const targetStatus = card.dataset.isActive === '1' ? 'inactive' : 'active';
                    toggleBtn.disabled = true;
                    try {
                        const response = await fetch(`/admin/movies/${movieId}/status`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ status: targetStatus }),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || !data.success) {
                            alert(data?.error || 'Failed to update movie status.');
                            return;
                        }

                        const isActive = data.is_active === true;
                        card.dataset.isActive = isActive ? '1' : '0';
                        card.classList.toggle('admin-collection__item--inactive', !isActive);
                        updateToggleButton(activeToggleBtn, isActive);
                        updateStatusBadge(statusBadge, isActive);
                        updateActiveCount();
                    } catch (error) {
                        console.error(error);
                        alert('Network error while updating the movie status.');
                    } finally {
                        toggleBtn.disabled = false;
                    }
                });

                deleteBtn?.addEventListener('click', async () => {
                    const movieTitle = card.dataset.title;
                    if (!confirm(`Delete ${movieTitle}? This cannot be undone.`)) return;
                    deleteBtn.disabled = true;
                    try {
                        const response = await fetch(`/admin/movies/${card.dataset.movieId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            alert(data?.error || 'Failed to delete movie.');
                            return;
                        }
                        const builderRootEl = form?.querySelector('[data-showtime-builder]');
                        if (builderRootEl) builders.delete(builderRootEl);
                        card.remove();
                        updateActiveCount();
                        ensureEmptyState();
                    } catch (error) {
                        console.error(error);
                        alert('Network error while deleting the movie.');
                    } finally {
                        deleteBtn.disabled = false;
                    }
                });

                updateToggleButton(activeToggleBtn, card.dataset.isActive === '1');
            };

            movieList?.querySelectorAll('.admin-collection__item').forEach(card => handleMovieCard(card));
            updateActiveCount();
            ensureEmptyState();
        });
    </script>
@endpush

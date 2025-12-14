@extends('layouts.app')

@section('page', 'home')
@section('title', 'Cashier — Now Showing')

@section('content')
  <div class="staff-dashboard">
    <div class="staff-dashboard__header">
      <div>
        <p class="eyebrow">Now Showing / Upcoming Today</p>
        <h1 class="dash-title">Now Showing / Upcoming Today</h1>
        <p class="muted">As of {{ $now->format('M d, Y g:i A') }}</p>
      </div>
      <button class="btn dash-refresh" type="button" onclick="window.location.reload()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12a9 9 0 1 1-2.64-6.36"/>
          <path d="M21 3v6h-6"/>
        </svg>
        Refresh
      </button>
    </div>

    <div class="stat-grid">
      <div class="kpi kpi--bookings">
        <div class="kpi__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4V6z" />
            <path d="M12 10h4" />
            <path d="M12 14h4" />
          </svg>
        </div>
        <div class="kpi__meta">
          <span class="kpi__label">Expected Revenue</span>
          <span class="kpi__value">₱{{ number_format($expectedRevenueTotal ?? 0, 0) }}</span>
          <span class="kpi__hint">From upcoming shows</span>
        </div>
      </div>
      <div class="kpi kpi--movies">
        <div class="kpi__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="8" />
            <polyline points="12 7 12 12 16 14" />
          </svg>
        </div>
        <div class="kpi__meta">
          <span class="kpi__label">Current Occupancy</span>
          <span class="kpi__value">{{ $activeOccupancy ?? 0 }}%</span>
          <span class="kpi__hint">{{ empty($nowShowing) ? 'No active screenings' : 'Live screenings in progress' }}</span>
        </div>
      </div>
      <div class="kpi kpi--paid">
        <div class="kpi__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12" />
            <path d="M9 17l.01-.01" />
          </svg>
        </div>
        <div class="kpi__meta">
          <span class="kpi__label">Upcoming Reserved</span>
          <span class="kpi__value">{{ $upcomingReservedTotal ?? 0 }}</span>
          <span class="kpi__hint">Tickets pre-booked</span>
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
          <span class="kpi__label">Upcoming Shows</span>
          <span class="kpi__value">{{ $upcomingShowsCount ?? 0 }}</span>
          <span class="kpi__hint">Scheduled today</span>
        </div>
      </div>
    </div>

    <section class="panel live-panel">
      <div class="section-heading">
        <div class="section-heading__title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v4l3 3"/>
          </svg>
          <span>Now Showing</span>
        </div>
      </div>
      @if(empty($nowShowing))
        <div class="empty-state">
          <div class="empty-state__icon">
            <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <circle cx="12" cy="12" r="10" />
              <line x1="9" y1="9" x2="15" y2="15" />
              <line x1="15" y1="9" x2="9" y2="15" />
            </svg>
          </div>
          <p class="empty-state__title">No screenings are currently in progress.</p>
          <p class="empty-state__hint">
            The next screening starts at
            <strong>{{ $nextScreeningTime ? $nextScreeningTime->format('g:i A') : '—' }}</strong>
          </p>
        </div>
      @else
        <div class="live-grid">
          @foreach($nowShowing as $show)
            <article class="screening-card screening-card--live">
              <div class="time-badge">
                <span class="time-badge__time">{{ \Carbon\Carbon::createFromFormat('H:i', $show['start_time'])->format('H:i') }}</span>
                <span class="time-badge__label">Live</span>
              </div>
              <div class="screening-card__body">
                <div class="screening-card__top">
                  <div>
                    <div class="screening-card__title">{{ $show['title'] }}</div>
                    <div class="screening-card__meta">Cinema {{ str_replace('Cinema ', '', $show['cinema']) }} • {{ $show['start_time'] }}–{{ $show['end_time'] }}</div>
                  </div>
                  <span class="status-badge status-badge--live">{{ $show['booked'] }}/{{ $show['capacity'] }}</span>
                </div>
                <div class="progress-row">
                  <div class="progress-row__label">Occupancy</div>
                  <div class="progress-row__value">{{ $show['progress'] }}%</div>
                </div>
                <div class="progress-bar">
                  <span style="width: {{ min(100, max(0, $show['progress'])) }}%"></span>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </section>

    <section class="panel upcoming-panel">
      <div class="section-heading">
        <div class="section-heading__title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="3"/>
            <path d="M8 2v4m8-4v4M3 10h18"/>
          </svg>
          <span>Upcoming Today</span>
        </div>
        <span class="pill pill--soft">{{ $upcomingShowsCount ?? 0 }} Screenings</span>
      </div>

      @if(empty($upcomingShowtimes))
        <div class="empty-state">
          <p class="empty-state__title">No upcoming screenings today.</p>
          <p class="empty-state__hint">Add new showtimes to see them here.</p>
        </div>
      @else
        <div class="upcoming-list">
          @foreach($upcomingShowtimes as $show)
            <article class="screening-card">
              <div class="time-badge">
                <span class="time-badge__time">{{ \Carbon\Carbon::createFromFormat('H:i', $show['start_time'])->format('H:i') }}</span>
                <span class="time-badge__label">Start</span>
              </div>
              <div class="screening-card__body">
                <div class="screening-card__top">
                  <div>
                    <div class="screening-card__title">{{ $show['title'] }}</div>
                    <div class="screening-card__meta">Cinema {{ str_replace('Cinema ', '', $show['cinema']) }} • {{ $show['start_time'] }}–{{ $show['end_time'] }} • {{ $show['duration'] }} mins</div>
                  </div>
                  <span class="status-badge">Scheduled</span>
                </div>
                <div class="progress-row">
                  <div class="progress-row__label">Pre-booked Seats</div>
                  <div class="progress-row__value">{{ $show['booked'] }}/{{ $show['capacity'] }}</div>
                </div>
                <div class="progress-bar">
                  <span style="width: {{ min(100, max(0, $show['progress'])) }}%"></span>
                </div>
                <div class="screening-card__footer">
                  <span class="muted">{{ $show['progress'] }}% reserved</span>
                  <span class="expected">Expected ₱{{ number_format($show['expected_revenue'] ?? 0, 0) }} <span class="muted">({{ $show['booked'] }} tickets)</span></span>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </section>
  </div>
@endsection

@extends('layouts.app')

@section('title', 'Admin Dashboard — XentroCine')
@section('page', 'admin')

@section('content')
<div class="admin-container">
  <div class="analytics-header">
    <div>
      <p class="eyebrow">Complete business analytics & performance metrics</p>
      <h1>Admin Dashboard</h1>
      <p class="admin-intro">Monthly revenue, trends, and operational insights at a glance.</p>
    </div>
    <div class="analytics-actions">
      <select class="analytics-select" id="monthSelect">
        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $idx => $monthName)
          <option value="{{ $idx + 1 }}" {{ ($analytics['period']['monthNum'] ?? now()->month) == $idx + 1 ? 'selected' : '' }}>
            {{ $monthName }}
          </option>
        @endforeach
      </select>
      <select class="analytics-select" id="yearSelect">
        @php
          $currentYear = now()->year;
          $startYear = 2020; // Adjust as needed
        @endphp
        @for($y = $currentYear; $y >= $startYear; $y--)
          <option value="{{ $y }}" {{ ($analytics['period']['year'] ?? now()->year) == $y ? 'selected' : '' }}>
            {{ $y }}
          </option>
        @endfor
      </select>
    </div>
  </div>

  @php
    $growth = $analytics['month']['growthPct'] ?? null;
    $dailyMax = max(array_column($analytics['dailyTrend'] ?? [], 'revenue')) ?: 1;
    $topMax = ($analytics['topMovies']->max('revenue') ?? 1);
  @endphp

  <div class="analytics-kpis">
    <div class="kpi kpi--green">
      <div class="kpi__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="3 17 9 11 13 15 21 7" />
          <polyline points="14 7 21 7 21 14" />
        </svg>
      </div>
      <div class="kpi__meta">
        <span class="kpi__label">Monthly Revenue</span>
        <span class="kpi__value">₱{{ number_format($analytics['month']['revenue'] ?? 0, 0) }}</span>
        <span class="kpi__hint">
          @if(!is_null($growth))
            <span class="trend trend--up">+{{ number_format($growth, 1) }}%</span> vs last month
          @else
            No previous month data
          @endif
        </span>
      </div>
    </div>
    <div class="kpi kpi--blue">
      <div class="kpi__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="7" r="4" />
          <path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" />
        </svg>
      </div>
      <div class="kpi__meta">
        <span class="kpi__label">Total Customers</span>
        <span class="kpi__value">{{ number_format($analytics['month']['customers'] ?? 0) }}</span>
        <span class="kpi__hint">Unique this month</span>
      </div>
    </div>
    <div class="kpi kpi--purple">
      <div class="kpi__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="16" rx="2.5" />
          <line x1="9" y1="4" x2="9" y2="20" />
          <line x1="15" y1="4" x2="15" y2="20" />
          <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
      </div>
      <div class="kpi__meta">
        <span class="kpi__label">Total Bookings</span>
        <span class="kpi__value">{{ number_format($analytics['month']['bookings'] ?? 0) }}</span>
        <span class="kpi__hint">{{ number_format($analytics['month']['tickets'] ?? 0) }} tickets</span>
      </div>
    </div>
    <div class="kpi kpi--orange">
      <div class="kpi__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="4" y="4" width="16" height="16" rx="3" />
          <path d="M8 12h8M8 8h6M8 16h4" />
        </svg>
      </div>
      <div class="kpi__meta">
        <span class="kpi__label">Avg Ticket Price</span>
        <span class="kpi__value">₱{{ number_format($analytics['month']['avgTicket'] ?? 0, 0) }}</span>
        <span class="kpi__hint">Per ticket sold</span>
      </div>
    </div>
  </div>

  <div class="analytics-grid analytics-grid--main">
    <section class="analytics-panel">
      <header class="analytics-panel__header">
        <div>
          <h3>Daily Revenue Trend</h3>
          <p class="admin-panel__hint">Last 7 days performance</p>
        </div>
      </header>
      <ul class="trend-list">
        @foreach($analytics['dailyTrend'] as $day)
          @php $bar = $dailyMax > 0 ? round(($day['revenue'] / $dailyMax) * 100) : 0; @endphp
          <li class="trend-row">
            <span class="trend-row__label">{{ $day['label'] }}</span>
            <div class="trend-row__bars">
              <div class="trend-bar" data-bar-width="{{ $bar }}"></div>
              <span class="trend-row__meta">₱{{ number_format($day['revenue'], 0) }} • {{ $day['bookings'] }} bookings</span>
            </div>
          </li>
        @endforeach
      </ul>
    </section>

    <section class="analytics-panel analytics-panel--side">
      <header class="analytics-panel__header">
        <div>
          <h3>Top Movies</h3>
          <p class="admin-panel__hint">By revenue (this month)</p>
        </div>
      </header>
      <ol class="top-movies">
        @forelse($analytics['topMovies'] as $i => $movie)
          @php
            $width = $topMax > 0 ? round(($movie->revenue / $topMax) * 100) : 0;
          @endphp
          <li class="top-movie">
            <span class="top-movie__rank">{{ $i + 1 }}</span>
            <div class="top-movie__meta">
              <div class="top-movie__title">{{ $movie->title ?? 'Untitled' }}</div>
              <div class="top-movie__stats">₱{{ number_format($movie->revenue, 0) }} • {{ $movie->tickets }} bookings</div>
              <div class="mg-progress" style="max-width:220px">
                <span class="mg-progress__bar" data-bar-width="{{ $width }}"></span>
              </div>
            </div>
          </li>
        @empty
          <li class="top-movie muted">No movie revenue recorded this month.</li>
        @endforelse
      </ol>
    </section>

    <section class="analytics-panel">
      <header class="analytics-panel__header">
        <div>
          <h3>Cinema Performance</h3>
          <p class="admin-panel__hint">Revenue & occupancy by cinema</p>
        </div>
      </header>
      <div class="cinema-grid">
        @foreach($analytics['cinemas'] as $cin)
          @php
            $occColor = $cin['occupancy'] >= 80 ? 'linear-gradient(135deg,#22c55e,#16a34a)' : ($cin['occupancy'] >= 50 ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'linear-gradient(135deg,#ef4444,#b91c1c)');
          @endphp
          <article class="cinema-card">
            <div class="cinema-card__header">
              <strong>{{ $cin['name'] }}</strong>
              <span class="cinema-card__revenue">₱{{ number_format($cin['revenue'], 0) }}</span>
            </div>
            <div class="cinema-card__meta">Capacity: {{ $cin['capacity'] }} • {{ $cin['screenings'] }} screenings</div>
            <div class="cinema-card__bar">
              <span class="cinema-bar" data-bar-width="{{ $cin['occupancy'] }}" data-bar-bg="{{ $occColor }}"></span>
            </div>
            <div class="cinema-card__footer">
              <span>Avg Occupancy</span>
              <span>{{ $cin['occupancy'] }}%</span>
            </div>
          </article>
        @endforeach
      </div>
    </section>

    <section class="analytics-panel analytics-panel--side">
      <header class="analytics-panel__header">
        <div>
          <h3>Peak Hours</h3>
          <p class="admin-panel__hint">Busiest time slots (last 30 days)</p>
        </div>
      </header>
      <ul class="trend-list">
        @php $maxPeak = max(array_column($analytics['peakHours'], 'bookings')); @endphp
        @foreach($analytics['peakHours'] as $label => $slot)
          @php $w = $maxPeak ? round(($slot['bookings'] / $maxPeak) * 100) : 0; @endphp
          <li class="trend-row">
            <span class="trend-row__label">{{ $label }}</span>
            <div class="trend-row__bars">
              <div class="trend-bar trend-bar--purple" data-bar-width="{{ $w }}"></div>
              <span class="trend-row__meta">{{ $slot['bookings'] }} bookings</span>
            </div>
          </li>
        @endforeach
      </ul>
    </section>

    <section class="analytics-panel analytics-panel--mini">
      <header class="analytics-panel__header">
        <div>
          <h3>Payment Methods</h3>
          <p class="admin-panel__hint">Current month split</p>
        </div>
      </header>
      <div class="split-bar">
  <span class="split-bar__cash" data-bar-width="{{ $analytics['paymentSplit']['cash'] }}"></span>
  <span class="split-bar__gcash" data-bar-width="{{ $analytics['paymentSplit']['gcash'] }}"></span>
      </div>
      <div class="split-labels">
        <span>Cash {{ $analytics['paymentSplit']['cash'] }}%</span>
        <span>GCash {{ $analytics['paymentSplit']['gcash'] }}%</span>
      </div>
    </section>

    <section class="analytics-panel analytics-panel--mini">
      <header class="analytics-panel__header">
        <div>
          <h3>Customer Types</h3>
          <p class="admin-panel__hint">Distribution</p>
        </div>
      </header>
      <div class="split-bar">
  <span class="split-bar__cash" data-bar-width="{{ $analytics['customerTypes']['regular'] }}"></span>
  <span class="split-bar__gcash" data-bar-width="{{ $analytics['customerTypes']['senior'] }}"></span>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Set widths for all elements with data-bar-width
  document.querySelectorAll('[data-bar-width]').forEach(function(el) {
    var width = el.getAttribute('data-bar-width');
    if (width !== null) {
      el.style.width = width + '%';
    }
  });
  // Set background for cinema bars
  document.querySelectorAll('.cinema-bar[data-bar-bg]').forEach(function(el) {
    var bg = el.getAttribute('data-bar-bg');
    if (bg) {
      el.style.background = bg;
    }
  });

  // Handle month/year dropdown changes
  var monthSelect = document.getElementById('monthSelect');
  var yearSelect = document.getElementById('yearSelect');

  function updateDashboard() {
    var month = monthSelect.value;
    var year = yearSelect.value;
    var url = new URL(window.location.href);
    url.searchParams.set('month', month);
    url.searchParams.set('year', year);
    window.location.href = url.toString();
  }

  if (monthSelect) {
    monthSelect.addEventListener('change', updateDashboard);
  }
  if (yearSelect) {
    yearSelect.addEventListener('change', updateDashboard);
  }
});
</script>
@endpush
      </div>
      <div class="split-labels">
        <span>Regular {{ $analytics['customerTypes']['regular'] }}%</span>
        <span>Senior {{ $analytics['customerTypes']['senior'] }}%</span>
      </div>
    </section>
  </div>
</div>
@endsection

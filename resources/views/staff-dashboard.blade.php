@extends('layouts.app')

@section('page', 'staff')
@section('title', 'Staff Dashboard — Now Showing')

@section('content')
  @if(!auth()->check() || auth()->user()->role !== 'staff')
    <div class="alert alert--error">Staff access only.</div>
    @php abort(403); @endphp
  @endif

  <h1>Now Showing / Next Showtimes</h1>
  <p class="muted">As of {{ $now->format('M d, Y g:i A') }}</p>

  <section style="margin-top:1rem">
    <h2>NOW SHOWING</h2>
    @if(empty($nowShowing))
      <p class="muted">No screenings are currently in progress.</p>
    @else
      <ul class="show-list">
        @foreach($nowShowing as $s)
          <li class="show-item">
            <strong>[{{ $s['start_time'] }}–{{ $s['end_time'] }}]</strong>
            {{ $s['title'] }} — <em>{{ $s['cinema'] }}</em>
            <span class="badge">{{ $s['booked'] }}/{{ $s['capacity'] }}</span>
          </li>
        @endforeach
      </ul>
    @endif
  </section>

  <section style="margin-top:2rem">
    <h2>NEXT / UPCOMING</h2>
    @if(empty($upcomingShowtimes))
      <p class="muted">No upcoming screenings today.</p>
    @else
      <ul class="show-list">
        @foreach($upcomingShowtimes as $s)
          <li class="show-item">
            <strong>[{{ $s['start_time'] }}]</strong>
            {{ $s['title'] }} — <em>{{ $s['cinema'] }}</em>
          </li>
        @endforeach
      </ul>
    @endif
  </section>

  <style>
    .show-list{list-style:none;padding:0;margin:0;display:grid;gap:.5rem}
    .show-item{display:flex;gap:.5rem;align-items:center;justify-content:flex-start;border:1px solid var(--outline);padding:.5rem .75rem;border-radius:.5rem}
    .badge{background:#1f2937;color:#fff;padding:.15rem .5rem;border-radius:.375rem;font-size:.8rem}
    .muted{color:var(--muted)}
  </style>
@endsection

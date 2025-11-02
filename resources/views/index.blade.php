@extends('layouts.app')

@section('title', 'XentroCine — Home')
@section('page', 'home')

@section('content')
<section class="hero">
  <div>
    <h1>Book your next movie night</h1>
    <p class="muted">No personal details needed to reserve — choose your movie and seats.</p>
    <a class="btn btn--primary" href="{{ route('booking') }}">Start Booking</a>
  </div>
</section>

@if($movies->isEmpty())
  <section>
    <p class="muted" style="text-align:center;padding:40px 0;">No movies available at the moment. Please check back later!</p>
  </section>
@else
  <section>
    <h2>Most Attended</h2>
    <div class="grid grid-4">
      @foreach($movies->sortByDesc(function($movie) { return strlen($movie->title); })->take(4) as $movie)
        <div class="card">
          <img class="card__img" src="{{ $movie->poster }}" alt="{{ $movie->title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $movie->title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <section>
    <h2>Most Popular</h2>
    <div class="grid grid-4">
      @foreach($movies->shuffle()->take(4) as $movie)
        <div class="card">
          <img class="card__img" src="{{ $movie->poster }}" alt="{{ $movie->title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $movie->title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <section>
    <h2>Now Showing</h2>
    <div class="grid grid-4">
      @foreach($movies as $movie)
        <div class="card">
          <img class="card__img" src="{{ $movie->poster }}" alt="{{ $movie->title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $movie->title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endif
@endsection

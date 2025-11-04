@extends('layouts.app')

@section('title', 'XentroCine — Home')
@section('page', 'home')

@section('content')

@if($movies->isEmpty())
  <section>
    <p class="muted" style="text-align:center;padding:40px 0;">No movies available at the moment. Please check back later!</p>
  </section>
@else
   <section>
    <h2>Most Attended</h2>
 <div class="movie-row" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: nowrap;">
  @foreach($movies->sortByDesc(fn($movie) => strlen($movie->title))->take(5) as $movie)
    <div class="card" style="flex: 0 0 auto; width: 268px;">
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
 <div class="movie-row" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: nowrap;">
  @foreach($movies->shuffle()->take(5) as $movie)
    <div class="card" style="flex: 0 0 auto; width: 268px;">
      <img class="card__img w-full object-cover" src="{{ $movie->poster }}" alt="{{ $movie->title }}" loading="lazy" />
      <div class="card__body">
        <div class="card__header flex items-center justify-between">
          <div class="card__title">{{ $movie->title }}</div>
          <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">
            {{ $movie->audience }}
          </span>
        </div>
        <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
      </div>
    </div>
  @endforeach
</div>
  </section>


  <section>
    <h2>Now Showing</h2>
 <div class="movie-row" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: nowrap;">
  @foreach($movies->shuffle()->take(5) as $movie)
    <div class="card" style="flex: 0 0 auto; width: 268px;">
      <img class="card__img w-full object-cover" src="{{ $movie->poster }}" alt="{{ $movie->title }}" loading="lazy" />
      <div class="card__body">
        <div class="card__header flex items-center justify-between">
          <div class="card__title">{{ $movie->title }}</div>
          <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">
            {{ $movie->audience }}
          </span>
        </div>
        <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
      </div>
    </div>
  @endforeach
</div>
  </section>
@endif
@endsection

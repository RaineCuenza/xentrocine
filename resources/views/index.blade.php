@extends('layouts.app')

@section('title', 'XentroCine — Home')
@section('page', 'home')

@php
  use Illuminate\Support\Str;
@endphp

@section('content')

@if($mostAttended->isEmpty() && $nowShowing->isEmpty() && $mostPopular->isEmpty() && $topWatched->isEmpty() && $comingSoon->isEmpty())
  <section>
    <p class="muted" style="text-align:center;padding:40px 0;">No movies available at the moment. Please check back later!</p>
  </section>
@else
  <section class="home-search">
    <form id="movieSearchForm" class="home-search__wrapper" autocomplete="off">
      <div class="home-search__field">
        <span class="home-search__icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="11" cy="11" r="7"></circle>
            <line x1="20" y1="20" x2="16.65" y2="16.65"></line>
          </svg>
        </span>
        <input id="movieSearch" class="home-search__input" type="search" placeholder="Search movies…" aria-label="Search movies" />
      </div>
    </form>
  </section>

  <section class="movie-section" data-filter-section>
    <h2 class="movie-section__title">Most Attended</h2>
    <div class="movie-row" data-movie-row>
      @foreach($mostAttended as $movie)
        @php
          $title = $movie->title;
          $poster = $movie->poster ?: '/images/placeholders/movie.jpg';
          $price = $movie->price ? '₱'.number_format((float)$movie->price, 2) : null;
        @endphp
        <article class="card movie-card" data-movie-card data-title="{{ Str::lower($title) }}">
          <img class="card__img" src="{{ $poster }}" alt="{{ $title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <div class="card__footer">
              @if($price)<span class="release-date">{{ $price }}</span>@endif
              <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>
    <p class="movie-row__empty" data-empty-message hidden>No movies found. Try a different title.</p>
  </section>

  <section class="movie-section" data-filter-section>
    <h2 class="movie-section__title">Now Showing</h2>
    <div class="movie-row" data-movie-row>
      @foreach($nowShowing as $movie)
        @php
          $title = $movie->title;
          $poster = $movie->poster ?: '/images/placeholders/movie.jpg';
          $price = $movie->price ? '₱'.number_format((float)$movie->price, 2) : null;
        @endphp
        <article class="card movie-card" data-movie-card data-title="{{ Str::lower($title) }}">
          <img class="card__img" src="{{ $poster }}" alt="{{ $title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <div class="card__footer">
              <span class="status-badge status-badge--now">Now Showing</span>
              @if($price)<span class="release-date">{{ $price }}</span>@endif
              <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>
    <p class="movie-row__empty" data-empty-message hidden>No movies found. Try a different title.</p>
  </section>

  <section class="movie-section" data-filter-section>
    <h2 class="movie-section__title">Most Popular</h2>
    <div class="movie-row" data-movie-row>
      @foreach($mostPopular as $movie)
        @php
          $title = $movie->title;
          $poster = $movie->poster ?: '/images/placeholders/movie.jpg';
          $price = $movie->price ? '₱'.number_format((float)$movie->price, 2) : null;
        @endphp
        <article class="card movie-card" data-movie-card data-title="{{ Str::lower($title) }}">
          <img class="card__img" src="{{ $poster }}" alt="{{ $title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <div class="card__footer">
              @if($price)<span class="release-date">{{ $price }}</span>@endif
              <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>
    <p class="movie-row__empty" data-empty-message hidden>No movies found. Try a different title.</p>
  </section>

  <section class="movie-section" data-filter-section>
    <h2 class="movie-section__title">Top Watched</h2>
    <div class="movie-row" data-movie-row>
      @foreach($topWatched as $movie)
        @php
          $title = $movie->title;
          $poster = $movie->poster ?: '/images/placeholders/movie.jpg';
          $price = $movie->price ? '₱'.number_format((float)$movie->price, 2) : null;
        @endphp
        <article class="card movie-card" data-movie-card data-title="{{ Str::lower($title) }}">
          <img class="card__img" src="{{ $poster }}" alt="{{ $title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <div class="card__footer">
              @if($price)<span class="release-date">{{ $price }}</span>@endif
              <a href="{{ route('booking', ['movie' => $movie->id]) }}" class="btn btn--ghost">Buy Tickets</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>
    <p class="movie-row__empty" data-empty-message hidden>No movies found. Try a different title.</p>
  </section>

  @if($comingSoon->isNotEmpty())
  <section class="movie-section" data-filter-section>
    <h2 class="movie-section__title">Coming Soon</h2>
    <div class="movie-row" data-movie-row>
      @foreach($comingSoon as $movie)
        @php
          $title = $movie->title;
          $poster = $movie->poster ?: '/images/placeholders/movie.jpg';
          $release = optional($movie->listing_start)->format('d M Y');
        @endphp
        <article class="card movie-card" data-movie-card data-title="{{ Str::lower($title) }}">
          <img class="card__img" src="{{ $poster }}" alt="{{ $title }}" loading="lazy" />
          <div class="card__body">
            <div class="card__header">
              <div class="card__title">{{ $title }}</div>
              <span class="aud-badge aud-badge--{{ str_replace([' ','_'], ['-','-'], $movie->audience) }}">{{ $movie->audience }}</span>
            </div>
            <div class="card__footer">
              <span class="status-badge status-badge--soon">Coming Soon</span>
              <span class="release-date">{{ $release }}</span>
              <a href="#" class="btn btn--ghost" aria-disabled="true">View Details</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>
    <p class="movie-row__empty" data-empty-message hidden>No movies found. Try a different title.</p>
  </section>
  @endif
@endif
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('movieSearch');
    const searchForm = document.getElementById('movieSearchForm');
    const filterSections = Array.from(document.querySelectorAll('[data-filter-section]'));

    if (!searchInput || !searchForm) return;

    const normalise = (value) => value?.toLowerCase().trim() ?? '';

    const applyFilter = () => {
      const query = normalise(searchInput.value);
      filterSections.forEach(section => {
        const cards = Array.from(section.querySelectorAll('[data-movie-card]'));
        let matches = 0;

        cards.forEach(card => {
          const title = card.dataset.title || '';
          const isMatch = !query || title.includes(query);
          card.toggleAttribute('hidden', !isMatch);
          card.classList.toggle('movie-card--hidden', !isMatch);
          if (isMatch) matches++;
        });

        const emptyMessage = section.querySelector('[data-empty-message]');
        if (emptyMessage) {
          emptyMessage.hidden = matches !== 0;
        }
      });
    };

    const debounce = (fn, delay = 150) => {
      let timeout;
      return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(null, args), delay);
      };
    };

    const debouncedFilter = debounce(applyFilter, 120);

    searchForm.addEventListener('submit', (event) => {
      event.preventDefault();
      applyFilter();
    });

    searchInput.addEventListener('input', debouncedFilter);
  });
</script>
@endpush

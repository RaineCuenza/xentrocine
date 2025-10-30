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

<section>
  <h2>Top Rated</h2>
  <div id="topRated" class="grid grid-4"></div>
</section>

<section>
  <h2>Most Popular</h2>
  <div id="mostPopular" class="grid grid-4"></div>
</section>

<section>
  <h2>Now Showing</h2>
  <div id="allMovies" class="grid grid-4"></div>
</section>
@endsection

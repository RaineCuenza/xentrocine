<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'XentroCine')</title>
  @vite(['resources/css/styles.css', 'resources/js/app-api.js'])
  @stack('styles')
</head>
<body data-page="@yield('page', 'home')">
  <header class="site-header">
    <a class="brand" href="{{ route('home') }}">XentroCine</a>
    <nav class="nav">
      <a class="nav__link @if(Route::currentRouteName() == 'home') nav__link--active @endif" href="{{ route('home') }}">Home</a>
      <a class="nav__link @if(Route::currentRouteName() == 'booking') nav__link--active @endif" href="{{ route('booking') }}">Booking & Reservation</a>
      <a class="nav__link @if(Route::currentRouteName() == 'about') nav__link--active @endif" href="{{ route('about') }}">About</a>
      <a class="nav__link nav__staff @if(Route::currentRouteName() == 'staff') nav__link--active @endif" href="{{ route('staff') }}" style="display:none">Staff</a>
      <a class="nav__link nav__admin @if(Route::currentRouteName() == 'admin') nav__link--active @endif" href="{{ route('admin') }}" style="display:none">Admin</a>
    </nav>
    <div class="account">
      <span id="currentUser" class="account__who">Guest</span>
      <a id="loginBtn" class="btn btn--primary" href="{{ route('login') }}">Log in / Sign up</a>
      <button id="logoutBtn" class="btn" style="display:none">Log out</button>
    </div>
  </header>

  <main class="container">
    @yield('content')
  </main>

  <footer class="site-footer">
    <small>© <span id="year"></span> XentroCine</small>
  </footer>

  @stack('scripts')
</body>
</html>

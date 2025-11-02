@extends('layouts.app')

@section('title', 'Admin — XentroCine')
@section('page', 'admin')

@section('content')
<h1>Admin Dashboard</h1>
<div class="admin-kpis">
  <div class="kpi"><div class="kpi__label">Total Movies</div><div id="kpiMovies" class="kpi__value">0</div></div>
  <div class="kpi"><div class="kpi__label">Total Bookings</div><div id="kpiBookings" class="kpi__value">0</div></div>
  <div class="kpi"><div class="kpi__label">Paid</div><div id="kpiPaid" class="kpi__value">0</div></div>
  <div class="kpi"><div class="kpi__label">Pending</div><div id="kpiPending" class="kpi__value">0</div></div>
  <div class="kpi"><div class="kpi__label">Cancelled</div><div id="kpiCancelled" class="kpi__value">0</div></div>
</div>

<div class="admin-grid">
  <div class="admin-card">
    <h2>Manage Movies</h2>
    <form id="movieForm" class="authform row" novalidate>
      @csrf
      <input id="mvTitle" type="text" placeholder="Title" required/>
      <input id="mvPrice" type="number" placeholder="Price (₱)" min="100" required/>
      <label>
        <span class="muted">Audience</span>
        <select id="mvAudience" required>
          <option value="PG" selected>PG</option>
          <option value="G">G</option>
          <option value="R-13">R-13</option>
          <option value="R-16">R-16</option>
          <option value="R-18">R-18</option>
        </select>
      </label>
      <input id="mvPoster" type="text" placeholder="Poster (/images/movie.jpg or URL)" />
      <input id="mvTimes" type="text" placeholder="Showtimes (e.g., 13:00,15:30)" required/>
      <button class="btn btn--primary" type="submit">Add Movie</button>
      <div id="movieErr" class="form-error"></div>
    </form>
    <ul id="adminMovieList" class="admin-list"></ul>
  </div>

  <div class="admin-card">
    <h2>All Bookings</h2>
    <ul id="adminBookingList" class="booking-list"></ul>
  </div>
</div>
@endsection

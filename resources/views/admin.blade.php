@extends('layouts.app')

@section('title', 'Admin — XentroCine')
@section('page', 'admin')

@section('content')
<h1>Admin Dashboard</h1>
<div class="admin-kpis">
  <div class="kpi"><div class="kpi__label">Total Movies</div><div class="kpi__value">{{ $kpis['movies'] }}</div></div>
  <div class="kpi"><div class="kpi__label">Total Bookings</div><div class="kpi__value">{{ $kpis['bookings'] }}</div></div>
  <div class="kpi"><div class="kpi__label">Paid</div><div class="kpi__value">{{ $kpis['paid'] }}</div></div>
  <div class="kpi"><div class="kpi__label">Pending</div><div class="kpi__value">{{ $kpis['pending'] }}</div></div>
  <div class="kpi"><div class="kpi__label">Cancelled</div><div class="kpi__value">{{ $kpis['cancelled'] }}</div></div>
</div>

<div class="admin-grid">
  <div class="admin-card">
    <h2>Manage Movies</h2>
    <form action="{{ url('/admin/movies') }}" method="POST" class="authform row" novalidate>
      @csrf
      <input name="title" type="text" placeholder="Title" required/>
      <input name="price" type="number" placeholder="Price (₱)" min="100" step="0.01" required/>
      <label>
        <span class="muted">Audience</span>
        <select name="audience" required>
          <option value="PG" selected>PG</option>
          <option value="G">G</option>
          <option value="R-13">R-13</option>
          <option value="R-16">R-16</option>
          <option value="R-18">R-18</option>
        </select>
      </label>
      <input name="poster" type="text" placeholder="Poster (/images/movie.jpg or URL)" />
      <input name="times" type="text" placeholder="Showtimes (comma-separated e.g., 13:00,15:30)" required/>
      <button class="btn btn--primary" type="submit">Add Movie</button>
    </form>
    
    <ul id="adminMovieList" class="admin-list">
      @forelse($movies as $movie)
        <li class="admin-item">
          <div>
            <strong>{{ $movie->title }}</strong>
            <small>₱{{ $movie->price }} • {{ $movie->audience }} • {{ $movie->booking_items_count ?? 0 }} bookings</small>
          </div>
          <form action="{{ url('/admin/movies/'.$movie->id.'/delete') }}" method="POST" style="margin:0">
            @csrf
            <button class="btn btn--ghost btn--sm">Delete</button>
          </form>
        </li>
      @empty
        <li class="muted">No movies yet</li>
      @endforelse
    </ul>
  </div>

  <div class="admin-card">
    <h2>All Bookings</h2>
    <ul id="adminBookingList" class="booking-list">
      @forelse($bookings as $booking)
        <li class="booking-item">
          <div class="booking-item__header">
            <div>
              <strong>{{ $booking->name }}</strong>
              <small style="display:block;color:var(--muted)">{{ $booking->email }}</small>
            </div>
            <span class="badge badge--{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
          </div>
          @foreach($booking->items as $item)
            <div class="booking-item__detail">
              {{ $item->movie_title }} • 
              {{ \Carbon\Carbon::parse($item->booking_date)->format('M d, Y') }} • 
              {{ date('g:i A', strtotime($item->showtime)) }} • 
              {{ $item->cinema }} • 
              {{ $item->quantity }} pax
            </div>
          @endforeach
          <div class="booking-item__footer">
            <span>Total: ₱{{ number_format($booking->total, 2) }}</span>
            <span>{{ $booking->created_at->format('M d, Y g:i A') }}</span>
          </div>
          @if($booking->status !== 'Cancelled')
            <form action="{{ url('/admin/bookings/'.$booking->id.'/cancel') }}" method="POST" style="margin-top:8px">
              @csrf
              <button class="btn btn--ghost btn--sm">Cancel Booking</button>
            </form>
          @endif
        </li>
      @empty
        <li class="muted">No bookings yet</li>
      @endforelse
    </ul>
  </div>
</div>
@endsection

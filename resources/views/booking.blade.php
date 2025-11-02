@extends('layouts.app')

@section('title', 'XentroCine — Book')
@section('page', 'booking')

@section('content')
  <!-- Email Verification Banner -->
  <div id="verificationBanner" class="verification-banner" style="display:none">
    <div class="verification-banner__content">
      <strong>⚠️ Please verify your email</strong>
      <p>You need to verify your email address before you can book tickets.</p>
      <button id="resendVerificationBtn" class="btn btn--primary">Resend Verification Email</button>
    </div>
  </div>

  <!-- Booking Form (single column) -->
  <div class="booking-main">
    <h1 class="mb-0">Book Now!</h1>
  <p class="muted">Select a movie, schedule, and number of pax. Payment options: <strong>Cash</strong> or <strong>GCash</strong>.</p>
    
    <div class="filters">
      <label>Movie
        <select id="movieSelect"></select>
      </label>
      <label>Date
        <input id="dateSelect" type="date" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" data-server-date="{{ date('Y-m-d') }}"/>
      </label>
      <label>Showtime
        <select id="showtimeSelect"></select>
      </label>
      <label>Cinema
        <select id="cinemaSelect">
          <option value="Cinema 1">Cinema 1</option>
          <option value="Cinema 2">Cinema 2</option>
        </select>
      </label>
    </div>

    <div class="pax-input">
      <label for="paxInput">Number of Pax</label>
      <input id="paxInput" type="number" min="1" max="20" value="1" style="width: 120px;">
    </div>

    <aside class="summary">
      <div class="summary__row"><span>Pax:</span><strong id="selectedSeats">1</strong></div>
      <div class="summary__row"><span>Price per pax:</span><strong>₱<span id="seatPrice">0</span></strong></div>
      <div class="summary__row summary__total"><span>Total:</span><strong>₱<span id="totalPrice">0</span></strong></div>
      <button id="addToCartBtn" class="btn" disabled>Add to cart</button>
      <div id="authHint" class="form-help" style="display:none">Log in as Client to proceed.</div>
    </aside>

    <section class="cart">
      <h3>Cart</h3>
      <ul id="cartList" class="cart__list"></ul>
      <div class="row">
        <button id="goCheckoutBtn" class="btn btn--primary" disabled>Proceed to Payment</button>
        <button id="clearCartBtn" class="btn btn--ghost" disabled>Clear Cart</button>
      </div>
    </section>
    <section class="my-bookings">
      <h3>My Bookings</h3>
      <p class="muted">Your reservation history (signed-in users only).</p>
      <ul id="bookingList" class="booking-list"></ul>
    </section>
  </div>

<section id="paymentSection" class="payment" style="display:none">
  <h2>Payment</h2>
  <div class="payment__methods">
    <label class="pmethod">
      <input type="radio" name="payMethod" value="Cash" checked/> Cash (hold for 48 hours)
    </label>
    <label class="pmethod">
      <input type="radio" name="payMethod" value="GCash"/> GCash (instant confirmation)
    </label>
  </div>

  <div id="gcashForm" class="authform" style="display:none">
    <label>GCash number
      <input id="gcashNumber" type="tel" inputmode="numeric" placeholder="09XXXXXXXXX"/>
    </label>
    <label>Reference (optional)
      <input id="gcashRef" type="text" placeholder="GCash ref #"/>
    </label>
  </div>

  <ul id="checkoutItems" class="summary-list"></ul>
  <div class="summary__row summary__total">
    <span>Total:</span>
    <strong>₱<span id="checkoutTotal">0</span></strong>
  </div>
  <button id="placeOrderBtn" class="btn btn--primary" disabled>Confirm & Place Order</button>
  <div class="form-help">Unpaid Cash reservations auto-cancel **after 48 hours**.</div>
</section>

<template id="cartItemTpl">
  <li class="cart__item">
    <div class="cart__main">
      <strong class="cart__movie"></strong>
      <span class="cart__meta"></span>
      <span class="cart__seats"></span>
    </div>
    <div class="cart__price">₱<span></span></div>
    <button class="cart__remove" title="Remove">✕</button>
  </li>
</template>
<template id="summaryItemTpl">
  <li class="summary-list__item">
    <div>
      <strong class="sum__movie"></strong>
      <div class="sum__meta"></div>
      <div class="sum__seats"></div>
    </div>
    <div class="sum__price">₱<span></span></div>
  </li>
</template>
@endsection

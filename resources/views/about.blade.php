@extends('layouts.app')

@section('title', 'About — XentroCine')
@section('page', 'about')

@section('content')
<section class="about-intro">
  <h1>About XentroCine</h1>
  <p class="about-intro__lead">
  We’re a neighbourhood-first cinema offering curated films and a seamless booking experience—reserve tickets easily from home or join us for a spontaneous movie night.  </p>
</section>

<section class="about-panels">
  <article class="about-panel">
    <h2>Why guests choose us</h2>
    <ul class="ticks">
      <li>Booking is easy—log in, choose how many tickets, and pay</li>
      <li>Flexible payments: Cash on-site or GCash</li>
      <li>Auto-cancel unpaid cash reservations to free up seats</li>
      <li>Paperless QR entry for faster cinema access</li>
    </ul>
  </article>
  <article class="about-panel">
    <h2>Made for teams &amp; families</h2>
    <p>
      Our dashboard lets staff monitor bookings, manage line-ups, and scan QR passes in real time. Families can sync seats across multiple halls, while corporate partners enjoy consolidated billing and private screenings.
    </p>
    <div class="about-pill-list">
      <span class="pill">Group reservations</span>
      <span class="pill">Walk-in counter support</span>
      <span class="pill">Discount tiers</span>
      <span class="pill">Real-time booking updates </span>
    </div>
  </article>
</section>

<section class="about-grid">
  <article class="about-card">
    <h3>Cinema 1 — Premiere</h3>
    <p>Dolby Atmos surround sound with tiered stadium seating and wall-to-wall projection for blockbuster nights.</p>
    <ul class="about-list">
      <li>225 luxury seats</li>
      <li>09:00 PM – 09:00 PM daily</li>
      <li>Accessible seating rows A–B</li>
    </ul>
  </article>

  <section class="about-panel about-panel--contact">
  <h2>Stay in touch</h2>
  <div class="about-contact">
    <div>
      <strong>Visit us</strong>
      <span>Address: C53M+5FP, Roxas Dr, Calapan City, Oriental Mindoro</span>
    </div>
    <div>
      <strong>Customer care</strong>
      <a href="mailto:hello@xentrocine.ph">hello@xentrocine.ph</a>
      <span>Phone: (043) 288 7555</span>
    </div>
    <div>
      <strong>Operating hours</strong>
      <span>Ticket booth opens 09:00 AM daily</span>
      <span>Last full show 09:00 PM</span>
    </div>  
  </div>
  <p class="about-panel__footnote">Looking to host a premiere or private block screening? Talk to our events team for bespoke packages.</p>
</section>
@endsection

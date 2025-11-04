@extends('layouts.app')

@section('title', 'About — XentroCine')
@section('page', 'about')

@section('content')
<section class="about-intro">
  <h1>About XentroCine</h1>
  <p class="about-intro__lead">
    We are a neighbourhood-first cinema offering curated films and a seamless reservation experience—book seats from your sofa or walk in for a spontaneous movie night.
  </p>
</section>

<section class="about-panels">
  <article class="about-panel">
    <h2>Why guests choose us</h2>
    <ul class="ticks">
      <li>Fast booking—log in, pay, and you’re done</li>
      <li>Flexible payments: cash at the counter or GCash online</li>
      <li>Auto-cancel unpaid reservations to free up seats for others</li>
      <li>Paperless QR entry for quicker cinema access</li>
    </ul>
  </article>
  <article class="about-panel">
    <h2>Made for teams &amp; families</h2>
    <p>
      Staff monitor bookings, manage line-ups, and scan QR passes in real time. Families can sync seats across multiple halls, while corporate partners enjoy consolidated billing and private screenings.
    </p>
    <div class="about-pill-list">
      <span class="pill">Group reservations</span>
      <span class="pill">Walk-in counter support</span>
      <span class="pill">Discount tiers</span>
      <span class="pill">Real-time booking updates</span>
    </div>
  </article>
</section>

<section class="about-grid">
  <article class="about-card">
    <h3>Cinema 1 — Premiere</h3>
    <p>Dolby Atmos surround with tiered stadium seating and wall-to-wall projection for blockbuster nights.</p>
    <ul class="about-list">
      <li>225 luxury seats</li>
      <li>09:00 AM – 09:00 PM daily</li>
      <li>Accessible seating rows A–B</li>
    </ul>
  </article>
  <article class="about-card">
    <h3>Cinema 2 — Luxe</h3>
    <p>Recliner pods, in-seat dining, and festival picks designed for boutique viewing.</p>
    <ul class="about-list">
      <li>88 reclining lounge pods</li>
      <li>Signature desserts &amp; craft coffee bar</li>
      <li>Private screening packages</li>
    </ul>
  </article>
</section>

<section class="about-story">
  <h2>How we run XentroCine</h2>
  <ol class="about-timeline">
    <li>
      <span class="about-timeline__label">Morning</span>
      <p>Programming team refreshes listings and opens same-day walk-in slots.</p>
    </li>
    <li>
      <span class="about-timeline__label">Afternoon</span>
      <p>Prints QR backups, and coordinates with corporate block screenings.</p>
    </li>
    <li>
      <span class="about-timeline__label">Evening</span>
      <p>Ushers scan digital passes while projectionists monitor sound and picture calibrations for every show.</p>
    </li>
  </ol>
</section>

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
      <span>Ticket booth opens 90:00 AM daily</span>
      <span>Last full show 09:00 PM</span>
    </div>
  </div>
  <p class="about-panel__footnote">Planning a premiere or private block screening? Our events team can customise packages to suit your audience.</p>
</section>
@endsection

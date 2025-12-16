@extends('layouts.app')

@section('title', 'About — XentroCine')
@section('page', 'about')

@section('content')
<section class="about-hero">
  <div class="about-hero__content">
    <div>
      <span class="about-hero__eyebrow">Inside XentroCine</span>
      <h1>About XentroCine</h1>
    </div>
    <div class="about-hero__copy">
      <h2>Who We Are</h2>
      <p>XentroCine is a modern cinema booking platform designed to make your movie-going experience seamless and enjoyable. We focus on convenience, comfort, and value — offering real-time listings, secure payments, and efficient booking solutions.</p>
      <p>Our mission is to bring the ultimate entertainment closer to home. We’re committed to creating memorable movie moments for families, friends, and film lovers alike — while maintaining the highest standards of service and user privacy.</p>
    </div>
    <div class="about-hero__meta" aria-label="Platform strengths">
      <span class="about-chip">Real-time listings</span>
      <span class="about-chip">Secure payments</span>
      <span class="about-chip">Effortless booking</span>
    </div>
  </div>
  <aside class="about-hero__panel" aria-label="Experience highlights">
    <h3>Designed for seamless movie nights</h3>
    <ul>
      <li>Convenience-first seat discovery for any schedule.</li>
      <li>Comfort-driven cinemas that feel premium every visit.</li>
      <li>Privacy-focused support safeguarding every transaction.</li>
    </ul>
  </aside>
</section>

<section class="about-section">
  <div class="section-heading">
    <h2>Our Services</h2>
    <p>Every touchpoint is streamlined so your next movie night is as easy as tapping “book now”.</p>
  </div>
  <div class="service-grid">
    <article class="service-card">
      <div class="service-card__icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4.5 4.5h15v15h-15z" fill="none" stroke="currentColor" stroke-width="1.5" />
          <path d="M9 7.5h6M9 12h6M9 16.5H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          <circle cx="6.5" cy="7.5" r="1" fill="currentColor" />
          <circle cx="6.5" cy="12" r="1" fill="currentColor" />
          <circle cx="6.5" cy="16.5" r="1" fill="currentColor" />
        </svg>
      </div>
      <span class="service-card__index">01</span>
      <h3>Quick &amp; Easy Booking</h3>
      <p>Instantly browse available screenings and purchase tickets within seconds.</p>
    </article>
    <article class="service-card">
      <div class="service-card__icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4.5 7.5l7.5-4.5 7.5 4.5v9l-7.5 4.5-7.5-4.5z" fill="none" stroke="currentColor" stroke-width="1.5" />
          <path d="M5.5 8l7 4 7-4M12.5 12v8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
      </div>
      <span class="service-card__index">02</span>
      <h3>Flexible Payment Options</h3>
      <p>Choose from multiple secure payment gateways for your convenience.</p>
    </article>
    <article class="service-card">
      <div class="service-card__icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M5.25 12.75l4 4 9-9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
          <rect x="3.5" y="3.5" width="17" height="17" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="1.5" />
        </svg>
      </div>
      <span class="service-card__index">03</span>
      <h3>Professional Management</h3>
      <p>A dedicated team ensures every showtime runs smoothly and on schedule.</p>
    </article>
  </div>
</section>

<section class="about-section">
  <div class="section-heading">
    <h2>Why Choose XentroCine?</h2>
    <p>Confidence built on privacy, automation, and reliable access for movie lovers.</p>
  </div>
  <div class="choose-grid">
    <article class="choose-card">
      <h3>Privacy Protected</h3>
      <p>Your data stays secure with our encrypted systems.</p>
    </article>
    <article class="choose-card">
      <h3>Smart Automation</h3>
      <p>Book, cancel, and update tickets in real time.</p>
    </article>
    <article class="choose-card">
      <h3>24/7 Access</h3>
      <p>Check showtimes and updates anytime.</p>
    </article>
    <article class="choose-card">
      <h3>Real-Time Updates</h3>
      <p>Stay in sync with schedule and maintenance alerts.</p>
    </article>
  </div>
</section>

<section class="about-section">
  <div class="section-heading">
    <h2>Our Commitment</h2>
    <p>We hold ourselves to thoughtful standards that keep every screening on point.</p>
  </div>
  <div class="commitment-grid">
    <article class="commitment-card">
      <h3>Customer First</h3>
      <p>We listen, adapt, and serve with care.</p>
    </article>
    <article class="commitment-card">
      <h3>Innovation</h3>
      <p>Continuously improving with the latest cinema tech.</p>
    </article>
    <article class="commitment-card">
      <h3>Reliability</h3>
      <p>You can count on us for every screening.</p>
    </article>
  </div>
</section>

<section class="about-section">
  <div class="section-heading">
    <h2>Cinemas Overview</h2>
    <p>Each hall is tuned for the kind of story you’re ready to experience.</p>
  </div>
  <div class="cinema-grid">
    <article class="cinema-card">
      <header class="cinema-card__header">
        <h3>Cinema 1 — Premiere</h3>
      </header>
      <p>Dolby Atmos surround with stadium seating, 225 luxury seats, open 9:00 AM – 9:00 PM daily, accessible rows A–B.</p>
    </article>
    <article class="cinema-card cinema-card--offline">
      <header class="cinema-card__header">
        <h3>Cinema 2 — Luxe</h3>
      </header>
      <p>Designed with lounge pods and cozy lighting, currently dimmed/disabled with a yellow “Under Maintenance” tag overlay.</p>
    </article>
  </div>
</section>

<section class="about-section">
  <div class="section-heading">
    <h2>How We Run XentroCine</h2>
    <p>Behind every showtime is a rhythm that keeps guests welcomed from dawn to close.</p>
  </div>
  <div class="workflow-row">
    <article class="workflow-card">
      <span class="workflow-card__icon" aria-hidden="true">☀️</span>
      <div>
        <h3>Morning</h3>
        <p>Team refreshes listings, cleans, and preps for walk-ins.</p>
      </div>
    </article>
    <article class="workflow-card">
      <span class="workflow-card__icon" aria-hidden="true">☕</span>
      <div>
        <h3>Afternoon</h3>
        <p>Handles group bookings and screenings.</p>
      </div>
    </article>
    <article class="workflow-card">
      <span class="workflow-card__icon" aria-hidden="true">🌙</span>
      <div>
        <h3>Evening</h3>
        <p>Ushers welcome guests and manage showtime flow.</p>
      </div>
    </article>
  </div>
</section>

<section class="about-section about-section--contact">
  <div class="section-heading">
    <h2>Stay in Touch</h2>
    <p>We’re here to help with bookings, screenings, and everything in between.</p>
  </div>
  <div class="contact-grid">
    <article class="contact-card">
      <h3>Visit us:</h3>
      <p>📍 C53M+5FP, Roxas Dr, Calapan City, Oriental Mindoro</p>
    </article>
    <article class="contact-card">
      <h3>Customer Care:</h3>
      <p>✉️ <a href="mailto:hello@xentrocine.ph">hello@xentrocine.ph</a></p>
      <p>📞 (043) 288 7555</p>
    </article>
    <article class="contact-card">
      <h3>Operating Hours:</h3>
      <p>🕘 Ticket booth opens 9:00 AM daily</p>
      <p>🎞️ Last full show at 9:00 PM</p>
    </article>
  </div>
</section>

<section class="about-cta">
  <div class="about-cta__inner">
    <h2>Experience the Difference</h2>
    <p>Join thousands of satisfied audiences who trust XentroCine for their movie screenings. Watch your next film fast, pay your way, and enjoy with friends.</p>
    <a class="about-cta__button" href="{{ route('booking') }}">Find a screening</a>
  </div>
</section>
@endsection

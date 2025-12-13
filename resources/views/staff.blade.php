@extends('layouts.app')

@section('page', 'staff')
@section('title', 'Cashier Dashboard - XentroCine')

@section('content')
<div class="staff-shell">
  <div class="staff-shell__top">
    <div>
      <p class="eyebrow">Cashier workspace</p>
      <h1>Scanner & Walk-in</h1>
      <p class="muted">Scan customer QR codes, take walk-in bookings, and keep tickets moving.</p>
    </div>
    <div class="pill pill--muted" id="apiHealth">Checking API...</div>
  </div>

  <div class="panel-grid">
    {{-- Scanner --}}
    <section class="panel panel--scanner" id="scannerMode">
      <header class="panel__head">
        <div>
          <p class="eyebrow">Live QR scanner</p>
          <h2>Scan Customer QR Code</h2>
          <p class="muted">Position the QR code in the frame or enter the token manually.</p>
        </div>
        <div class="panel__actions">
          <span class="pill pill--info" id="scanStatus">Auto-starting camera...</span>
          <div class="btn-row">
            <button type="button" class="btn btn--ghost" id="scanStartBtn">Start</button>
            <button type="button" class="btn btn--ghost" id="scanStopBtn">Stop</button>
            <button type="button" class="btn btn--outline" id="scanRescanBtn">Rescan</button>
          </div>
        </div>
      </header>

      <div class="scanner-stage">
        <div class="qr-shell">
          <div id="qr-reader"></div>
          <div class="qr-overlay">
            <span class="corner tl"></span>
            <span class="corner tr"></span>
            <span class="corner bl"></span>
            <span class="corner br"></span>
          </div>
          <div class="qr-status" id="scanMessage">Preparing camera...</div>
        </div>
      </div>

      <div class="manual-row">
        <input id="manualTokenInput" type="text" placeholder="Enter token manually (e.g., et_xxxxxxxxxx)">
        <button type="button" class="btn btn--primary" id="manualVerifyBtn">Verify</button>
      </div>

      <div class="result-card" id="scanResultCard" hidden></div>
    </section>

    {{-- Walk-in booking --}}
    <section class="panel panel--booking" id="walkinMode">
      <header class="panel__head">
        <div>
          <p class="eyebrow">Create walk-in booking</p>
          <h2>Create Walk-in Booking</h2>
          <p class="muted">Select movie, session, customer details, and payment.</p>
        </div>
        <div class="pill pill--muted" id="walkinStatus">Waiting for selection</div>
      </header>

      <div class="walkin-grid">
        <div class="walkin-column">
          <div class="section-title">1. Select Movie & Showtime</div>
          @php $hasMovies = !empty($movies) && $movies->count() > 0; @endphp
          @if(!$hasMovies)
            <div class="empty-tile">
              <strong>No movies available for walk-in booking.</strong>
              <p class="muted">Add movies with showtimes and valid listing dates in Admin.</p>
            </div>
          @endif
          <div class="movie-grid" id="walkinMovieGrid">
            @foreach($movies ?? [] as $mv)
              @php
                $showtimes = ($mv->showtimes ?? collect())
                  ->map(function ($st) {
                    $start = date('H:i', strtotime($st->showtime));
                    $end = $st->end_time ? date('H:i', strtotime($st->end_time)) : null;
                    return [
                      'id' => $st->id,
                      'label' => $end ? "{$start} - {$end}" : $start,
                      'time' => $start,
                    ];
                  });
              @endphp
              <button
                type="button"
                class="movie-card"
                data-movie-id="{{ $mv->id }}"
                data-title="{{ $mv->title }}"
                data-price="{{ number_format((float)($mv->price ?? 0), 2, '.', '') }}"
                data-showtimes='@json($showtimes)'
              >
                <div class="movie-card__poster">
                  @if(!empty($mv->poster))
                    <img src="{{ $mv->poster }}" alt="{{ $mv->title }} poster" loading="lazy">
                  @else
                    <div class="poster-fallback">{{ strtoupper(substr($mv->title, 0, 1)) }}</div>
                  @endif
                </div>
                <div class="movie-card__body">
                  <div class="movie-card__title">{{ $mv->title }}</div>
                  <div class="movie-card__price">₱{{ number_format((float)($mv->price ?? 0), 2) }}</div>
                  <div class="movie-card__meta">Rated {{ $mv->audience ?? 'NR' }}</div>
                  <div class="showtime-pills">
                    @forelse($showtimes as $st)
                      <span class="pill pill--soft">{{ $st['label'] }}</span>
                    @empty
                      <span class="pill pill--muted">No showtimes</span>
                    @endforelse
                  </div>
                </div>
              </button>
            @endforeach
          </div>
        </div>

        <div class="walkin-column">
          <div class="section-title">2. Session & Customer</div>
          <div class="form-grid">
            <div class="form-block">
              <label>Showtime</label>
              <div id="showtimeChipWrap" class="chip-row muted">Select a movie to load today&rsquo;s showtimes</div>
            </div>
            <div class="form-block">
              <label for="walkinCinemaSelect">Cinema</label>
              <select id="walkinCinemaSelect">
                <option value="">Select cinema</option>
                @foreach(($cinemas ?? collect()) as $cinema)
                  @php
                    $status = $cinema->status ?? 'available';
                    $isAvailable = $status === 'available';
                    $suffix = $isAvailable ? '' : ($status === 'maintenance' ? ' (Maintenance)' : ' (Inactive)');
                  @endphp
                  <option value="{{ $cinema->name }}" @if(!$isAvailable) disabled @endif>
                    {{ $cinema->name }}{{ $suffix }}
                  </option>
                @endforeach
              </select>
              <small class="muted">Unavailable cinemas can't be selected.</small>
            </div>
          </div>

          <div class="form-grid form-grid--compact">
            <div class="form-block">
              <label for="paxRegularInput">Regular pax</label>
              <input id="paxRegularInput" type="number" min="0" value="1">
            </div>
            <div class="form-block">
              <label for="paxSeniorInput">Senior pax</label>
              <input id="paxSeniorInput" type="number" min="0" value="0">
              <small class="muted">20% discount applied.</small>
            </div>
          </div>

          <div class="form-block">
            <label>Payment method</label>
            <div class="pill-row">
              <label class="pill pill--select">
                <input type="radio" name="payment_method" value="cash" checked> Cash (pay now)
              </label>
              <label class="pill pill--select">
                <input type="radio" name="payment_method" value="gcash"> GCash (already paid)
              </label>
            </div>
          </div>

          <div class="summary-card" id="bookingSummary">
            <div class="summary-row">
              <span>Movie</span>
              <strong id="summaryMovie">Not selected</strong>
            </div>
            <div class="summary-row">
              <span>Session</span>
              <strong id="summarySession">Pick showtime & cinema</strong>
            </div>
            <div class="summary-row">
              <span>Pax</span>
              <strong id="summaryPax">1 Regular / 0 Senior</strong>
            </div>
            <div class="summary-row total">
              <span>Total</span>
              <strong id="summaryTotal">₱0.00</strong>
            </div>
          </div>

          <div class="action-row">
            <div class="inline-message" id="walkinMessage">Choose a movie to begin.</div>
            <div class="btn-row">
              <button type="button" class="btn btn--ghost" id="resetWalkinBtn">Reset</button>
              <button type="button" class="btn btn--primary" id="createBookingBtn" disabled>Confirm Booking</button>
            </div>
          </div>
        </div>
      </div>

      {{-- Hidden form for graceful fallback --}}
      <form id="walkinForm" method="POST" action="{{ route('bookings.place') }}" class="d-none">
        @csrf
        <input type="hidden" name="movieId" id="formMovieId">
        <input type="hidden" name="show_date" id="formShowDate" value="{{ now()->toDateString() }}">
        <input type="hidden" name="showtime_id" id="formShowtimeId">
        <input type="hidden" name="cinema" id="formCinema">
        <input type="hidden" name="quantity" id="formQuantity" value="1">
        <input type="hidden" name="seniorQuantity" id="formSeniorQuantity" value="0">
        <input type="hidden" name="payment_method" id="formPaymentMethod" value="cash">
        <input type="hidden" name="customer_name" id="formCustomerName" value="">
      </form>
    </section>
  </div>

  {{-- Recent transactions --}}
  <section class="panel panel--table">
    <header class="panel__head">
      <div>
        <p class="eyebrow">Recent transactions</p>
        <h3>Latest tickets</h3>
        <p class="muted">Newest eight bookings handled on this account.</p>
      </div>
      <span class="pill pill--muted" id="transactionsStatus">Loading...</span>
    </header>

    <div class="table" id="transactionsTable">
      <div class="table__skeleton">Loading transactions...</div>
    </div>
  </section>
</div>
@endsection

@push('styles')
<style>
.staff-shell{max-width:1400px;margin:0 auto;padding:28px 18px 56px;color:#e7edf3;}
.staff-shell__top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px;}
.eyebrow{letter-spacing:.08em;text-transform:uppercase;font-size:.8rem;margin:0 0 4px;color:#94a3b8;}
.panel-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:18px;}
.panel{background:#11151d;border:1px solid #1f2633;border-radius:18px;padding:20px 20px 18px;box-shadow:0 18px 48px rgba(0,0,0,.35);}
.panel__head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px;}
.panel__head h2,.panel__head h3{margin:4px 0;}
.panel__actions{display:flex;flex-direction:column;align-items:flex-end;gap:10px;}
.panel--scanner{background:linear-gradient(135deg,#0f172a,#0c111b);}
.panel--booking{background:linear-gradient(135deg,#0d0f1a,#0f1624);}
.panel--table{margin-top:18px;}
.pill{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:8px 12px;font-size:.9rem;border:1px solid rgba(255,255,255,0.08);}
.pill--muted{background:rgba(255,255,255,.05);color:#a5b4c8;}
.pill--info{background:rgba(94,234,212,.1);border-color:rgba(94,234,212,.35);color:#5eead4;}
.pill--error{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.4);color:#fca5a5;}
.pill--success{background:rgba(74,222,128,.12);border-color:rgba(74,222,128,.4);color:#86efac;}
.pill--soft{background:rgba(124,92,255,.14);border-color:rgba(124,92,255,.18);color:#c4b5fd;}
.pill--select input{margin-right:8px;}
.btn-row{display:flex;gap:10px;flex-wrap:wrap;}
.btn{border:none;border-radius:12px;padding:12px 16px;font-weight:700;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease;}
.btn--primary{background:#7c5cff;color:#fff;box-shadow:0 8px 20px rgba(124,92,255,.35);}
.btn--primary:disabled{opacity:.5;cursor:not-allowed;box-shadow:none;}
.btn--ghost{background:rgba(255,255,255,.05);color:#e7edf3;border:1px solid rgba(255,255,255,.08);}
.btn--outline{background:transparent;color:#c4b5fd;border:1px solid rgba(196,181,253,.4);}
.btn:hover:not(:disabled){transform:translateY(-1px);}
.scanner-stage{margin-top:6px;}
.qr-shell{position:relative;background:#05070d;border:1px solid #1f2633;border-radius:16px;min-height:340px;overflow:hidden;}
#qr-reader{width:100%;height:100%;}
.qr-overlay{pointer-events:none;position:absolute;inset:18px;border-radius:14px;}
.qr-overlay .corner{position:absolute;width:36px;height:36px;border:3px solid #7c5cff;}
.qr-overlay .tl{top:0;left:0;border-right:none;border-bottom:none;border-top-left-radius:10px;}
.qr-overlay .tr{top:0;right:0;border-left:none;border-bottom:none;border-top-right-radius:10px;}
.qr-overlay .bl{bottom:0;left:0;border-right:none;border-top:none;border-bottom-left-radius:10px;}
.qr-overlay .br{bottom:0;right:0;border-left:none;border-top:none;border-bottom-right-radius:10px;}
.qr-status{position:absolute;left:16px;bottom:16px;background:rgba(15,18,26,.85);padding:10px 12px;border-radius:12px;color:#cbd5e1;border:1px solid #1f2633;}
.manual-row{display:flex;gap:10px;margin:14px 0 10px;}
.manual-row input{flex:1;border-radius:12px;border:1px solid #1f2633;background:#0b0f17;color:#e7edf3;padding:12px 14px;}
.manual-row input:focus{outline:none;border-color:#7c5cff;box-shadow:0 0 0 2px rgba(124,92,255,.25);}
.result-card{border:1px solid #1f2633;border-radius:14px;padding:14px;background:rgba(255,255,255,.02);margin-top:10px;}
.result-card .result-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px;}
.result-card .result-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;margin:10px 0;}
.result-card .badge{padding:6px 10px;border-radius:10px;font-weight:700;font-size:.9rem;}
.badge--pending{background:rgba(250,204,21,.16);color:#facc15;border:1px solid rgba(250,204,21,.4);}
.badge--paid{background:rgba(74,222,128,.16);color:#86efac;border:1px solid rgba(74,222,128,.35);}
.badge--error{background:rgba(248,113,113,.16);color:#f87171;border:1px solid rgba(248,113,113,.35);}
.walkin-grid{display:grid;grid-template-columns:1.1fr 0.9fr;gap:14px;}
.section-title{font-weight:700;margin:0 0 10px;}
.movie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;}
.movie-card{border:1px solid #1f2633;border-radius:14px;padding:10px;display:grid;grid-template-columns:82px 1fr;gap:10px;align-items:center;background:rgba(255,255,255,.02);cursor:pointer;transition:border-color .15s ease,transform .15s ease,box-shadow .15s ease;}
.movie-card:hover{border-color:rgba(124,92,255,.5);transform:translateY(-1px);box-shadow:0 12px 24px rgba(0,0,0,.25);}
.movie-card.selected{border-color:#7c5cff;box-shadow:0 16px 30px rgba(124,92,255,.25);}
.movie-card__poster{width:82px;height:108px;border-radius:10px;overflow:hidden;background:#0b0f17;border:1px solid #1f2633;display:grid;place-items:center;}
.movie-card__poster img{width:100%;height:100%;object-fit:cover;}
.poster-fallback{font-weight:800;font-size:1.4rem;color:#c4b5fd;}
.movie-card__title{font-weight:800;}
.movie-card__price{color:#c4b5fd;font-weight:700;margin:4px 0;}
.movie-card__meta{color:#94a3b8;font-size:.9rem;}
.showtime-pills{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px;margin-bottom:10px;}
.form-grid--compact{grid-template-columns:repeat(auto-fit,minmax(180px,1fr));}
.form-block{display:flex;flex-direction:column;gap:6px;}
.form-block label{font-weight:700;}
.form-block input,.form-block select{border-radius:12px;border:1px solid #1f2633;background:#0b0f17;color:#e7edf3;padding:12px 14px;}
.chip-row{display:flex;gap:8px;flex-wrap:wrap;}
.chip-row .chip{padding:9px 12px;border-radius:12px;border:1px solid #1f2633;background:rgba(255,255,255,.03);cursor:pointer;transition:border-color .12s ease,background .12s ease;}
.chip-row .chip.active{border-color:#7c5cff;background:rgba(124,92,255,.14);color:#c4b5fd;}
.pill-row{display:flex;gap:8px;flex-wrap:wrap;}
.summary-card{border:1px solid #1f2633;border-radius:14px;padding:12px;background:rgba(255,255,255,.02);margin:8px 0;}
.summary-row{display:flex;justify-content:space-between;margin:6px 0;color:#cbd5e1;}
.summary-row.total{font-size:1.1rem;font-weight:800;}
.action-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:8px;flex-wrap:wrap;}
.inline-message{color:#94a3b8;}
.empty-tile{border:1px dashed rgba(255,255,255,.18);border-radius:12px;padding:16px;margin-bottom:10px;background:rgba(255,255,255,.02);}
.table{width:100%;border:1px solid #1f2633;border-radius:14px;overflow:hidden;}
.table__row{display:grid;grid-template-columns:2fr 1fr 1fr 140px;gap:10px;padding:12px 14px;border-bottom:1px solid #1f2633;align-items:center;}
.table__row:nth-child(odd){background:rgba(255,255,255,.02);}
.table__header{font-weight:800;color:#cbd5e1;}
.status-badge{padding:6px 10px;border-radius:10px;font-weight:700;width:max-content;}
.status-paid{background:rgba(74,222,128,.16);color:#86efac;}
.status-pending{background:rgba(250,204,21,.16);color:#facc15;}
.status-cancelled{background:rgba(248,113,113,.16);color:#f87171;}
.table__skeleton{padding:16px;color:#94a3b8;}
.d-none{display:none;}
@media (max-width:1080px){.panel-grid{grid-template-columns:1fr;}.walkin-grid{grid-template-columns:1fr;}}
@media (max-width:640px){.manual-row{flex-direction:column;}.panel__head{flex-direction:column;}.panel__actions{align-items:flex-start;}.table__row{grid-template-columns:1fr;}}
</style>
@endpush


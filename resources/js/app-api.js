
// API Configuration
const API_BASE = '/api';

// CSRF Token Helper
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

// Minimal state (only for current user session info)
const state = {
  currentUser: null
};

// DOM Elements - Global (kept minimal; nav/account handled by Blade)
const yearEl = document.getElementById("year");

// Auth page handled via Blade forms now

// DOM Elements - Booking Page
const movieSelect = document.getElementById("movieSelect");
const dateSelect = document.getElementById("dateSelect");
const showtimeSelect = document.getElementById("showtimeSelect");
const cinemaSelect = document.getElementById("cinemaSelect");
const paxInputEl = document.getElementById('paxInput');
const seatPriceDisplay = document.getElementById('seatPrice');
const selectedSeatsEl = document.getElementById('selectedSeats');
const totalPriceEl = document.getElementById('totalPrice');
const addToCartBtn = document.getElementById('addToCartBtn');
const authHint = document.getElementById('authHint');
const cartListEl = document.getElementById('cartList');
const goCheckoutBtnEl = document.getElementById('goCheckoutBtn');
const clearCartBtnEl = document.getElementById('clearCartBtn');
const verificationBanner = document.getElementById('verificationBanner');
const resendVerificationBtn = document.getElementById('resendVerificationBtn');
const resendVerificationMsg = document.getElementById('resendVerificationMsg');
const paymentSection = document.getElementById('paymentSection');
const checkoutItemsEl = document.getElementById('checkoutItems');
const checkoutTotalEl = document.getElementById('checkoutTotal');
const placeOrderBtnEl = document.getElementById('placeOrderBtn');

// DOM Elements - Admin Page
const movieForm = document.getElementById("movieForm");
const mvTitle = document.getElementById("mvTitle");
const mvPrice = document.getElementById("mvPrice");
const mvAudience = document.getElementById("mvAudience");
const mvPoster = document.getElementById("mvPoster");
const mvTimes = document.getElementById("mvTimes");
const movieErr = document.getElementById("movieErr");
const adminMovieList = document.getElementById("adminMovieList");
const adminBookingList = document.getElementById("adminBookingList");
const kpiMovies = document.getElementById('kpiMovies');
const kpiBookings = document.getElementById('kpiBookings');
const kpiPaid = document.getElementById('kpiPaid');
const kpiPending = document.getElementById('kpiPending');
const kpiCancelled = document.getElementById('kpiCancelled');

// ---------- API Helper Functions ----------
async function apiCall(endpoint, options = {}) {
  try {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers
    };

    // Add CSRF token for non-GET requests
    if (options.method && options.method !== 'GET') {
      const token = getCsrfToken();
      if (token) {
        headers['X-CSRF-TOKEN'] = token;
      }
    }

    const response = await fetch(API_BASE + endpoint, {
      ...options,
      headers,
      credentials: 'same-origin'
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ error: 'Request failed' }));
      throw new Error(errorData.error || `HTTP ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error('API call failed:', error);
    throw error;
  }
}

// ---------- Mobile Navigation ----------
function setupMobileNav() {
  const toggle = document.querySelector('.mobile-toggle');
  const nav = document.querySelector('.nav');
  const account = document.querySelector('.account');
  
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const isOpen = nav.style.display === 'flex';
      nav.style.display = isOpen ? 'none' : 'flex';
      if (account) account.style.display = isOpen ? 'none' : 'flex';
    });
  }
}

// Authentication is server-rendered; only verification resend remains if needed
function initAuthPage() {}

// ---------- Home Page ----------
function initHomePage() {
  // Movies are already rendered server-side in index.blade.php
  // No JS needed unless we add filtering/search functionality later
}

// ---------- Booking Page ----------
function initBookingPage() {
  const user = window.currentUser || state.currentUser;
  if (verificationBanner && user && !user.email_verified) {
    verificationBanner.style.display = '';
  }

  if (resendVerificationBtn) {
    resendVerificationBtn.addEventListener('click', async () => {
      resendVerificationMsg && (resendVerificationMsg.textContent = '');
      resendVerificationBtn.disabled = true;
      const original = resendVerificationBtn.textContent;
      resendVerificationBtn.textContent = 'Sending...';
      try {
        const data = await apiCall('/auth/email/resend', { method: 'POST' });
        resendVerificationMsg && (resendVerificationMsg.textContent = data.message || 'Verification email sent.');
      } catch (err) {
        resendVerificationMsg && (resendVerificationMsg.textContent = err.message || 'Failed to resend verification email.');
      } finally {
        resendVerificationBtn.disabled = false;
        resendVerificationBtn.textContent = original;
      }
    });
  }
}

// ---------- Admin Page ----------
async function initAdminPage() {
  // Admin data is already passed via Blade, but we still need API for add/edit/delete operations
  
  // Add movie
  if (movieForm) {
    movieForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (movieErr) movieErr.textContent = '';

      const times = mvTimes.value.split('\n').map(t => t.trim()).filter(Boolean);

      try {
        await apiCall('/movies', {
          method: 'POST',
          body: JSON.stringify({
            title: mvTitle.value,
            price: Number(mvPrice.value),
            audience: mvAudience.value,
            poster: mvPoster.value || 'https://via.placeholder.com/300x450',
            showtimes: times
          })
        });

        alert('Movie added successfully!');
        window.location.reload();
      } catch (err) {
        if (movieErr) movieErr.textContent = err.message;
      }
    });
  }

    // Archive/Unarchive handled by server via forms in Blade; remove hard-delete behavior

}

// ---------- Staff Page (QR Scanner + Walk-in Booking) ----------
async function initStaffPage() {
  await Promise.all([
    initQRScanner(),
    initWalkinBooking(),
    initRecentTransactions(),
  ]);
  pingApiHealth();
}

async function pingApiHealth() {
  const pill = document.getElementById('apiHealth');
  if (!pill) return;
  pill.textContent = 'Checking API...';
  pill.className = 'pill pill--muted';
  try {
    await apiCall('/health');
    pill.textContent = 'API online';
    pill.className = 'pill pill--success';
  } catch (err) {
    pill.textContent = 'API unreachable';
    pill.className = 'pill pill--error';
  }
}

async function initQRScanner() {
  const readerEl = document.getElementById('qr-reader');
  const statusChip = document.getElementById('scanStatus');
  const messageEl = document.getElementById('scanMessage');
  const resultCard = document.getElementById('scanResultCard');
  const manualInput = document.getElementById('manualTokenInput');
  const manualBtn = document.getElementById('manualVerifyBtn');
  const startBtn = document.getElementById('scanStartBtn');
  const stopBtn = document.getElementById('scanStopBtn');
  const rescanBtn = document.getElementById('scanRescanBtn');

  if (!readerEl) return;

  let html5QrCode = null;
  let scanning = false;
  let processing = false;
  let lastScanned = '';
  let lastScanAt = 0;

  const setStatus = (text, tone = 'info') => {
    if (statusChip) {
      let cls = 'pill ';
      cls += tone === 'error' ? 'pill--error' : tone === 'success' ? 'pill--success' : 'pill--info';
      statusChip.className = cls;
      statusChip.textContent = text;
    }
    if (messageEl) messageEl.textContent = text;
  };

  const formatPeso = (n) => `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

  async function startCamera(auto = false) {
    if (scanning || processing) return;
    setStatus(auto ? 'Auto-starting camera...' : 'Starting camera...', 'info');
    try {
      const { Html5Qrcode } = await import('html5-qrcode');
      if (!html5QrCode) {
        html5QrCode = new Html5Qrcode('qr-reader');
      }
      await html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 260, height: 220 } },
        onScanSuccess,
        () => {}
      );
      scanning = true;
      setStatus('Scanning...', 'info');
    } catch (err) {
      console.error('Failed to start scanner:', err);
      setStatus('Camera blocked or unavailable', 'error');
      if (resultCard) {
        resultCard.hidden = false;
        resultCard.innerHTML = `<div class="result-head"><strong>Camera error</strong><span class="badge badge--error">Check permissions</span></div><p class="muted">${err.message || 'Allow camera access to scan tickets.'}</p>`;
      }
    }
  }

  async function stopCamera() {
    if (html5QrCode && scanning) {
      try { await html5QrCode.stop(); } catch (_) {}
      scanning = false;
      setStatus('Camera paused', 'muted');
    }
  }

  async function onScanSuccess(decodedText) {
    const now = Date.now();
    if (processing) return;
    if (decodedText === lastScanned && (now - lastScanAt) < 2500) return;
    processing = true;
    lastScanned = decodedText;
    lastScanAt = now;
    await stopCamera();
    await verifyToken(decodedText);
    setTimeout(() => { processing = false; }, 300);
  }

  async function verifyToken(token) {
    if (!token) return;
    setStatus('Verifying token...', 'info');
    if (resultCard) {
      resultCard.hidden = false;
      resultCard.innerHTML = '<div class="muted">Checking booking...</div>';
    }

    try {
      const data = await apiCall('/bookings/scan', {
        method: 'POST',
        body: JSON.stringify({ token })
      });

      const booking = data?.booking || {};
      const isPending = data.pending_payment === true || booking.status === 'Pending';
      const awaitingCheckin = data.awaiting_checkin === true || booking.status === 'Paid';
      const badgeClass = isPending ? 'badge--pending' : 'badge--paid';
      const badgeLabel = isPending ? 'Pending payment' : 'Ready to check in';

      const items = booking.items || [];
      const firstItem = items[0] || {};
      const seats = items.reduce((sum, it) => sum + (Number(it.quantity) || 0), 0);

      const actions = [];
      if (isPending) {
        actions.push('<button type="button" class="btn btn--primary" data-action="accept-cash">Accept cash & check-in</button>');
      } else if (awaitingCheckin) {
        actions.push('<button type="button" class="btn btn--primary" data-action="verify-entry">Verify entry</button>');
      }
      actions.push('<button type="button" class="btn btn--ghost" data-action="rescan">Scan next</button>');

      if (resultCard) {
        resultCard.hidden = false;
        resultCard.innerHTML = `
          <div class="result-head">
            <div>
              <div class="badge ${badgeClass}">${badgeLabel}</div>
              <div class="muted" style="margin-top:6px;">${booking.name || 'Customer'} - ${booking.reference || booking.id || ''}</div>
            </div>
            <strong>${formatPeso(booking.total)}</strong>
          </div>
          <div class="result-meta">
            <div><small class="muted">Movie</small><div>${firstItem.movie_title || '-'}</div></div>
            <div><small class="muted">Session</small><div>${firstItem.time || ''} - ${firstItem.cinema || ''}</div></div>
            <div><small class="muted">Tickets</small><div>${seats || 0} pax</div></div>
            <div><small class="muted">Status</small><div>${booking.status || '-'}</div></div>
          </div>
          <div class="btn-row">${actions.join('')}</div>
        `;
      }

      setStatus(isPending ? 'Pending payment' : 'Valid ticket', isPending ? 'info' : 'success');
      manualInput && (manualInput.value = '');

      resultCard?.querySelector('[data-action="accept-cash"]')?.addEventListener('click', async () => {
        try {
          await apiCall('/bookings/scan', {
            method: 'POST',
            body: JSON.stringify({ token, confirmCash: true, confirmCheckIn: true })
          });
          setStatus('Checked in with cash', 'success');
          resultCard.innerHTML = '<div class="result-head"><strong>Cash accepted</strong><span class="badge badge--paid">Checked in</span></div>';
        } catch (err) {
          setStatus(err.message || 'Failed to accept cash', 'error');
        }
      });

      resultCard?.querySelector('[data-action="verify-entry"]')?.addEventListener('click', async () => {
        try {
          await apiCall('/bookings/scan', {
            method: 'POST',
            body: JSON.stringify({ token, confirmCheckIn: true })
          });
          setStatus('Entry verified', 'success');
          resultCard.innerHTML = '<div class="result-head"><strong>Entry verified</strong><span class="badge badge--paid">Checked in</span></div>';
        } catch (err) {
          setStatus(err.message || 'Failed to verify entry', 'error');
        }
      });

      resultCard?.querySelector('[data-action="rescan"]')?.addEventListener('click', () => {
        setStatus('Scanning...', 'info');
        startCamera();
      });
    } catch (err) {
      setStatus(err.message || 'Scan failed', 'error');
      if (resultCard) {
        resultCard.hidden = false;
        resultCard.innerHTML = `<div class="result-head"><strong>Scan failed</strong><span class="badge badge--error">Error</span></div><p class="muted">${err.message || 'Unable to verify token.'}</p><div class="btn-row"><button type="button" class="btn btn--ghost" data-action="rescan">Try again</button></div>`;
        resultCard.querySelector('[data-action="rescan"]')?.addEventListener('click', () => startCamera());
      }
    }
  }

  manualBtn?.addEventListener('click', () => {
    const token = manualInput?.value.trim();
    if (!token) {
      setStatus('Enter a token to verify', 'error');
      return;
    }
    verifyToken(token);
  });

  manualInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      manualBtn?.click();
    }
  });

  startBtn?.addEventListener('click', () => startCamera());
  rescanBtn?.addEventListener('click', () => startCamera());
  stopBtn?.addEventListener('click', () => stopCamera());

  startCamera(true);
}

async function initWalkinBooking() {
  const wrap = document.getElementById('walkinMode');
  if (!wrap) return;

  const movies = Array.isArray(window.staffMovies) ? window.staffMovies : buildMoviesFromDom();
  const movieGrid = document.getElementById('walkinMovieGrid');
  const showtimeWrap = document.getElementById('showtimeChipWrap');
  const cinemaSelect = document.getElementById('walkinCinemaSelect');
  const customerName = document.getElementById('customerNameInput');
  const paxRegular = document.getElementById('paxRegularInput');
  const paxSenior = document.getElementById('paxSeniorInput');
  const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
  const summaryMovie = document.getElementById('summaryMovie');
  const summarySession = document.getElementById('summarySession');
  const summaryPax = document.getElementById('summaryPax');
  const summaryTotal = document.getElementById('summaryTotal');
  const statusPill = document.getElementById('walkinStatus');
  const messageEl = document.getElementById('walkinMessage');
  const confirmBtn = document.getElementById('createBookingBtn');
  const resetBtn = document.getElementById('resetWalkinBtn');
  const form = document.getElementById('walkinForm');

  let selectedMovie = null;
  let selectedShowtime = null;

  function buildMoviesFromDom() {
    const cards = document.querySelectorAll('.movie-card');
    return Array.from(cards).map((card) => {
      let showtimes = [];
      const raw = card.dataset.showtimes || '[]';
      try { showtimes = JSON.parse(raw); } catch (_) { showtimes = []; }
      return {
        id: card.dataset.movieId,
        title: card.dataset.title,
        price: Number(card.dataset.price || 0),
        audience: card.dataset.audience || '',
        showtimes,
      };
    });
  }

  const setStatus = (text, tone = 'muted') => {
    if (statusPill) {
      let cls = 'pill ';
      cls += tone === 'error' ? 'pill--error' : tone === 'success' ? 'pill--success' : 'pill--muted';
      statusPill.className = cls;
      statusPill.textContent = text;
    }
    if (messageEl) messageEl.textContent = text;
  };

  const formatPeso = (n) => `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

  function renderShowtimes(movie) {
    showtimeWrap.innerHTML = '';
    const sts = Array.isArray(movie?.showtimes) ? movie.showtimes : [];
    if (!sts.length) {
      showtimeWrap.textContent = 'No showtimes available for today.';
      return;
    }
    if (!selectedShowtime) {
      selectedShowtime = sts[0];
    }
    sts.forEach((st) => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'chip';
      chip.dataset.showtimeId = st.id;
      chip.dataset.showtimeLabel = st.label;
      chip.textContent = st.label;
      if (selectedShowtime && selectedShowtime.id === st.id) {
        chip.classList.add('active');
      }
      chip.addEventListener('click', () => {
        selectedShowtime = st;
        renderShowtimes(movie);
        updateSummary();
      });
      showtimeWrap.appendChild(chip);
    });
  }

  function selectMovie(cardEl, movieId) {
    selectedMovie = movies.find((m) => String(m.id) === String(movieId)) || null;
    selectedShowtime = null;
    document.querySelectorAll('.movie-card').forEach((c) => c.classList.remove('selected'));
    cardEl.classList.add('selected');
    renderShowtimes(selectedMovie);
    updateSummary();
    setStatus('Movie selected. Choose showtime & cinema.', 'info');
  }

  movieGrid?.querySelectorAll('.movie-card').forEach((card) => {
    card.addEventListener('click', () => selectMovie(card, card.dataset.movieId));
  });

  cinemaSelect?.addEventListener('change', () => updateSummary());
  customerName?.addEventListener('input', () => updateSummary());
  paxRegular?.addEventListener('input', () => updateSummary());
  paxSenior?.addEventListener('input', () => updateSummary());
  paymentRadios.forEach((r) => r.addEventListener('change', () => updateSummary()));

  function updateSummary() {

    const reg = Math.max(0, Number(paxRegular?.value || 0));
    const sen = Math.max(0, Number(paxSenior?.value || 0));
    const totalPax = reg + sen;
    let errorMsg = '';
    let price = 0;
    if (selectedMovie) price = Number(selectedMovie.price || 0);
    const total = (reg * price) + (sen * price * 0.8);
    summaryMovie.textContent = selectedMovie ? selectedMovie.title : 'Not selected';
    const sessionStr = selectedShowtime && cinemaSelect?.value
      ? `${selectedShowtime.label} - ${cinemaSelect.value}`
      : 'Pick showtime & cinema';
    summarySession.textContent = sessionStr;
    summaryPax.textContent = `${reg} Regular / ${sen} Senior`;
    summaryTotal.textContent = formatPeso(total);

    // Validation
    if (!selectedMovie) errorMsg = 'Select a movie.';
    else if (!selectedShowtime) errorMsg = 'Select a showtime.';
    else if (!cinemaSelect?.value) errorMsg = 'Select a cinema.';
    else if (totalPax < 1) errorMsg = 'Total pax must be at least 1.';
    else if (sen > 0 && document.getElementById('seniorCheck') && !document.getElementById('seniorCheck').checked) errorMsg = 'Senior ID confirmation required.';

    if (errorMsg) {
      setStatus(errorMsg, 'error');
      confirmBtn.disabled = true;
    } else {
      setStatus('Ready to confirm booking', 'success');
      confirmBtn.disabled = false;
    }

    if (form) {
      const payMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
      form.querySelector('#formMovieId').value = selectedMovie?.id || '';
      form.querySelector('#formShowtimeId').value = selectedShowtime?.id || '';
      form.querySelector('#formCinema').value = cinemaSelect?.value || '';
      form.querySelector('#formQuantity').value = totalPax;
      form.querySelector('#formSeniorQuantity').value = sen;
      form.querySelector('#formPaymentMethod').value = payMethod;
      form.querySelector('#formCustomerName').value = customerName?.value?.trim() || '';
    }
  }

  async function submitWalkinBooking() {
    if (!form) return;
    const formData = new FormData(form);
    setStatus('Placing booking...', 'info');
    confirmBtn.disabled = true;
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(data.error || 'Failed to place booking');
      }
      setStatus('Booking confirmed', 'success');
      if (messageEl) messageEl.textContent = 'Booking confirmed. Ticket ready to print.';
      await initRecentTransactions(true);
      resetWalkin();
    } catch (err) {
      setStatus(err.message || 'Booking failed', 'error');
      confirmBtn.disabled = false;
    }
  }

  function resetWalkin() {
    selectedMovie = null;
    selectedShowtime = null;
    document.querySelectorAll('.movie-card').forEach((c) => c.classList.remove('selected'));
    showtimeWrap.innerHTML = 'Select a movie to load today\'s showtimes';
    cinemaSelect && (cinemaSelect.value = '');
    customerName && (customerName.value = '');
    paxRegular && (paxRegular.value = 1);
    paxSenior && (paxSenior.value = 0);
    paymentRadios.forEach((r, idx) => { r.checked = idx === 0; });
    updateSummary();
    setStatus('Waiting for selection', 'muted');
    confirmBtn.disabled = true;
  }

  confirmBtn?.addEventListener('click', submitWalkinBooking);
  resetBtn?.addEventListener('click', resetWalkin);
  updateSummary();
}

async function initRecentTransactions(refreshOnly = false) {
  const table = document.getElementById('transactionsTable');
  const statusEl = document.getElementById('transactionsStatus');
  if (!table) return;

  statusEl && (statusEl.textContent = 'Loading...');
  try {
    const data = await apiCall('/bookings');
    const items = (Array.isArray(data) ? data : []).slice(0, 8);
    if (!items.length) {
      table.innerHTML = '<div class="table__skeleton">No transactions yet.</div>';
      statusEl && (statusEl.textContent = 'Idle');
      return;
    }
    table.innerHTML = `
      <div class="table__row table__header">
        <div>Booking</div><div>Total</div><div>Status</div><div>Actions</div>
      </div>
    `;
    items.forEach((bk, idx) => {
      const item = (bk.items || [])[0] || {};
      const dateStr = bk.created_at ? new Date(bk.created_at).toLocaleString() : '';
      const statusClass = bk.status === 'Paid' ? 'status-paid' : bk.status === 'Cancelled' ? 'status-cancelled' : 'status-pending';
      const row = document.createElement('div');
      row.className = 'table__row';
      row.innerHTML = `
        <div>
          <div><strong>${item.movie_title || 'Booking'}</strong></div>
          <div class="muted" style="font-size:.9rem;">${dateStr}</div>
          <div class="muted" style="font-size:.9rem;">${item.time || ''} - ${item.cinema || ''}</div>
        </div>
        <div>${(bk.total !== undefined && bk.total !== null) ? `₱${Number(bk.total).toLocaleString('en-PH', {minimumFractionDigits:2})}` : '-'}</div>
        <div><span class="status-badge ${statusClass}">${bk.status || 'Pending'}</span></div>
        <div><button type="button" class="btn btn--outline" data-print-index="${idx}">Print ticket</button></div>
      `;
      table.appendChild(row);
    });
    table.querySelectorAll('[data-print-index]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const idx = Number(btn.dataset.printIndex);
        const bk = items[idx];
        openPrintSlip(bk);
      });
    });
    statusEl && (statusEl.textContent = refreshOnly ? 'Updated' : 'Live');
  } catch (err) {
    console.warn('Transactions load failed', err);
    table.innerHTML = `<div class="table__skeleton">Failed to load transactions: ${err.message || ''}</div>`;
    statusEl && (statusEl.textContent = 'Error');
  }
}

function openPrintSlip(booking) {
  const win = window.open('', '_blank', 'width=420,height=640');
  if (!win) {
    alert('Allow pop-ups to print ticket.');
    return;
  }
  const item = (booking?.items || [])[0] || {};
  const seats = (booking?.items || []).reduce((sum, it) => sum + (Number(it.quantity) || 0), 0);
  win.document.write(`
    <html><head><title>Ticket ${booking?.id || ''}</title></head>
    <body style="font-family:Arial,Helvetica,sans-serif;padding:18px;">
      <h2 style="margin:0 0 6px;">XentroCine Ticket</h2>
      <p style="margin:0 0 12px;color:#444;">${new Date(booking?.created_at || Date.now()).toLocaleString()}</p>
      <hr style="margin:12px 0;">
      <p style="margin:6px 0;"><strong>Movie:</strong> ${item.movie_title || '-'}</p>
      <p style="margin:6px 0;"><strong>Session:</strong> ${item.time || ''} - ${item.cinema || ''}</p>
      <p style="margin:6px 0;"><strong>Tickets:</strong> ${seats} pax</p>
      <p style="margin:6px 0;"><strong>Total:</strong> ₱${Number(booking?.total || 0).toLocaleString('en-PH',{minimumFractionDigits:2})}</p>
      <p style="margin:6px 0;"><strong>Status:</strong> ${booking?.status || 'Pending'}</p>
      <p style="margin:6px 0;"><strong>Token:</strong> ${booking?.et_qr_token || ''}</p>
      <hr style="margin:12px 0;">
      <p style="margin:0;color:#555;">Thank you for choosing XentroCine.</p>
      <script>window.print();<\/script>
    </body></html>
  `);
  win.document.close();
}

// ---------- QR Test Page ----------
function initQRTestPage() {
  const bookingSelect = document.getElementById('bookingSelect');
  const qrDisplay = document.getElementById('qrDisplay');
  const tokenDisplay = document.getElementById('tokenDisplay');

  if (!bookingSelect) return;

  const bookings = window.initialData?.bookings || [];

  bookingSelect.addEventListener('change', () => {
    const bookingId = bookingSelect.value;
    const booking = bookings.find(b => b.id === bookingId);

    if (booking && qrDisplay && tokenDisplay) {
      // Generate QR code using qrcode library
      QRCode.toCanvas(qrDisplay, booking.et_qr_token, { width: 300 }, (error) => {
        if (error) console.error(error);
      });
      tokenDisplay.textContent = booking.et_qr_token;
    }
  });
}

// ---------- Global Initialization ----------
async function initGlobal() {
  setupMobileNav();
  // Sync minimal user state from Blade
  state.currentUser = window.currentUser || null;

  // Set current year in footer
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // Page-specific initialization
  const page = document.body.getAttribute('data-page');
  
  switch (page) {
    case 'home':
      initHomePage();
      break;
    case 'booking':
      initBookingPage();
      break;
    case 'login':
      initAuthPage();
      break;
    case 'admin':
      await initAdminPage();
      break;
    case 'staff':
      await initStaffPage();
      break;
    case 'qr-test':
      initQRTestPage();
      break;
  }
}

// Start when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initGlobal);
} else {
  initGlobal();
}

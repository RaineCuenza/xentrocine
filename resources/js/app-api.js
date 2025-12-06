
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
function initBookingPage() { /* Booking now uses standard Blade forms */ }

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

  // Delete movie
  if (adminMovieList) {
    adminMovieList.addEventListener('click', async (e) => {
      if (e.target.classList.contains('admin-item__delete')) {
        const movieId = e.target.getAttribute('data-id');
        if (!confirm('Delete this movie?')) return;

        try {
          await apiCall(`/movies/${movieId}`, { method: 'DELETE' });
          window.location.reload();
        } catch (err) {
          alert(err.message || 'Failed to delete movie');
        }
      }
    });
  }

}

// ---------- Staff Page (QR Scanner + Walk-in Booking) ----------
async function initStaffPage() {
  // QR scanner and walk-in booking still require JS
  await initQRScanner();
  await initWalkinBooking();
}

async function initQRScanner() {
  const scannerContainer = document.getElementById('qr-reader');
  const scanResult = document.getElementById('scanResult');
  const manualTokenInput = document.getElementById('manualToken');
  const verifyManualBtn = document.getElementById('manualScanBtn');
  const scannerMode = document.getElementById('scannerMode');

  if (!scannerContainer) return;

  let html5QrCode = null;
  let scanning = false;
  let processing = false;
  let lastScanned = '';
  let lastScanAt = 0;

  async function ensureScannerStarted() {
    if (scanning) return;
    try {
      // Lazy-load the QR library only when needed
      const { Html5Qrcode } = await import('html5-qrcode');
      html5QrCode = new Html5Qrcode('qr-reader');
      await html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 250 },
        onScanSuccess,
        () => {}
      );
      scanning = true;
    } catch (err) {
      console.error('Failed to start scanner:', err);
      // Don't alert immediately; user might not have granted camera permissions yet
    }
  }

  async function stopScanner() {
    if (html5QrCode && scanning) {
      try { await html5QrCode.stop(); } catch (_) {}
      scanning = false;
    }
  }

  async function onScanSuccess(decodedText) {
    const now = Date.now();
    // Ignore duplicate reads within 2.5s or while processing
    if (processing) return;
    if (decodedText === lastScanned && (now - lastScanAt) < 2500) return;
    processing = true;
    lastScanned = decodedText;
    lastScanAt = now;
    try {
      await verifyToken(decodedText);
    } finally {
      // Pause camera to prevent repeated scans of the same code
      await stopScanner();
      // allow another scan after UI action
      setTimeout(() => { processing = false; }, 250);
    }
  }

  // Manual token verification
  if (verifyManualBtn) {
    verifyManualBtn.addEventListener('click', async () => {
      const token = manualTokenInput?.value.trim();
      if (!token) {
        alert('Enter a token');
        return;
      }
      await verifyToken(token);
    });
  }

  // Support Enter key for hardware scanners
  if (manualTokenInput) {
    manualTokenInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        verifyManualBtn?.click();
      }
    });
  }

  async function verifyToken(token) {
    if (!scanResult) return;

    try {
      const data = await apiCall('/bookings/scan', {
        method: 'POST',
        body: JSON.stringify({ token })
      });

      const isPending = data.pending_payment === true || data?.booking?.status === 'Pending';
      const awaitingCheckin = data.awaiting_checkin === true || data?.booking?.status === 'Paid';

      scanResult.className = 'scan-result ' + (isPending ? 'pending' : 'success');
      scanResult.style.display = 'block';

      const detailsHtml = `
        <div class="result-details">
          <div class="detail-row"><span class="detail-label">Name</span><span>${data.booking?.name || '-'}</span></div>
          <div class="detail-row"><span class="detail-label">Total</span><span>₱${data.booking?.total ?? '-'}</span></div>
          <div class="detail-row"><span class="detail-label">Status</span><span>${data.booking?.status ?? '-'}</span></div>
        </div>`;

      let actionsHtml = '';
      if (isPending) {
        actionsHtml = '<button id="acceptCashBtn" class="btn-primary">Accept Cash & Check-in</button>';
      } else if (awaitingCheckin) {
        actionsHtml = '<button id="verifyEntryBtn" class="btn-primary">Verify Entry</button>';
      }

      scanResult.innerHTML = `
        <div class="result-header">${isPending ? '⚠ Pending Payment' : '✓ Valid Ticket'}</div>
        ${detailsHtml}
        <div class="result-actions">${actionsHtml || '<button id="scanNextBtn" class="btn-secondary">Scan Next</button>'}</div>
      `;

      // Clear input for next scan
      if (manualTokenInput) manualTokenInput.value = '';

      const acceptCashBtn = document.getElementById('acceptCashBtn');
      if (acceptCashBtn) {
        acceptCashBtn.addEventListener('click', async () => {
          try {
            await apiCall('/bookings/scan', {
              method: 'POST',
              // Accept cash and check-in in one step
              body: JSON.stringify({ token, confirmCash: true, confirmCheckIn: true })
            });
            scanResult.className = 'scan-result success';
            scanResult.innerHTML = '<div class="result-header">✓ Checked In Successfully</div>';
          } catch (err) {
            alert(err.message || 'Failed to process payment');
          }
        });
      }

      const verifyEntryBtn = document.getElementById('verifyEntryBtn');
      if (verifyEntryBtn) {
        verifyEntryBtn.addEventListener('click', async () => {
          try {
            await apiCall('/bookings/scan', {
              method: 'POST',
              body: JSON.stringify({ token, confirmCheckIn: true })
            });
            scanResult.className = 'scan-result success';
            scanResult.innerHTML = '<div class="result-header">✓ Check-in Verified</div><div class="result-actions"><button id="scanNextBtn" class="btn-secondary">Scan Next</button></div>';
          } catch (err) {
            alert(err.message || 'Failed to check-in');
          }
        });
      }

      const scanNextBtn = document.getElementById('scanNextBtn');
      if (scanNextBtn) {
        scanNextBtn.addEventListener('click', () => {
          ensureScannerStarted();
        });
      }
    } catch (err) {
      scanResult.className = 'scan-result error';
      scanResult.style.display = 'block';
      scanResult.innerHTML = `<div class="result-header">✗ ${err.message}</div>`;
      // Allow scanning again after error message is shown
      const btn = document.createElement('button');
      btn.textContent = 'Scan Next';
      btn.className = 'btn-secondary';
      btn.addEventListener('click', () => ensureScannerStarted());
      const actions = document.createElement('div');
      actions.className = 'result-actions';
      actions.appendChild(btn);
      scanResult.appendChild(actions);
    }
  }

  // Desktop: start camera immediately since both panels are visible
  // Mobile: start only when scanner section is in view or hash points to it
  function shouldStartScanner() {
    const isMobile = window.innerWidth < 1024;
    if (!isMobile) return true; // Desktop: always show scanner
    // Mobile: start if hash points to scanner or no hash (scanner is first panel)
    const hash = location.hash.toLowerCase();
    return !hash || hash.includes('scanner');
  }

  function handleVisibility() {
    if (shouldStartScanner()) {
      ensureScannerStarted();
    } else {
      stopScanner();
    }
  }

  handleVisibility();
  window.addEventListener('hashchange', handleVisibility);
  window.addEventListener('resize', handleVisibility);
}

async function initWalkinBooking() {
  const walkinMode = document.getElementById('walkinMode');
  if (!walkinMode) return;

  const movieGrid = document.getElementById('walkinMovieGrid');
  const dateInput = document.getElementById('walkinDate');
  const showtimeSelect = document.getElementById('walkinShowtime');
  const cinemaSelect = document.getElementById('walkinCinema');
  const proceedBtn = document.getElementById('proceedToSeatsBtn');
  const seatStep = document.getElementById('seatSelectionStep');
  const dateTimeCinemaStep = document.getElementById('dateTimeCinemaStep');
  const selectedMovieInfo = document.getElementById('selectedMovieInfo');
  const paxInput = document.getElementById('walkinPax');
  const seatsCount = document.getElementById('selectedSeatsCount');
  const totalAmount = document.getElementById('totalAmount');
  const backBtn = document.getElementById('backToMovieBtn');
  const confirmBtn = document.getElementById('confirmWalkinBookingBtn');

  let movies = [];
  let chosenMovie = null;

  function formatCurrency(n){ return `₱${Number(n).toFixed(2)}`; }

  // Load movies
  try {
    movies = await apiCall('/movies');
  } catch (e) {
    console.error(e);
    return;
  }

  // Render movie cards
  if (movieGrid) {
    movieGrid.innerHTML = '';
    movies.forEach(m => {
      const card = document.createElement('div');
      card.className = 'movie-card-small';
      card.innerHTML = `
        <div class="title"><h4>${m.title}</h4></div>
        <div class="price">${formatCurrency(m.price)}</div>
      `;
      card.addEventListener('click', () => selectMovie(m, card));
      movieGrid.appendChild(card);
    });
  }

  function selectMovie(m, cardEl){
    chosenMovie = m;
    // highlight selected
    document.querySelectorAll('.movie-card-small').forEach(c => c.classList.remove('selected'));
    cardEl.classList.add('selected');

    // populate showtimes
    const times = (m.showtimes || []).map(st => typeof st === 'object' ? st.showtime : st);
    showtimeSelect.innerHTML = '<option value="">Select time</option>';
    times.forEach(t => {
      const opt = document.createElement('option'); opt.value = t; opt.textContent = t; showtimeSelect.appendChild(opt);
    });

    dateTimeCinemaStep.style.display = 'block';
  }

  if (proceedBtn) {
    proceedBtn.addEventListener('click', () => {
      if (!chosenMovie || !dateInput.value || !showtimeSelect.value || !cinemaSelect.value){
        alert('Please complete date, time, and cinema');
        return;
        }
      seatStep.style.display = 'block';
      selectedMovieInfo.textContent = `${chosenMovie.title} — ${dateInput.value} ${showtimeSelect.value} • ${cinemaSelect.value}`;
      updateTotals();
      seatStep.scrollIntoView({ behavior: 'smooth' });
    });
  }

  function updateTotals(){
    const pax = Number(paxInput.value || 1);
    const categorySel = document.getElementById('walkinCategory');
    const category = categorySel ? categorySel.value : 'regular';
    seatsCount.textContent = pax;
    let total = pax * (chosenMovie?.price || 0);
    if (category === 'senior' || category === 'pwd') {
      total = total * 0.8; // 20% discount
    }
    totalAmount.textContent = formatCurrency(total);
  }
  paxInput?.addEventListener('input', updateTotals);
  document.getElementById('walkinCategory')?.addEventListener('change', updateTotals);

  backBtn?.addEventListener('click', () => {
    seatStep.style.display = 'none';
  });

  confirmBtn?.addEventListener('click', async () => {
    if (!chosenMovie) return;
    const name = document.getElementById('customerName')?.value.trim();
    const email = document.getElementById('customerEmail')?.value.trim();
    const phone = document.getElementById('customerPhone')?.value.trim();
    const paymentMethod = (document.querySelector('input[name="paymentMethod"]:checked')?.value) || 'cash';
    const quantity = Number(paxInput.value || 1);
  const categorySel = document.getElementById('walkinCategory');
  const payCategory = categorySel ? categorySel.value : 'regular';

    const payload = {
      paymentMethod,
      customer: { name, email, phone },
      items: [{
        movieId: chosenMovie.id,
        title: chosenMovie.title,
        date: dateInput.value,
        time: showtimeSelect.value,
        cinema: cinemaSelect.value,
        quantity,
        seatPrice: Number(chosenMovie.price),
        payCategory
      }]
    };

    try {
      await apiCall('/bookings', { method: 'POST', body: JSON.stringify(payload) });
      alert('Walk-in booking created.');
      location.hash = '#scannerMode';
      location.reload();
    } catch (err) {
      alert(err.message || 'Booking failed');
    }
  });
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

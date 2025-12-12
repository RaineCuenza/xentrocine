
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

// ---------- Notification System ----------
function showNotification(message, type = 'info') {
  // Remove any existing notifications
  const existing = document.querySelector('.toast-notification');
  if (existing) existing.remove();
  
  const toast = document.createElement('div');
  toast.className = `toast-notification toast-notification--${type}`;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'polite');
  
  const icon = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  }[type] || 'ℹ';
  
  toast.innerHTML = `
    <span class="toast-notification__icon" aria-hidden="true">${icon}</span>
    <span class="toast-notification__message">${message}</span>
    <button class="toast-notification__close" aria-label="Close notification">×</button>
  `;
  
  document.body.appendChild(toast);
  
  // Auto dismiss after 5 seconds
  const dismissTimer = setTimeout(() => {
    dismissToast(toast);
  }, 5000);
  
  // Manual dismiss
  const closeBtn = toast.querySelector('.toast-notification__close');
  closeBtn.addEventListener('click', () => {
    clearTimeout(dismissTimer);
    dismissToast(toast);
  });
  
  // Animate in
  setTimeout(() => toast.classList.add('toast-notification--visible'), 10);
}

function dismissToast(toast) {
  toast.classList.remove('toast-notification--visible');
  setTimeout(() => toast.remove(), 300);
}

// ---------- Loading State Helper ----------
function setLoading(button, isLoading) {
  if (!button) return;
  
  if (isLoading) {
    button.disabled = true;
    button.setAttribute('data-original-text', button.textContent);
    button.innerHTML = '<span class="spinner" aria-hidden="true"></span> Loading...';
  } else {
    button.disabled = false;
    button.textContent = button.getAttribute('data-original-text') || button.textContent;
    button.removeAttribute('data-original-text');
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
  // Setup movie search functionality
  const searchInput = document.getElementById('movieSearch');
  const movieSections = document.querySelectorAll('[data-filter-section]');
  
  if (searchInput && movieSections.length > 0) {
    // Debounce search input
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        filterMovies(e.target.value.toLowerCase().trim());
      }, 300);
    });
  }
  
  function filterMovies(searchTerm) {
    movieSections.forEach(section => {
      const movieRow = section.querySelector('[data-movie-row]');
      const emptyMessage = section.querySelector('[data-empty-message]');
      const movieCards = section.querySelectorAll('[data-movie-card]');
      
      if (!movieRow || !movieCards.length) return;
      
      let visibleCount = 0;
      
      movieCards.forEach(card => {
        const title = card.getAttribute('data-title') || '';
        const matches = !searchTerm || title.includes(searchTerm);
        
        card.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
      });
      
      // Show/hide empty message
      if (emptyMessage) {
        if (visibleCount === 0 && searchTerm) {
          emptyMessage.hidden = false;
        } else {
          emptyMessage.hidden = true;
        }
      }
      
      // Hide entire section if no matches
      if (visibleCount === 0 && searchTerm) {
        section.style.display = 'none';
      } else {
        section.style.display = '';
      }
    });
  }
}

// ---------- Booking Page ----------
function initBookingPage() { /* Booking now uses standard Blade forms */ }

// ---------- Admin Page ----------
async function initAdminPage() {
  // Admin data is already passed via Blade, but we still need API for add/edit/delete operations
  
  // Add movie
  if (movieForm) {
    const submitBtn = movieForm.querySelector('button[type="submit"]');
    
    movieForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (movieErr) movieErr.textContent = '';

      const times = mvTimes.value.split('\n').map(t => t.trim()).filter(Boolean);

      try {
        setLoading(submitBtn, true);
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

        showNotification('Movie added successfully!', 'success');
        
        // Wait a bit before reload to let user see success message
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } catch (err) {
        if (movieErr) movieErr.textContent = err.message;
        showNotification(err.message || 'Failed to add movie', 'error');
        setLoading(submitBtn, false);
      }
    });
  }

    // Archive/Unarchive handled by server via forms in Blade; remove hard-delete behavior

}

// ---------- Staff Page (QR Scanner + Walk-in Booking) ----------
async function initStaffPage() {
  // QR scanner and walk-in booking still require JS
  await initQRScanner();
  await initWalkinBooking();

  // Real-time showing list (poll every 30s)
  initRealtimeShowing();
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

      const hasReceipt = data?.booking?.gcash_receipt_url && data?.booking?.payment_method === 'digital';
      const detailsHtml = `
        <div class="result-details">
          <div class="detail-row"><span class="detail-label">Name</span><span>${data.booking?.name || '-'}</span></div>
          <div class="detail-row"><span class="detail-label">Total</span><span>₱${data.booking?.total ?? '-'}</span></div>
          <div class="detail-row"><span class="detail-label">Status</span><span>${data.booking?.status ?? '-'}</span></div>
          ${hasReceipt ? `<div class="detail-row"><span class="detail-label">GCash</span><span><a href="${data.booking.gcash_receipt_url}" target="_blank">View receipt</a></span></div>` : ''}
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

async function initRealtimeShowing() {
  const container = document.getElementById('walkinMovieGrid');
  if (!container) return;

  async function refresh() {
    try {
      const showing = await apiCall('/movies/showing');
      // Render a simple banner/list above the grid
      let banner = document.getElementById('realtimeShowingBanner');
      if (!banner) {
        banner = document.createElement('div');
        banner.id = 'realtimeShowingBanner';
        banner.className = 'muted';
        banner.style.marginBottom = '10px';
        container.parentElement.insertBefore(banner, container);
      }
      if (!showing.length) {
        banner.textContent = 'No movies are currently showing.';
        return;
      }
      const items = showing.map(s => `${s.title} — started ${s.showtime}`).join(' • ');
      banner.textContent = `Now Showing: ${items}`;
    } catch (err) {
      // Fail silently
      console.warn('Realtime showing fetch failed:', err.message);
    }
  }

  await refresh();
  setInterval(refresh, 30000);
}

async function initWalkinBooking() {
  const walkinMode = document.getElementById('walkinMode');
  if (!walkinMode) return;

  const movieGrid = document.getElementById('walkinMovieGrid');
  // Date removed; default to today
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
    const times = (m.showtimes || []);
    showtimeSelect.innerHTML = '<option value="">Select time</option>';
    times.forEach(t => {
      const rangeStr = String(t);
      const start = rangeStr.split('–')[0]?.trim() || rangeStr;
      const opt = document.createElement('option');
      opt.value = start;
      opt.textContent = rangeStr;
      showtimeSelect.appendChild(opt);
    });

    dateTimeCinemaStep.style.display = 'block';
  }

  if (proceedBtn) {
    proceedBtn.addEventListener('click', () => {
      if (!chosenMovie || !showtimeSelect.value || !cinemaSelect.value){
        alert('Please complete time and cinema');
        return;
        }
      seatStep.style.display = 'block';
      const todayStr = new Date().toISOString().slice(0,10);
      selectedMovieInfo.textContent = `${chosenMovie.title} — ${todayStr} ${showtimeSelect.value} • ${cinemaSelect.value}`;
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
    // Customer information removed for staff flow
    const paymentMethod = (document.querySelector('input[name="paymentMethod"]:checked')?.value) || 'cash';
    const gcashRef = document.getElementById('gcashRef')?.value.trim();
    const quantity = Number(paxInput.value || 1);
  const categorySel = document.getElementById('walkinCategory');
  const payCategory = categorySel ? categorySel.value : 'regular';

    // Require GCash reference when Digital
    if (paymentMethod === 'digital' && !gcashRef) {
      alert('Please enter the GCash reference number.');
      return;
    }

    const todayStr = new Date().toISOString().slice(0,10);
    const payload = {
      paymentMethod,
      reference: gcashRef || '',
      items: [{
        movieId: chosenMovie.id,
        title: chosenMovie.title,
        date: todayStr,
        time: showtimeSelect.value,
        cinema: cinemaSelect.value,
        quantity,
        seatPrice: Number(chosenMovie.price),
        payCategory
      }]
    };

    try {
      setLoading(confirmBookingBtn, true);
      await apiCall('/bookings', { method: 'POST', body: JSON.stringify(payload) });
      showNotification('Walk-in booking created successfully!', 'success');
      
      // Wait a bit before redirect to let user see the success message
      setTimeout(() => {
        location.hash = '#scannerMode';
        location.reload();
      }, 1500);
    } catch (err) {
      showNotification(err.message || 'Booking failed', 'error');
      setLoading(confirmBookingBtn, false);
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

// ---------- Email Verification Handler ----------
function setupEmailVerificationHandler() {
  const resendBtn = document.getElementById('resendVerificationBtn');
  
  if (resendBtn) {
    resendBtn.addEventListener('click', async () => {
      setLoading(resendBtn, true);
      
      try {
        await apiCall('/auth/email/resend', { method: 'POST' });
        showNotification('Verification email sent! Please check your inbox.', 'success');
      } catch (err) {
        showNotification(err.message || 'Failed to send verification email', 'error');
      } finally {
        setLoading(resendBtn, false);
      }
    });
  }
}

// ---------- Global Initialization ----------
async function initGlobal() {
  setupMobileNav();
  setupEmailVerificationHandler();

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

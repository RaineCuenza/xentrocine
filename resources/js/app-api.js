/* =========================
   XentroCine — Movie Booking
   Laravel Backend Version
   Pure JS (no frameworks)
========================= */

// API Configuration - Updated for Laravel
const API_BASE = '/api';

// CSRF Token Helper
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

// State management
const state = {
  currentUser: null,
  movies: [],
  seatRows: 9,
  seatCols: 14,
  aisleAfter: 7,
  cart: [],
  bookings: []
};

// DOM Elements
const yearEl = document.getElementById("year");
const topRatedEl = document.getElementById('topRated');
const mostPopularEl = document.getElementById('mostPopular');
const allMoviesEl = document.getElementById('allMovies');
const movieSelect = document.getElementById("movieSelect");
const dateSelect = document.getElementById("dateSelect");
const showtimeSelect = document.getElementById("showtimeSelect");
const cinemaSelect = document.getElementById("cinemaSelect");
const authHint = document.getElementById("authHint");

const currentUserEl = document.getElementById("currentUser");
const loginBtn = document.getElementById("loginBtn");
const logoutBtn = document.getElementById("logoutBtn");
const adminNavBtn = document.querySelector(".nav__admin");

const tabs = [...document.querySelectorAll(".tab")];
const loginForm = document.getElementById("loginForm");
const loginEmail = document.getElementById("loginEmail");
const loginPassword = document.getElementById("loginPassword");
const loginErr = document.getElementById("loginErr");

const signupForm = document.getElementById("signupForm");
const suName = document.getElementById("suName");
const suEmail = document.getElementById("suEmail");
const suPassword = document.getElementById("suPassword");
const signupErr = document.getElementById("signupErr");

const movieForm = document.getElementById("movieForm");
const mvTitle = document.getElementById("mvTitle");
const mvPrice = document.getElementById("mvPrice");
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

const bookingListEl = document.getElementById('bookingList');
const seatMapEl = document.getElementById('seatMap');
const seatPriceDisplay = document.getElementById('seatPrice');
const selectedSeatsEl = document.getElementById('selectedSeats');
const totalPriceEl = document.getElementById('totalPrice');
const addToCartBtn = document.getElementById('addToCartBtn');
const cartListEl = document.getElementById('cartList');
const goCheckoutBtnEl = document.getElementById('goCheckoutBtn');
const clearCartBtnEl = document.getElementById('clearCartBtn');
const paymentSection = document.getElementById('paymentSection');
const checkoutItemsEl = document.getElementById('checkoutItems');
const checkoutTotalEl = document.getElementById('checkoutTotal');
const placeOrderBtnEl = document.getElementById('placeOrderBtn');

const verificationBanner = document.getElementById('verificationBanner');
const resendVerificationBtn = document.getElementById('resendVerificationBtn');

const legendAvailableEl = document.getElementById('legendAvailable');
const legendSelectedEl = document.getElementById('legendSelected');
const legendReservedEl = document.getElementById('legendReserved');

const cartItemTpl = document.getElementById("cartItemTpl");
const summaryItemTpl = document.getElementById("summaryItemTpl");

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
      headers['X-CSRF-TOKEN'] = getCsrfToken();
    }

    const response = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      credentials: 'same-origin',
      headers
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || 'API request failed');
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// ---------- Utils ----------
const peso = n => (Math.round(n * 100) / 100).toLocaleString("en-PH", { minimumFractionDigits: 0 });

function sessionId(movieId, date, time, cinema) {
  return `${movieId}|${date}|${time}|${cinema}`;
}

function seatKey(r, c) {
  return `${r}-${c}`;
}

// ---------- Session Management ----------
async function checkSession() {
  try {
    const data = await apiCall('/auth/session');
    state.currentUser = data.user;
    updateAccountUI();
  } catch (error) {
    console.error('Session check failed:', error);
    state.currentUser = null;
    updateAccountUI();
  }
}

function updateAccountUI() {
  if (currentUserEl) currentUserEl.textContent = state.currentUser ? state.currentUser.name : 'Guest';
  if (loginBtn) loginBtn.style.display = state.currentUser ? 'none' : '';
  if (logoutBtn) logoutBtn.style.display = state.currentUser ? '' : 'none';
  if (adminNavBtn) {
    adminNavBtn.style.display = (state.currentUser && state.currentUser.role === 'admin') ? '' : 'none';
  }
  
  // Show/hide verification banner
  if (verificationBanner && state.currentUser) {
    const needsVerification = !state.currentUser.email_verified;
    verificationBanner.style.display = needsVerification ? '' : 'none';
  } else if (verificationBanner) {
    verificationBanner.style.display = 'none';
  }
}

// ---------- Auth Page ----------
function initAuthPage() {
  // Tab switching
  if (tabs && tabs.length) {
    tabs.forEach(t => t.addEventListener('click', (e) => {
      const tab = e.currentTarget;
      const target = tab.dataset.tab;
      tabs.forEach(x => x.classList.remove('tab--active'));
      tab.classList.add('tab--active');
      document.querySelectorAll('.tabcontent').forEach(tc => tc.classList.remove('tabcontent--active'));
      const el = document.getElementById(target);
      if (el) el.classList.add('tabcontent--active');
    }));
  }

  // Signup
  if (signupForm) {
    signupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (signupErr) signupErr.textContent = '';

      const name = suName ? suName.value.trim() : '';
      const email = suEmail ? suEmail.value.trim() : '';
      const password = suPassword ? suPassword.value : '';

      try {
        const data = await apiCall('/auth/signup', {
          method: 'POST',
          body: JSON.stringify({ name, email, password, role: 'client' })
        });

        state.currentUser = data.user;
        updateAccountUI();

        if (data.user.role === 'admin') window.location.href = '/admin';
        else window.location.href = '/booking';
      } catch (error) {
        if (signupErr) signupErr.textContent = error.message;
      }
    });
  }

  // Login
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (loginErr) loginErr.textContent = '';

      const email = loginEmail ? loginEmail.value.trim() : '';
      const password = loginPassword ? loginPassword.value : '';

      try {
        const data = await apiCall('/auth/login', {
          method: 'POST',
          body: JSON.stringify({ email, password })
        });

        state.currentUser = data.user;
        updateAccountUI();

        if (data.user.role === 'admin') window.location.href = '/admin';
        else window.location.href = '/booking';
      } catch (error) {
        if (loginErr) loginErr.textContent = error.message;
      }
    });
  }

  // Logout
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      try {
        await apiCall('/auth/logout', { method: 'POST' });
        state.currentUser = null;
        updateAccountUI();
        window.location.href = '/';
      } catch (error) {
        console.error('Logout failed:', error);
      }
    });
  }

  // Resend verification email
  if (resendVerificationBtn) {
    resendVerificationBtn.addEventListener('click', async () => {
      resendVerificationBtn.disabled = true;
      resendVerificationBtn.textContent = 'Sending...';
      
      try {
        const data = await apiCall('/auth/email/resend', { method: 'POST' });
        alert(data.message || 'Verification email sent! Check your inbox.');
      } catch (error) {
        alert(error.message || 'Failed to send verification email');
      } finally {
        resendVerificationBtn.disabled = false;
        resendVerificationBtn.textContent = 'Resend Verification Email';
      }
    });
  }
}

// ---------- Home Page ----------
const PLACEHOLDER_POSTER = 'data:image/svg+xml;utf8,' + encodeURIComponent(`
  <svg xmlns="http://www.w3.org/2000/svg" width="400" height="600">
    <defs>
      <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
        <stop offset="0%" stop-color="#1b2230"/>
        <stop offset="100%" stop-color="#121721"/>
      </linearGradient>
    </defs>
    <rect width="100%" height="100%" fill="url(#g)"/>
    <text x="50%" y="50%" fill="#778" font-family="Inter,Arial" font-size="22" text-anchor="middle">No Poster</text>
  </svg>`);

function renderMovieCards(targetEl, movies) {
  if (!targetEl) return;
  targetEl.innerHTML = '';
  movies.forEach(m => {
    const card = document.createElement('div');
    card.className = 'card';
    const img = document.createElement('img');
    img.className = 'card__img';
    img.loading = 'lazy';
    img.alt = `${m.title} poster`;
    img.src = m.poster || PLACEHOLDER_POSTER;
    img.onerror = () => { img.onerror = null; img.src = PLACEHOLDER_POSTER; };
    const body = document.createElement('div');
    body.className = 'card__body';
    const title = document.createElement('strong');
    title.textContent = m.title;
    const badge = document.createElement('span');
    badge.className = 'badge';
    const rating = (typeof m.rating === 'number') ? m.rating : Math.round(((m.price / 100) + 3) * 10) / 10;
    badge.textContent = `★ ${rating.toFixed(1)} · ₱${m.price}`;
    const btn = document.createElement('a');
    btn.href = '/booking';
    btn.className = 'btn';
    btn.textContent = 'Book';
    btn.addEventListener('click', () => {
      try { localStorage.setItem('selectedMovieId', m.id); } catch (e) { }
    });
    body.appendChild(title);
    body.appendChild(badge);
    body.appendChild(btn);
    card.appendChild(img);
    card.appendChild(body);
    targetEl.appendChild(card);
  });
}

async function initHomePage() {
  try {
    state.movies = await apiCall('/movies');

    // All movies
    renderMovieCards(allMoviesEl, state.movies);

    // Top rated
    const rated = [...state.movies].sort((a, b) => {
      const ra = (typeof a.rating === 'number') ? a.rating : ((a.price / 100) + 3);
      const rb = (typeof b.rating === 'number') ? b.rating : ((b.price / 100) + 3);
      return rb - ra;
    });
    renderMovieCards(topRatedEl, rated);

    // Most popular (shuffle)
    const popular = [...state.movies].sort(() => Math.random() - 0.5);
    renderMovieCards(mostPopularEl, popular);
  } catch (error) {
    console.error('Failed to load movies:', error);
  }
}

// ---------- Booking Page ----------
async function initBookingPage() {
  try {
    // Load movies from API
    state.movies = await apiCall('/movies');

    if (!state.movies || state.movies.length === 0) {
      if (movieSelect) {
        movieSelect.innerHTML = '<option>No movies available</option>';
        movieSelect.disabled = true;
      }
      if (addToCartBtn) addToCartBtn.disabled = true;
      if (seatMapEl) {
        const msg = document.createElement('div');
        msg.className = 'muted';
        msg.textContent = 'No movies available yet. Please check back later.';
        seatMapEl.innerHTML = '';
        seatMapEl.appendChild(msg);
      }
      return;
    }

    // Populate movie select
    if (movieSelect) {
      movieSelect.innerHTML = '';
      state.movies.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = `${m.title} — ₱${m.price}`;
        movieSelect.appendChild(opt);
      });

      // Pre-select from localStorage
      try {
        const pre = localStorage.getItem('selectedMovieId');
        if (pre && state.movies.some(m => m.id === pre)) {
          movieSelect.value = pre;
          localStorage.removeItem('selectedMovieId');
        }
      } catch (e) { }
    }

    // Set default date (use server date from data attribute, fallback to browser date)
    if (dateSelect) {
      const serverDate = dateSelect.getAttribute('data-server-date');
      if (serverDate) {
        dateSelect.value = serverDate;
      } else {
        // Fallback to browser date if server date not available
        dateSelect.value = new Date().toISOString().slice(0, 10);
      }
      
      // Make calendar picker open automatically when field is clicked
      dateSelect.addEventListener('click', function(e) {
        if (this.showPicker) {
          try {
            this.showPicker();
          } catch(err) {
            // Fallback for browsers that don't support showPicker
            console.log('showPicker not supported');
          }
        }
      });
    }

    // Event listeners
    function refreshShowtimes() {
      const mv = state.movies.find(m => m.id === (movieSelect ? movieSelect.value : state.movies[0].id));
      if (showtimeSelect) {
        showtimeSelect.innerHTML = '';
        mv.showtimes.forEach(t => {
          const o = document.createElement('option');
          o.value = t;
          o.textContent = t;
          showtimeSelect.appendChild(o);
        });
      }
      if (seatPriceDisplay) seatPriceDisplay.textContent = mv.price;
      selected.clear(); // Clear seat selection when movie changes
      renderSeatMap();
    }

    // Seat selection
    const selected = new Set();

    // Clear selection and re-render when session parameters change
    if (movieSelect) movieSelect.addEventListener('change', refreshShowtimes);
    if (showtimeSelect) showtimeSelect.addEventListener('change', () => {
      selected.clear();
      renderSeatMap();
    });
    if (dateSelect) {
      dateSelect.addEventListener('change', () => {
        // Validate date is not in the past (use server date from min attribute)
        const selectedDate = new Date(dateSelect.value);
        const minDate = new Date(dateSelect.getAttribute('min'));
        
        if (selectedDate < minDate) {
          alert('Cannot book tickets for past dates. Please select today or a future date.');
          // Reset to server date
          const serverDate = dateSelect.getAttribute('data-server-date');
          dateSelect.value = serverDate || new Date().toISOString().slice(0, 10);
          return;
        }
        
        selected.clear();
        renderSeatMap();
      });
    }
    if (cinemaSelect) cinemaSelect.addEventListener('change', () => {
      selected.clear();
      renderSeatMap();
    });

    async function renderSeatMap() {
      if (!seatMapEl) return;
      seatMapEl.innerHTML = '';

      const rows = state.seatRows, cols = state.seatCols, aisle = state.aisleAfter;
      const mvId = movieSelect ? movieSelect.value : state.movies[0].id;
      const date = dateSelect ? dateSelect.value : new Date().toISOString().slice(0, 10);
      const time = showtimeSelect ? showtimeSelect.value : (state.movies[0].showtimes[0]);
      const cinema = cinemaSelect ? cinemaSelect.value : 'Cinema 1';

      // Fetch occupied seats from API
      let occupied = new Set();
      try {
        const data = await apiCall(`/seats/occupied?movieId=${mvId}&date=${date}&time=${time}&cinema=${encodeURIComponent(cinema)}`);
        occupied = new Set(data.occupiedSeats);
      } catch (error) {
        console.error('Failed to fetch occupied seats:', error);
      }

      // Cart holds
      const heldSeats = new Set();
      (state.cart || []).forEach(it => {
        const cid = sessionId(it.movieId, it.date, it.time, it.cinema);
        const sid = sessionId(mvId, date, time, cinema);
        if (cid === sid) {
          it.seats.forEach(s => heldSeats.add(s));
        }
      });

      const totalSeats = rows * cols;
      const reservedCount = new Set([...occupied, ...heldSeats]).size;

      for (let r = 1; r <= rows; r++) {
        for (let c = 1; c <= cols; c++) {
          const key = seatKey(r, c);
          const seat = document.createElement('button');
          seat.className = 'seat';
          if (c === aisle + 1) seat.classList.add('seat--aisle');
          seat.dataset.seat = key;
          seat.setAttribute('type', 'button');

          if (occupied.has(key) || heldSeats.has(key)) {
            seat.classList.add('seat--occupied');
            seat.disabled = true;
          }

          if (selected.has(key)) seat.classList.add('seat--selected');

          seat.addEventListener('click', () => {
            if (seat.classList.contains('seat--selected')) {
              selected.delete(key);
              seat.classList.remove('seat--selected');
            } else {
              selected.add(key);
              seat.classList.add('seat--selected');
            }
            updateSelectionUI();
          });

          seatMapEl.appendChild(seat);
        }
      }

      updateSelectionUI();

      function updateSelectionUI() {
        const mv = state.movies.find(m => m.id === mvId);
        const seatPrice = mv ? mv.price : 0;
        if (selectedSeatsEl) selectedSeatsEl.textContent = selected.size;
        if (totalPriceEl) totalPriceEl.textContent = seatPrice * selected.size;
        if (addToCartBtn) addToCartBtn.disabled = selected.size === 0 || !state.currentUser;
        if (authHint) authHint.style.display = state.currentUser ? 'none' : '';

        const selectedCount = selected.size;
        let availableCount = totalSeats - reservedCount - selectedCount;
        if (availableCount < 0) availableCount = 0;

        if (legendAvailableEl) legendAvailableEl.textContent = availableCount;
        if (legendSelectedEl) legendSelectedEl.textContent = selectedCount;
        if (legendReservedEl) legendReservedEl.textContent = reservedCount;
      }
    }

    // Add to cart
    if (addToCartBtn) {
      addToCartBtn.addEventListener('click', () => {
        const mv = state.movies.find(m => m.id === movieSelect.value);
        const date = dateSelect.value;
        const time = showtimeSelect.value;
        const cinema = cinemaSelect.value;

        const seats = Array.from(seatMapEl.querySelectorAll('.seat--selected')).map(s => s.dataset.seat);
        if (seats.length === 0) return alert('Select seats first');

        // Find existing cart item for this session
        let cartItem = state.cart.find(
          it => it.movieId === mv.id && it.date === date && it.time === time && it.cinema === cinema
        );

        if (cartItem) {
          // Only add seats that are not already present
          const newSeats = seats.filter(seat => !cartItem.seats.includes(seat));
          if (newSeats.length === 0) {
            alert('Selected seats are already in cart');
            return;
          }
          cartItem.seats = cartItem.seats.concat(newSeats);
          cartItem.total = mv.price * cartItem.seats.length;
        } else {
          const itemId = `ci_${Math.random().toString(36).slice(2, 8)}${Date.now().toString(36).slice(-4)}`;
          const item = {
            id: itemId,
            movieId: mv.id,
            title: mv.title,
            date,
            time,
            cinema,
            seats,
            seatPrice: mv.price,
            total: mv.price * seats.length
          };
          state.cart.push(item);
        }
        renderCart();
        renderSeatMap();
      });
    }

    function renderCart() {
      if (!cartListEl) return;
      cartListEl.innerHTML = '';

      if (state.cart.length === 0) {
        cartListEl.innerHTML = '<li class="muted">Cart is empty</li>';
        if (goCheckoutBtnEl) goCheckoutBtnEl.disabled = true;
        if (clearCartBtnEl) clearCartBtnEl.disabled = true;
        return;
      }

      state.cart.forEach(it => {
        const li = cartItemTpl.content.cloneNode(true);
        li.querySelector('.cart__movie').textContent = it.title;
        li.querySelector('.cart__meta').textContent = `${it.date} @ ${it.time}`;
        li.querySelector('.cart__seats').textContent = it.seats.join(', ');
        li.querySelector('.cart__price span').textContent = it.total;

        const node = li.querySelector('li');
        node.querySelector('.cart__remove').addEventListener('click', () => {
          const idx = state.cart.findIndex(x => x.id === it.id);
          if (idx !== -1) state.cart.splice(idx, 1);
          renderCart();
          renderSeatMap();
        });

        cartListEl.appendChild(node);
      });

      if (goCheckoutBtnEl) goCheckoutBtnEl.disabled = !state.currentUser;
      if (clearCartBtnEl) clearCartBtnEl.disabled = false;
    }

    if (clearCartBtnEl) {
      clearCartBtnEl.addEventListener('click', () => {
        state.cart = [];
        renderCart();
        renderSeatMap();
      });
    }

    // Checkout
    if (goCheckoutBtnEl) {
      goCheckoutBtnEl.addEventListener('click', () => {
        if (!state.currentUser) {
          window.location.href = '/login';
          return;
        }
        if (paymentSection) paymentSection.style.display = '';
        renderCheckoutSummary();
      });
    }

    function renderCheckoutSummary() {
      if (!checkoutItemsEl) return;
      checkoutItemsEl.innerHTML = '';

      let total = 0;
      state.cart.forEach(it => {
        const li = summaryItemTpl.content.cloneNode(true);
        li.querySelector('.sum__movie').textContent = it.title;
        li.querySelector('.sum__meta').textContent = `${it.date} @ ${it.time} — ${it.cinema}`;
        li.querySelector('.sum__seats').textContent = `Seats: ${it.seats.join(', ')}`;
        li.querySelector('.sum__price span').textContent = it.total;
        checkoutItemsEl.appendChild(li);
        total += it.total;
      });

      if (checkoutTotalEl) checkoutTotalEl.textContent = total;
      if (placeOrderBtnEl) placeOrderBtnEl.disabled = state.cart.length === 0 || !state.currentUser;
    }

    // Place order
    if (placeOrderBtnEl) {
      placeOrderBtnEl.addEventListener('click', async () => {
        if (!state.currentUser) {
          window.location.href = '/login';
          return;
        }

        try {
          const items = state.cart.map(it => ({
            movieId: it.movieId,
            title: it.title,
            date: it.date,
            time: it.time,
            cinema: it.cinema,
            seats: it.seats,
            seatPrice: it.seatPrice
          }));

          await apiCall('/bookings', {
            method: 'POST',
            body: JSON.stringify({ items })
          });

          state.cart = [];
          renderCart();
          if (paymentSection) paymentSection.style.display = 'none';
          alert('Booking confirmed — thank you!');
          renderSeatMap();
          await loadUserBookings();
        } catch (error) {
          // Check if error is due to unverified email
          if (error.message && error.message.includes('verify your email')) {
            alert('Please verify your email address before booking. Check your inbox for the verification link, or click "Resend Verification Email" above.');
            window.scrollTo(0, 0); // Scroll to top to see banner
          } else {
            alert('Booking failed: ' + error.message);
          }
        }
      });
    }

    // Resend verification email button
    if (resendVerificationBtn) {
      resendVerificationBtn.addEventListener('click', async () => {
        try {
          const result = await apiCall('/auth/email/resend', { method: 'POST' });
          alert(result.message || 'Verification email sent! Please check your inbox.');
        } catch (error) {
          alert('Failed to resend email: ' + error.message);
        }
      });
    }

    // Initial render
    refreshShowtimes();
    renderCart();
  } catch (error) {
    console.error('Failed to initialize booking page:', error);
  }
}

// ---------- User Bookings ----------
async function loadUserBookings() {
  if (!bookingListEl) return;

  try {
    const bookings = await apiCall('/bookings');
    state.bookings = bookings;

    bookingListEl.innerHTML = '';

    if (bookings.length === 0) {
      bookingListEl.innerHTML = '<li class="muted">No bookings yet.</li>';
      return;
    }

    bookings.forEach(b => {
      const li = document.createElement('li');
      li.className = 'admin-item';
      const firstItem = b.items[0];
      li.innerHTML = `<div>
        <strong>${firstItem.movie_title || firstItem.title}</strong>
        <small>${new Date(b.created_at).toLocaleString()}</small>
        <div class="cart__meta">${firstItem.date || firstItem.booking_date} @ ${firstItem.time || firstItem.showtime} — ${firstItem.cinema}</div>
        <div class="cart__seats">Seats: ${b.items.map(it => it.seat_key || it.seats[0]).join(', ')}</div>
        <div class="cart__meta">Status: ${b.status || 'Paid'}</div>
      </div>
      <div><div class="cart__price">₱${peso(b.total)}</div></div>`;
      bookingListEl.appendChild(li);
    });
  } catch (error) {
    console.error('Failed to load bookings:', error);
    if (bookingListEl) bookingListEl.innerHTML = '<li class="muted">Failed to load bookings</li>';
  }
}

// ---------- Admin Page ----------
async function initAdminPage() {
  // Guard: only admins
  if (!state.currentUser || state.currentUser.role !== 'admin') {
    window.location.href = '/login';
    return;
  }

  await loadAdminData();

  // Reset button
  const resetBtn = document.getElementById('resetDemoBtn');
  if (resetBtn) {
    resetBtn.addEventListener('click', async () => {
      const ok = confirm('Reset all bookings? This cannot be undone.');
      if (!ok) return;

      try {
        await apiCall('/bookings/reset', { method: 'DELETE' });
        alert('Demo data has been reset.');
        await loadAdminData();
      } catch (error) {
        alert('Reset failed: ' + error.message);
      }
    });
  }

  // Add movie
  if (movieForm) {
    movieForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (movieErr) movieErr.textContent = '';

      const title = mvTitle ? mvTitle.value.trim() : '';
      const price = mvPrice ? Number(mvPrice.value) : 0;
      const rating = document.getElementById('mvRating') ? Number(document.getElementById('mvRating').value) : 0;
      const poster = mvPoster ? mvPoster.value.trim() : '';
      const timesStr = mvTimes ? mvTimes.value.trim() : '';

      if (!title || !price || !timesStr) {
        if (movieErr) movieErr.textContent = 'Title, price, and showtimes are required';
        return;
      }

      const showtimes = timesStr.split(',').map(s => s.trim()).filter(Boolean);

      try {
        await apiCall('/movies', {
          method: 'POST',
          body: JSON.stringify({ title, price, poster, rating, showtimes })
        });

        alert('Movie added successfully!');

        // Clear form
        if (mvTitle) mvTitle.value = '';
        if (mvPrice) mvPrice.value = '';
        if (mvPoster) mvPoster.value = '';
        if (mvTimes) mvTimes.value = '';
        if (document.getElementById('mvRating')) document.getElementById('mvRating').value = '';

        await loadAdminData();
      } catch (error) {
        if (movieErr) movieErr.textContent = error.message;
      }
    });
  }
}

async function loadAdminData() {
  try {
    // Load movies
    const movies = await apiCall('/movies');
    state.movies = movies;

    // Load bookings
    const bookings = await apiCall('/bookings');
    state.bookings = bookings;

    renderAdminMovies();
    renderAdminBookings();
    renderAdminKPIs();
  } catch (error) {
    console.error('Failed to load admin data:', error);
  }
}

function renderAdminMovies() {
  if (!adminMovieList) return;
  adminMovieList.innerHTML = '';

  state.movies.forEach(m => {
    const li = document.createElement('li');
    li.className = 'admin-item';
    li.innerHTML = `<div>
      <strong>${m.title}</strong> <small>₱${m.price} — ${m.showtimes.join(', ')}</small>
    </div>`;
    adminMovieList.appendChild(li);
  });
}

function renderAdminBookings() {
  if (!adminBookingList) return;
  adminBookingList.innerHTML = '';

  if (state.bookings.length === 0) {
    adminBookingList.innerHTML = '<li class="muted">No bookings</li>';
    return;
  }

  state.bookings.slice().reverse().forEach(b => {
    const li = document.createElement('li');
    li.className = 'admin-item';
    const movieTitles = b.items.map(it => it.movie_title).filter((v, i, a) => a.indexOf(v) === i).join(', ');
    li.innerHTML = `<div>
      <strong>${b.name || 'Guest'}</strong>
      <small>${new Date(b.created_at).toLocaleString()}</small>
      <div class="admin-item-meta">${movieTitles}</div>
      <small class="muted">${b.email || ''} — ₱${peso(b.total)} — Status: ${b.status || 'Paid'}</small>
    </div>
    <div>
      <button class="btn btn--ghost admin-cancel" data-id="${b.id}" ${b.status === 'Cancelled' ? 'disabled' : ''}>Cancel</button>
    </div>`;
    adminBookingList.appendChild(li);
  });

  // Attach cancel handlers
  adminBookingList.querySelectorAll('.admin-cancel').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = e.currentTarget.dataset.id;
      try {
        await apiCall(`/bookings/${id}/cancel`, { method: 'PUT' });
        await loadAdminData();
      } catch (error) {
        alert('Cancel failed: ' + error.message);
      }
    });
  });
}

function renderAdminKPIs() {
  if (kpiMovies) kpiMovies.textContent = state.movies.length;
  if (kpiBookings) kpiBookings.textContent = state.bookings.length;

  const statusCounts = state.bookings.reduce((acc, b) => {
    const s = b.status || 'Paid';
    acc[s] = (acc[s] || 0) + 1;
    return acc;
  }, {});

  if (kpiPaid) kpiPaid.textContent = statusCounts['Paid'] || 0;
  if (kpiPending) kpiPending.textContent = statusCounts['Pending'] || 0;
  if (kpiCancelled) kpiCancelled.textContent = statusCounts['Cancelled'] || 0;
}

// ---------- Global Init ----------
async function initGlobal() {
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // Check session
  await checkSession();

  const page = document.body && document.body.dataset.page;

  if (page === 'auth') {
    initAuthPage();
  } else {
    // Logout handler for non-auth pages
    if (logoutBtn) {
      logoutBtn.addEventListener('click', async () => {
        try {
          await apiCall('/auth/logout', { method: 'POST' });
          state.currentUser = null;
          updateAccountUI();
          window.location.href = '/';
        } catch (error) {
          console.error('Logout failed:', error);
        }
      });
    }
  }

  if (page === 'booking') {
    await initBookingPage();
    await loadUserBookings();
  }

  if (page === 'admin') {
    await initAdminPage();
  }

  if (page === 'home') {
    await initHomePage();
  }
}

// Run init
initGlobal();

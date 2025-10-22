/* =========================
   XentroCine — Movie Booking
   Now with Auth (Client/Admin)
   Pure JS (no frameworks)
========================= */
const state = {
  users: [],        // {id, name, email, password, role}
  currentUser: null, // {id, name, email, role}
  movies: [
    {
      id: "mv100",
      title: "Star Quest",
      price: 300,
      poster: "images/starquest.jpg",
      showtimes: ["13:00", "15:30", "18:00", "20:30"]
    },
    {
      id: "mv200",
      title: "The Last Laugh",
      price: 320,
      poster: "images/thelastlaugh.jpg",
      showtimes: ["12:00", "14:15", "17:00", "19:45", "21:30"]
    },
    {
      id: "mv300",
      title: "Hidden Tide",
      price: 350,
      poster: "images/hiddentide.jpg",
      showtimes: ["11:00", "13:45", "16:15", "18:45"]
    }
  ],
  seatRows: 9,
  seatCols: 14,
  aisleAfter: 7, // visual gap
  occupiedMap: {}, // key: sessionId -> Set of occupied "r-c"
  cart: [],       // items: {id, movieId, movie, date, time, cinema, seats[], seatPrice, total}
  bookings: []    // {id, createdAt, items[], total, name, email, phone, reference, userId}
};

// ---------- DOM ----------
const navBtns = [...document.querySelectorAll(".nav__link")];
const adminNavBtn = document.querySelector(".nav__admin");
const views = {
  auth: document.getElementById("view-auth"),
  browse: document.getElementById("view-browse"),
  checkout: document.getElementById("view-checkout"),
  bookings: document.getElementById("view-bookings"),
  admin: document.getElementById("view-admin"),
};

const yearEl = document.getElementById("year");
// Home sections
const topRatedEl = document.getElementById('topRated');
const mostPopularEl = document.getElementById('mostPopular');
const allMoviesEl = document.getElementById('allMovies');
const movieSelect = document.getElementById("movieSelect");
const dateSelect = document.getElementById("dateSelect");
const showtimeSelect = document.getElementById("showtimeSelect");
const cinemaSelect = document.getElementById("cinemaSelect");
// seat/cart elements declared later where booking page logic is initialized
const authHint = document.getElementById("authHint");
// cart elements declared later

const checkoutEmpty = document.getElementById("checkoutEmpty");
const checkoutForm = document.getElementById("checkoutForm");
const checkoutItems = document.getElementById("checkoutItems");
const checkoutTotal = document.getElementById("checkoutTotal");
const placeOrderBtn = document.getElementById("placeOrderBtn");

const bookingList = document.getElementById("bookingList");
const clearBookingsBtn = document.getElementById("clearBookingsBtn");

const cartItemTpl = document.getElementById("cartItemTpl");
const summaryItemTpl = document.getElementById("summaryItemTpl");

// Auth UI
const currentUserEl = document.getElementById("currentUser");
const loginBtn = document.getElementById("loginBtn");
const logoutBtn = document.getElementById("logoutBtn");

const tabs = [...document.querySelectorAll(".tab")];
const loginForm = document.getElementById("loginForm");
const loginEmail = document.getElementById("loginEmail");
const loginPassword = document.getElementById("loginPassword");
const loginErr = document.getElementById("loginErr");

const signupForm = document.getElementById("signupForm");
const suName = document.getElementById("suName");
const suEmail = document.getElementById("suEmail");
const suPassword = document.getElementById("suPassword");
const suRole = document.getElementById("suRole");
const signupErr = document.getElementById("signupErr");

// Admin UI
const movieForm = document.getElementById("movieForm");
const mvTitle = document.getElementById("mvTitle");
const mvPrice = document.getElementById("mvPrice");
const mvPoster = document.getElementById("mvPoster");
const mvTimes = document.getElementById("mvTimes");
const movieErr = document.getElementById("movieErr");
const adminMovieList = document.getElementById("adminMovieList");
const adminBookingList = document.getElementById("adminBookingList");
// Admin KPIs
const kpiMovies = document.getElementById('kpiMovies');
const kpiBookings = document.getElementById('kpiBookings');
const kpiPaid = document.getElementById('kpiPaid');
const kpiPending = document.getElementById('kpiPending');
const kpiCancelled = document.getElementById('kpiCancelled');

// booking elements (user)
const bookingListEl = document.getElementById('bookingList');
// seat/cart/checkout elements
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
// legend counters
const legendAvailableEl = document.getElementById('legendAvailable');
const legendSelectedEl = document.getElementById('legendSelected');
const legendReservedEl = document.getElementById('legendReserved');

// ---------- Utils ----------
const peso = n => (Math.round(n * 100) / 100).toLocaleString("en-PH", { minimumFractionDigits: 0 });

function uid(prefix="id"){
  return `${prefix}_${Math.random().toString(36).slice(2,8)}${Date.now().toString(36).slice(-4)}`;
}
function sessionId(movieId, date, time, cinema){
  return `${movieId}|${date}|${time}|${cinema}`;
}
function seatKey(r,c){ return `${r}-${c}`; }

function persist(){
  localStorage.setItem("xentroCineState", JSON.stringify({
    users: state.users,
    currentUser: state.currentUser,
    movies: state.movies,
    occupiedMap: Object.fromEntries(Object.entries(state.occupiedMap).map(([k,v]) => [k, [...v]])),
    cart: state.cart,
    bookings: state.bookings
  }));
}
function restore(){
  const raw = localStorage.getItem("xentroCineState");
  if(!raw) return;
  try{
    const data = JSON.parse(raw);
    state.users = data.users || [];
    state.currentUser = data.currentUser || null;
    state.movies = data.movies && data.movies.length ? data.movies : state.movies;
    state.occupiedMap = Object.fromEntries(Object.entries(data.occupiedMap || {}).map(([k,arr]) => [k, new Set(arr)]));
    state.cart = data.cart || [];
    state.bookings = data.bookings || [];
  }catch(e){ console.warn("Failed to parse saved state", e); }
}

// Seed a default admin account if none exists
function ensureDefaultAdmin(){
  const hasAdmin = state.users.some(u => u.role==="admin");
  if(!hasAdmin){
    state.users.push({
      id: uid("u"),
      name: "Administrator",
      email: "admin@xentrocine.local",
      password: "Admin123!",
      role: "admin"
    });
  }
}

// ---------- Minimal initialization & Auth handlers (safe, page-aware) ----------
function updateAccountUI(){
  if(currentUserEl) currentUserEl.textContent = state.currentUser ? state.currentUser.name : 'Guest';
  if(loginBtn) loginBtn.style.display = state.currentUser ? 'none' : '';
  if(logoutBtn) logoutBtn.style.display = state.currentUser ? '' : 'none';
  if(adminNavBtn){
    adminNavBtn.style.display = (state.currentUser && state.currentUser.role === 'admin') ? '' : 'none';
  }
}

function initAuthPage(){
  // Tabs switching (login / signup)
  if(tabs && tabs.length){
    tabs.forEach(t => t.addEventListener('click', (e) => {
      const tab = e.currentTarget;
      const target = tab.dataset.tab;
      tabs.forEach(x => x.classList.remove('tab--active'));
      tab.classList.add('tab--active');
      document.querySelectorAll('.tabcontent').forEach(tc => tc.classList.remove('tabcontent--active'));
      const el = document.getElementById(target);
      if(el) el.classList.add('tabcontent--active');
    }));
  }

  // Signup
  if(signupForm){
    signupForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if(signupErr) signupErr.textContent = '';
      const name = suName ? suName.value.trim() : '';
      const email = suEmail ? suEmail.value.trim().toLowerCase() : '';
      const pass = suPassword ? suPassword.value : '';
      const role = suRole ? suRole.value : 'client';
      if(!name || !email || !pass){ if(signupErr) signupErr.textContent = 'Please fill all fields'; return; }
      if(state.users.some(u => u.email === email)){ if(signupErr) signupErr.textContent = 'Email already exists'; return; }
      const u = { id: uid('u'), name, email, password: pass, role };
      state.users.push(u);
      state.currentUser = { id: u.id, name: u.name, email: u.email, role: u.role };
      persist();
      updateAccountUI();
      // Redirect after signup
      if(u.role === 'admin') location.assign('admin.html');
      else location.assign('booking.html');
    });
  }

  // Login
  if(loginForm){
    loginForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if(loginErr) loginErr.textContent = '';
      const email = loginEmail ? loginEmail.value.trim().toLowerCase() : '';
      const pass = loginPassword ? loginPassword.value : '';
      const user = state.users.find(u => u.email === email && u.password === pass);
      if(!user){ if(loginErr) loginErr.textContent = 'Invalid credentials'; return; }
      state.currentUser = { id: user.id, name: user.name, email: user.email, role: user.role };
      persist();
      updateAccountUI();
      if(user.role === 'admin') location.assign('admin.html');
      else location.assign('booking.html');
    });
  }

  // Logout (also available on auth page)
  if(logoutBtn){
    logoutBtn.addEventListener('click', () => {
      state.currentUser = null;
      persist();
      updateAccountUI();
      location.assign('index.html');
    });
  }
}

function initGlobal(){
  // restore state and ensure default admin
  restore();
  ensureDefaultAdmin();
  // set year if present
  if(yearEl) yearEl.textContent = new Date().getFullYear();
  // prune expired pending bookings on every start
  // pruneExpiredBookings();
  updateAccountUI();

  const page = document.body && document.body.dataset.page;
  if(page === 'auth'){
    initAuthPage();
  } else {
    // Make logout work on other pages too
    if(logoutBtn){
      logoutBtn.addEventListener('click', () => {
        state.currentUser = null;
        persist();
        updateAccountUI();
        location.assign('index.html');
      });
    }
  }

  // wire booking page flows
  if(page === 'booking'){
    initBookingPage();
  }
  if(page === 'admin'){
    initAdminPage();
  }
  if(page === 'home'){
    initHomePage();
  }

  // render lists if present
  renderUserBookings();
  renderAdminBookings();
}

// Run init (script is loaded with `defer`, so DOM is ready)
initGlobal();

// ---------- Home (index) rendering ----------
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

function renderMovieCards(targetEl, movies){
  if(!targetEl) return;
  targetEl.innerHTML = '';
  movies.forEach(m => {
    const card = document.createElement('div'); card.className = 'card';
    const img = document.createElement('img'); img.className = 'card__img'; img.loading = 'lazy'; img.alt = `${m.title} poster`;
    img.src = m.poster || PLACEHOLDER_POSTER;
    img.onerror = () => { img.onerror = null; img.src = PLACEHOLDER_POSTER; };
    const body = document.createElement('div'); body.className = 'card__body';
    const title = document.createElement('strong'); title.textContent = m.title;
    const badge = document.createElement('span'); badge.className = 'badge';
    const rating = (typeof m.rating === 'number') ? m.rating : Math.round(((m.price/100)+3)*10)/10;
    badge.textContent = `★ ${rating.toFixed(1)} · ₱${m.price}`;
    const btn = document.createElement('a'); btn.href = 'booking.html'; btn.className = 'btn'; btn.textContent = 'Book';
    btn.addEventListener('click', () => {
      try{ localStorage.setItem('selectedMovieId', m.id); }catch(e){}
    });
    body.appendChild(title); body.appendChild(badge); body.appendChild(btn);
    card.appendChild(img); card.appendChild(body);
    targetEl.appendChild(card);
  });
}

function initHomePage(){
  // all movies
  renderMovieCards(allMoviesEl, state.movies);
  // top rated (by rating if present, else fallback derived)
  const rated = [...state.movies].sort((a,b)=>{
    const ra = (typeof a.rating==='number')?a.rating:((a.price/100)+3);
    const rb = (typeof b.rating==='number')?b.rating:((b.price/100)+3);
    return rb - ra;
  });
  renderMovieCards(topRatedEl, rated);
  // most popular (simple shuffle for demo)
  const popular = [...state.movies].sort(()=>Math.random()-0.5);
  renderMovieCards(mostPopularEl, popular);
}

// ---------- Booking / Seat map / Checkout flows ----------
function initBookingPage(){
  // If there are no movies yet, show a friendly message and disable interactions
  if(!state.movies || state.movies.length === 0){
    if(movieSelect){ movieSelect.innerHTML = '<option>No movies available</option>'; movieSelect.disabled = true; }
    if(addToCartBtn) addToCartBtn.disabled = true;
    if(seatMapEl){
      const msg = document.createElement('div');
      msg.className = 'muted';
      msg.textContent = 'No movies available yet. Please check back later.';
      seatMapEl.innerHTML = '';
      seatMapEl.appendChild(msg);
    }
    return;
  }
  // populate movie select
  if(movieSelect){
    movieSelect.innerHTML = '';
    state.movies.forEach(m => {
      const opt = document.createElement('option'); opt.value = m.id; opt.textContent = `${m.title} — ₱${m.price}`;
      movieSelect.appendChild(opt);
    });
    // pick preselected movie if coming from Home
    try{
      const pre = localStorage.getItem('selectedMovieId');
      if(pre && state.movies.some(m=>m.id===pre)){
        movieSelect.value = pre;
        localStorage.removeItem('selectedMovieId');
      }
    }catch(e){}
  }
  // set default date
  if(dateSelect){ dateSelect.value = new Date().toISOString().slice(0,10); }
  // showtimes for selected movie
  function refreshShowtimes(){
    const mv = state.movies.find(m => m.id === (movieSelect ? movieSelect.value : state.movies[0].id));
    if(showtimeSelect) {
      showtimeSelect.innerHTML = '';
      mv.showtimes.forEach(t => { const o = document.createElement('option'); o.value = t; o.textContent = t; showtimeSelect.appendChild(o); });
    }
    if(seatPriceDisplay) seatPriceDisplay.textContent = mv.price;
    // render seats for this session
    renderSeatMap();
  }
  if(movieSelect) movieSelect.addEventListener('change', refreshShowtimes);
  if(showtimeSelect) showtimeSelect.addEventListener('change', renderSeatMap);
  if(dateSelect) dateSelect.addEventListener('change', renderSeatMap);
  if(cinemaSelect) cinemaSelect.addEventListener('change', renderSeatMap);

  // auth gating helpers
  const isAuthed = () => !!state.currentUser;
  function updateAuthGate(){
    if(authHint) authHint.style.display = isAuthed() ? 'none' : '';
    if(goCheckoutBtnEl) goCheckoutBtnEl.disabled = (state.cart.length === 0) || !isAuthed();
    if(placeOrderBtnEl) placeOrderBtnEl.disabled = (state.cart.length === 0) || !isAuthed();
  }

  // seat selection state
  const selected = new Set();

  function renderSeatMap(){
    if(!seatMapEl) return;
    seatMapEl.innerHTML = '';
    const rows = state.seatRows, cols = state.seatCols, aisle = state.aisleAfter;
    const mvId = movieSelect ? movieSelect.value : state.movies[0].id;
    const date = dateSelect ? dateSelect.value : new Date().toISOString().slice(0,10);
    const time = showtimeSelect ? showtimeSelect.value : (state.movies[0].showtimes[0]);
    const cinema = cinemaSelect ? cinemaSelect.value : 'Cinema 1';
    const sId = sessionId(mvId, date, time, cinema);
    const occupied = state.occupiedMap[sId] ? new Set(state.occupiedMap[sId]) : new Set();
    // Also treat seats in cart for this same session as reserved (temporary hold)
    const heldSeats = new Set();
    (state.cart || []).forEach(it => {
      const cid = sessionId(it.movieId, it.date, it.time, it.cinema);
      if(cid === sId){ it.seats.forEach(s => heldSeats.add(s)); }
    });
  let totalSeats = rows * cols;
  let reservedCount = new Set([...occupied, ...heldSeats]).size;
    for(let r=1;r<=rows;r++){
      for(let c=1;c<=cols;c++){
        const key = seatKey(r,c);
        const seat = document.createElement('button');
        seat.className = 'seat';
        if(c === aisle+1) seat.classList.add('seat--aisle');
        seat.dataset.seat = key;
        seat.setAttribute('type','button');
        if(occupied.has(key) || heldSeats.has(key)){
          seat.classList.add('seat--occupied');
          seat.disabled = true;
        }
        if(selected.has(key)) seat.classList.add('seat--selected');
        seat.addEventListener('click', () => {
          if(seat.classList.contains('seat--selected')){ selected.delete(key); seat.classList.remove('seat--selected'); }
          else { selected.add(key); seat.classList.add('seat--selected'); }
          updateSelectionUI();
        });
        seatMapEl.appendChild(seat);
      }
    }
    updateSelectionUI();
    function updateSelectionUI(){
      const mv = state.movies.find(m => m.id === mvId);
      const seatPrice = mv ? mv.price : 0;
      if(selectedSeatsEl) selectedSeatsEl.textContent = selected.size;
      if(totalPriceEl) totalPriceEl.textContent = seatPrice * selected.size;
      if(addToCartBtn) addToCartBtn.disabled = selected.size === 0 || !isAuthed();
      if(authHint) authHint.style.display = isAuthed() ? 'none' : '';
      const selectedCount = selected.size;
      let availableCount = totalSeats - reservedCount - selectedCount;
      if(availableCount < 0) availableCount = 0;
      if(legendAvailableEl) legendAvailableEl.textContent = availableCount;
      if(legendSelectedEl) legendSelectedEl.textContent = selectedCount;
      if(legendReservedEl) legendReservedEl.textContent = reservedCount;
    }
  }

  // add to cart
  if(addToCartBtn){
    addToCartBtn.addEventListener('click', () => {
      const mv = state.movies.find(m => m.id === movieSelect.value);
      const date = dateSelect.value;
      const time = showtimeSelect.value;
      const cinema = cinemaSelect.value;
      // collect selected seats from DOM
      const seats = Array.from(seatMapEl.querySelectorAll('.seat--selected')).map(s => s.dataset.seat);
      if(seats.length === 0) return alert('Select seats first');
      const item = { id: uid('ci'), movieId: mv.id, title: mv.title, date, time, cinema, seats, seatPrice: mv.price, total: mv.price * seats.length };
      state.cart.push(item);
      persist();
      renderCart();
      // re-render to reflect holds and update legend
      renderSeatMap();
    });
  }

  function renderCart(){
    if(!cartListEl) return;
    cartListEl.innerHTML = '';
    if(state.cart.length === 0){ cartListEl.innerHTML = '<li class="muted">Cart is empty</li>'; if(goCheckoutBtnEl) goCheckoutBtnEl.disabled = true; if(clearCartBtnEl) clearCartBtnEl.disabled = true; updateAuthGate(); return; }
    state.cart.forEach(it => {
      const li = cartItemTpl.content.cloneNode(true);
      li.querySelector('.cart__movie').textContent = it.title;
      li.querySelector('.cart__meta').textContent = `${it.date} @ ${it.time}`;
      li.querySelector('.cart__seats').textContent = it.seats.join(', ');
      li.querySelector('.cart__price span').textContent = it.total;
      const node = li.querySelector('li');
      node.querySelector('.cart__remove').addEventListener('click', () => {
        // free seats from this cart item
        const idx = state.cart.findIndex(x => x.id === it.id);
        if(idx !== -1) state.cart.splice(idx,1);
        persist();
        renderCart();
        renderSeatMap();
      });
      cartListEl.appendChild(node);
    });
    if(goCheckoutBtnEl) goCheckoutBtnEl.disabled = !isAuthed() ? true : false;
    if(clearCartBtnEl) clearCartBtnEl.disabled = false;
    updateAuthGate();
  }

  if(clearCartBtnEl){ clearCartBtnEl.addEventListener('click', () => { state.cart = []; persist(); renderCart(); renderSeatMap(); }); }

  // toggle gcash form visibility
  const gcashForm = document.getElementById('gcashForm');
  let payMethod = 'Cash';
  const payRadios = document.querySelectorAll('input[name="payMethod"]');
  if(payRadios && payRadios.length){
    payRadios.forEach(r => r.addEventListener('change', (e) => {
      payMethod = e.target.value;
      if(gcashForm) gcashForm.style.display = payMethod === 'GCash' ? '' : 'none';
    }));
    // initial
    const checked = document.querySelector('input[name="payMethod"]:checked');
    if(checked){ payMethod = checked.value; if(gcashForm) gcashForm.style.display = payMethod === 'GCash' ? '' : 'none'; }
  }

  if(goCheckoutBtnEl){
    goCheckoutBtnEl.addEventListener('click', () => {
      if(!isAuthed()) { location.assign('login.html'); return; }
      // show payment section
      if(paymentSection) paymentSection.style.display = '';
      // populate summary
      renderCheckoutSummary();
    });
  }

  function renderCheckoutSummary(){
    if(!checkoutItemsEl) return;
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
    if(checkoutTotalEl) checkoutTotalEl.textContent = total;
    if(placeOrderBtnEl) placeOrderBtnEl.disabled = (state.cart.length === 0) || !isAuthed();
  }

  if(placeOrderBtnEl){
    placeOrderBtnEl.addEventListener('click', () => {
      if(!isAuthed()) { location.assign('login.html'); return; }
      // simple place order flow: convert cart to booking, mark seats in occupiedMap, clear cart
      const userId = state.currentUser ? state.currentUser.id : null;
      const name = state.currentUser ? state.currentUser.name : 'Guest';
      const email = state.currentUser ? state.currentUser.email : '';
      const id = uid('bk');
      const createdAt = Date.now();
      const items = state.cart.map(it => ({ movieId: it.movieId, title: it.title, seats: it.seats.slice(), date: it.date, time: it.time, cinema: it.cinema, price: it.seatPrice }));
      const total = state.cart.reduce((s, it) => s + it.total, 0);
  // Confirm immediately upon place order
  const status = 'Paid';
      const booking = { id, createdAt, items, total, name, email, phone: '', reference: '', userId, status };
      // mark occupied
      items.forEach(it => {
        const sId = sessionId(it.movieId, it.date, it.time, it.cinema);
        if(!state.occupiedMap[sId]) state.occupiedMap[sId] = new Set();
        it.seats.forEach(s => state.occupiedMap[sId].add(s));
      });
      state.bookings.push(booking);
      state.cart = [];
      persist();
      renderCart();
      renderUserBookings();
      renderAdminBookings();
      renderAdminKPIs();
      if(paymentSection) paymentSection.style.display = 'none';
      alert('Booking confirmed — thank you!');
      renderSeatMap();
    });
  }

  // initial
  refreshShowtimes();
  renderCart();
}

// ---------- Bookings rendering & admin actions ----------
function renderUserBookings(){
  if(!bookingListEl) return;
  bookingListEl.innerHTML = '';
  const userId = state.currentUser ? state.currentUser.id : null;
  const userBookings = userId ? state.bookings.filter(b => b.userId === userId) : [];
  if(userBookings.length === 0){
    bookingListEl.innerHTML = '<li class="muted">No bookings yet.</li>';
    return;
  }
  userBookings.forEach(b => {
    const li = document.createElement('li');
    li.className = 'admin-item';
    li.innerHTML = `<div>
      <strong>${b.items[0].title}</strong>
      <small>${new Date(b.createdAt).toLocaleString()}</small>
      <div class="cart__meta">${b.items[0].date} @ ${b.items[0].time} — ${b.items[0].cinema}</div>
      <div class="cart__seats">Seats: ${b.items[0].seats.join(', ')}</div>
      <div class="cart__meta">Status: ${b.status || 'Paid'}</div>
    </div>
    <div><div class="cart__price">₱${peso(b.total)}</div></div>`;
    bookingListEl.appendChild(li);
  });
}

function renderAdminBookings(){
  if(!adminBookingList) return;
  adminBookingList.innerHTML = '';
  if(state.bookings.length === 0){
    adminBookingList.innerHTML = '<li class="muted">No bookings</li>';
    return;
  }
  state.bookings.slice().reverse().forEach(b => {
    const li = document.createElement('li');
    li.className = 'admin-item';
    const seats = b.items.map(it => it.seats.join(', ')).join(' | ');
    li.innerHTML = `<div>
      <strong>${b.name || 'Guest'}</strong>
      <small>${new Date(b.createdAt).toLocaleString()}</small>
      <div class="admin-item-meta">${b.items.map(it => it.title).join(', ')}</div>
      <small class="muted">${b.email || ''} — ₱${peso(b.total)} — Status: ${b.status || 'Paid'}</small>
    </div>
    <div>
      <button class="btn btn--ghost admin-cancel" data-id="${b.id}" ${b.status === 'Cancelled' ? 'disabled' : ''}>Cancel</button>
    </div>`;
    adminBookingList.appendChild(li);
  });
  // attach cancel handlers
  adminBookingList.querySelectorAll('.admin-cancel').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.dataset.id;
      cancelBooking(id);
    });
  });
}

function cancelBooking(id){
  const idx = state.bookings.findIndex(b => b.id === id);
  if(idx === -1) return;
  const booking = state.bookings[idx];
  // free seats
  booking.items.forEach(it => {
    const sId = sessionId(it.movieId, it.date, it.time, it.cinema);
    const set = state.occupiedMap[sId];
    if(set){
      it.seats.forEach(s => set.delete(s));
      // if empty, remove key
      if(set.size === 0) delete state.occupiedMap[sId];
    }
  });
  // mark as cancelled (keep record for KPIs)
  state.bookings[idx] = { ...booking, status: 'Cancelled' };
  persist();
  renderAdminBookings();
  renderUserBookings();
  renderAdminKPIs();
}

// ---------- Admin: KPIs and Movies Management ----------
function renderAdminKPIs(){
  if(kpiMovies) kpiMovies.textContent = state.movies.length;
  if(kpiBookings) kpiBookings.textContent = state.bookings.length;
  const statusCounts = state.bookings.reduce((acc, b) => { const s = b.status || 'Paid'; acc[s] = (acc[s]||0)+1; return acc; }, {});
  if(kpiPaid) kpiPaid.textContent = statusCounts['Paid'] || 0;
  if(kpiPending) kpiPending.textContent = statusCounts['Pending'] || 0;
  if(kpiCancelled) kpiCancelled.textContent = statusCounts['Cancelled'] || 0;
}

function renderAdminMovies(){
  if(!adminMovieList) return;
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

function initAdminPage(){
  // Guard: only admins may view this page
  if(!state.currentUser || state.currentUser.role !== 'admin'){
    location.assign('login.html');
    return;
  }
  renderAdminKPIs();
  renderAdminMovies();
  renderAdminBookings();
  // Reset Demo Data button
  const resetBtn = document.getElementById('resetDemoBtn');
  if(resetBtn){
    resetBtn.addEventListener('click', () => {
      const ok = confirm('Reset all bookings and seat reservations? This cannot be undone.');
      if(!ok) return;
      state.bookings = [];
      state.occupiedMap = {};
      state.cart = [];
      persist();
      renderAdminBookings();
      renderAdminKPIs();
      alert('Demo data has been reset.');
    });
  }
  if(movieForm){
    movieForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if(movieErr) movieErr.textContent = '';
      const title = mvTitle ? mvTitle.value.trim() : '';
      const price = mvPrice ? Number(mvPrice.value) : 0;
      const rating = document.getElementById('mvRating') ? Number(document.getElementById('mvRating').value) : 0;
      const poster = mvPoster ? mvPoster.value.trim() : '';
      const timesStr = mvTimes ? mvTimes.value.trim() : '';
      if(!title || !price || !timesStr){ if(movieErr) movieErr.textContent = 'Title, price, and showtimes are required'; return; }
      const showtimes = timesStr.split(',').map(s => s.trim()).filter(Boolean);
      const movie = { id: uid('mv'), title, price, poster: poster || 'https://picsum.photos/seed/' + encodeURIComponent(title) + '/400/600', showtimes, rating: rating || 0 };
      state.movies.push(movie);
      persist();
      renderAdminMovies();
      renderAdminKPIs();
      // clear form
      if(mvTitle) mvTitle.value = '';
      if(mvPrice) mvPrice.value = '';
      if(mvPoster) mvPoster.value = '';
      if(mvTimes) mvTimes.value = '';
      alert('Movie added. Clients will see it in Booking page.');
    });
  }
}

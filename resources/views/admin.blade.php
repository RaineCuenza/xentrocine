@extends('layouts.app')

@section('title', 'Admin — XentroCine')
@section('page', 'admin')

@section('content')
<h1>Admin Dashboard</h1>
<p class="admin-intro">Monitor cinema performance and keep your catalogue up to date.</p>
<div class="admin-kpis">
  <div class="kpi kpi--movies">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="16" rx="2.5" />
        <line x1="9" y1="4" x2="9" y2="20" />
        <line x1="15" y1="4" x2="15" y2="20" />
        <line x1="3" y1="10" x2="21" y2="10" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Total Movies</span>
      <span class="kpi__value">{{ $kpis['movies'] }}</span>
      <span class="kpi__hint">Active titles</span>
    </div>
  </div>
  <div class="kpi kpi--bookings">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 6h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4V6z" />
        <path d="M12 10h4" />
        <path d="M12 14h4" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Total Bookings</span>
      <span class="kpi__value">{{ $kpis['bookings'] }}</span>
      <span class="kpi__hint">Reservations recorded</span>
    </div>
  </div>
  <div class="kpi kpi--paid">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 6.5a8 8 0 1 1-8-4.5" />
        <polyline points="20 2 20 6.5 15.5 6.5" />
        <polyline points="9.5 12 11.5 14 15 10.5" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Paid</span>
      <span class="kpi__value">{{ $kpis['paid'] }}</span>
      <span class="kpi__hint">Settled payments</span>
    </div>
  </div>
  <div class="kpi kpi--pending">
    <div class="kpi__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="8" />
        <polyline points="12 8 12 12 15 13.5" />
      </svg>
    </div>
    <div class="kpi__meta">
      <span class="kpi__label">Pending</span>
      <span class="kpi__value">{{ $kpis['pending'] }}</span>
      <span class="kpi__hint">Awaiting payment</span>
    </div>
  </div>
</div>

<div class="admin-grid">
  <section class="admin-panel">
    <header class="admin-panel__header">
      <div>
        <h2>Manage Movies</h2>
        <p class="admin-panel__hint">Create, update, or retire titles across your catalogue.</p>
      </div>
      <span class="admin-panel__tag">{{ $movies->count() }} active</span>
    </header>
    <div class="admin-panel__body">
      <form action="{{ url('/admin/movies') }}" method="POST" class="admin-form" novalidate>
        @csrf
        <div class="admin-form__grid">
          <label class="admin-field">
            <span class="admin-field__label">Title</span>
            <input name="title" type="text" placeholder="e.g., Hidden Tide" required/>
          </label>
          <label class="admin-field admin-field--compact">
            <span class="admin-field__label">Price (₱)</span>
            <div class="admin-price-control" role="group" aria-label="Adjust ticket price">
              <button type="button" class="admin-price-btn" data-dir="down" aria-label="Decrease price"><span aria-hidden="true">−</span></button>
              <input id="priceInput" name="price" type="number" min="100" step="10" value="{{ old('price', 350) }}" placeholder="350" required/>
              <button type="button" class="admin-price-btn" data-dir="up" aria-label="Increase price"><span aria-hidden="true">+</span></button>
            </div>
          </label>
          <label class="admin-field">
            <span class="admin-field__label">Audience</span>
            <select name="audience" required>
              <option value="PG" selected>PG</option>
              <option value="G">G</option>
              <option value="R-13">R-13</option>
              <option value="R-16">R-16</option>
              <option value="R-18">R-18</option>
            </select>
          </label>
          <label class="admin-field">
            <span class="admin-field__label">Poster</span>
            <input name="poster" type="text" placeholder="/images/movie.jpg or full URL" />
          </label>
          <div class="admin-field admin-field--wide">
            <span class="admin-field__label">Showtimes</span>
            <div class="showtime-builder">
              <div class="showtime-presets">
                <button type="button" class="preset-btn" data-times="09:00,12:00">Morning</button>
                <button type="button" class="preset-btn" data-times="13:00,15:30">Matinee</button>
                <button type="button" class="preset-btn" data-times="18:00,20:30">Evening</button>
                <button type="button" class="preset-btn" data-times="22:00,23:30">Late Night</button>
              </div>
              <div class="showtime-input-group">
                <input type="text" id="customTimeInput" placeholder="Add custom (e.g., 3:00 PM or 15:00)" />
                <button type="button" id="addTimeBtn" class="btn btn--sm btn--ghost">+ Add</button>
              </div>
              <div id="showtimeChips" class="showtime-chips"></div>
              <input name="times" type="hidden" id="hiddenTimesInput" required/>
              <p class="showtime-hint">Click presets or add custom times. Times will auto-sort.</p>
            </div>
          </div>
        </div>
        <div class="admin-form__actions">
          <button class="btn btn--primary" type="submit">Add Movie</button>
        </div>
      </form>
      
      <ul id="adminMovieList" class="admin-collection">
        @forelse($movies as $movie)
          <li class="admin-collection__item">
            <div class="admin-collection__meta">
              <h3 class="admin-collection__title">{{ $movie->title }}</h3>
              <p class="admin-collection__subtitle">
                ₱{{ number_format($movie->price, 2) }} • {{ $movie->audience }} • {{ $movie->booking_items_count ?? 0 }} bookings
              </p>
            </div>
            <form action="{{ url('/admin/movies/'.$movie->id.'/delete') }}" method="POST" class="admin-collection__action">
              @csrf
              <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
            </form>
          </li>
        @empty
          <li class="admin-collection__empty">No movies yet</li>
        @endforelse
      </ul>
    </div>
  </section>

  <section class="admin-panel">
    <header class="admin-panel__header">
      <div>
        <h2>All Bookings</h2>
        <p class="admin-panel__hint">Recent reservations with current payment status.</p>
      </div>
      <span class="admin-panel__tag">{{ $bookings->count() }} on record</span>
    </header>
    <div class="admin-panel__body">
      <ul id="adminBookingList" class="booking-list booking-list--dense">
        @forelse($bookings as $booking)
          <li class="booking-item booking-item--admin">
            <div class="booking-item__header">
              <div>
                <strong class="booking-item__title">{{ $booking->name }}</strong>
                <span class="booking-item__subtitle">{{ $booking->email }}</span>
              </div>
              <span class="badge badge--{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
            </div>
            @foreach($booking->items as $item)
              <div class="booking-item__detail">
                <span>{{ $item->movie_title }}</span>
                <span>{{ \Carbon\Carbon::parse($item->booking_date)->format('M d, Y') }}</span>
                <span>{{ date('g:i A', strtotime($item->showtime)) }}</span>
                <span>{{ $item->cinema }}</span>
                <span>{{ $item->quantity }} pax</span>
              </div>
            @endforeach
            <div class="booking-item__footer">
              <span class="booking-item__total">Total: ₱{{ number_format($booking->total, 2) }}</span>
              <span class="booking-item__timestamp">{{ $booking->created_at->format('M d, Y g:i A') }}</span>
            </div>
          </li>
        @empty
          <li class="booking-item booking-item--empty">No bookings yet</li>
        @endforelse
      </ul>
    </div>
  </section>
</div>
@endsection

@push('styles')
<style>
    .showtime-builder {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .showtime-presets {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .preset-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #334155;
        background: #1e293b;
        color: #cbd5e1;
        border-radius: 0.375rem;
        cursor: pointer;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    
    .preset-btn:hover {
        background: #334155;
        border-color: #475569;
    }
    
    .preset-btn:active {
        transform: scale(0.95);
    }
    
    .showtime-input-group {
        display: flex;
        gap: 0.5rem;
    }
    
    .showtime-input-group input {
        flex: 1;
        padding: 0.5rem 0.75rem;
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 0.375rem;
        color: #e2e8f0;
        font-size: 0.875rem;
    }
    
    .showtime-input-group input:focus {
        outline: none;
        border-color: #6366f1;
    }
    
    .showtime-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 2.5rem;
        padding: 0.5rem;
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 0.375rem;
        pointer-events: none;
    }
    
    .showtime-chips:empty::before {
        content: 'No showtimes added yet';
        color: #64748b;
        font-size: 0.875rem;
        font-style: italic;
    }
    
    .time-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        animation: chipIn 0.2s ease-out;
        pointer-events: auto;
    }
    
    @keyframes chipIn {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .time-chip button {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0;
        width: 1.25rem;
        height: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
        font-size: 1.25rem;
        line-height: 1;
    }
    
    .time-chip button:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .time-chip button:active {
        transform: scale(0.9);
    }
    
    .showtime-hint {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0;
    }
</style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const priceInput = document.getElementById('priceInput');
            const priceButtons = document.querySelectorAll('.admin-price-btn');

            // Price adjustment buttons
            function adjustPrice(button){
                if(!priceInput) return;
                const direction = button.getAttribute('data-dir') === 'up' ? 1 : -1;
                const step = Number(priceInput.step || 10);
                const min = Number(priceInput.min || 0);
                const max = priceInput.max ? Number(priceInput.max) : null;
                const current = Number(priceInput.value || min || 0);
                let next = current + (direction * step);
                if(!isFinite(next)) next = min || 0;
                if(max !== null && isFinite(max)) next = Math.min(max, next);
                next = Math.max(min, next);
                const hasDecimals = step % 1 !== 0;
                priceInput.value = hasDecimals ? next.toFixed(2) : String(Math.round(next));
                priceInput.dispatchEvent(new Event('input', { bubbles: true }));
                priceInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            priceButtons.forEach(btn => {
                btn.addEventListener('click', () => adjustPrice(btn));
            });

            // Showtime Chip Builder
            const customTimeInput = document.getElementById('customTimeInput');
            const addTimeBtn = document.getElementById('addTimeBtn');
            const showtimeChips = document.getElementById('showtimeChips');
            const hiddenTimesInput = document.getElementById('hiddenTimesInput');
            const presetButtons = document.querySelectorAll('.preset-btn');

            let selectedTimes = new Set();

            // Parse time string to 24-hour format
            function parseTime(timeStr) {
                timeStr = timeStr.trim();
                if (!timeStr) return null;

                // Match HH:MM format (24-hour)
                let match = timeStr.match(/^(\d{1,2}):(\d{2})$/);
                if (match) {
                    let hours = parseInt(match[1]);
                    let minutes = parseInt(match[2]);
                    if (hours >= 0 && hours < 24 && minutes >= 0 && minutes < 60) {
                        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                    }
                }

                // Match H:MM or HH:MM with AM/PM
                match = timeStr.match(/^(\d{1,2}):(\d{2})\s*(am|pm)$/i);
                if (match) {
                    let hours = parseInt(match[1]);
                    let minutes = parseInt(match[2]);
                    let period = match[3].toLowerCase();
                    
                    if (hours < 1 || hours > 12 || minutes < 0 || minutes >= 60) return null;
                    
                    if (period === 'pm' && hours !== 12) hours += 12;
                    if (period === 'am' && hours === 12) hours = 0;
                    
                    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                }

                return null;
            }

            // Convert 24-hour to 12-hour format for display
            function formatTime12Hour(time24) {
                const [hours24, minutes] = time24.split(':').map(Number);
                const period = hours24 >= 12 ? 'PM' : 'AM';
                const hours12 = hours24 % 12 || 12;
                return `${hours12}:${String(minutes).padStart(2, '0')} ${period}`;
            }

            // Update chips display
            function updateChips() {
                showtimeChips.innerHTML = '';
                const sortedTimes = Array.from(selectedTimes).sort();
                
                sortedTimes.forEach(time => {
                    const chip = document.createElement('div');
                    chip.className = 'time-chip';
                    
                    const timeSpan = document.createElement('span');
                    timeSpan.textContent = formatTime12Hour(time);
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'remove-time-btn';
                    removeBtn.setAttribute('aria-label', `Remove ${time}`);
                    removeBtn.setAttribute('data-time', time);
                    removeBtn.textContent = '×';
                    removeBtn.onclick = function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        selectedTimes.delete(time);
                        updateChips();
                        updateHiddenInput();
                    };
                    
                    chip.appendChild(timeSpan);
                    chip.appendChild(removeBtn);
                    showtimeChips.appendChild(chip);
                });
            }

            // Update hidden input for form submission
            function updateHiddenInput() {
                const sortedTimes = Array.from(selectedTimes).sort();
                hiddenTimesInput.value = sortedTimes.join(', ');
            }

            // Add time(s) to selection
            function addTimes(timesString) {
                const times = timesString.split(',').map(t => t.trim()).filter(Boolean);
                let hasInvalid = false;
                
                times.forEach(timeStr => {
                    const parsed = parseTime(timeStr);
                    if (parsed) {
                        selectedTimes.add(parsed);
                    } else {
                        hasInvalid = true;
                    }
                });

                if (hasInvalid) {
                    alert('Invalid time format. Use 24-hour (15:00) or 12-hour (3:00 PM) format.');
                }

                updateChips();
                updateHiddenInput();
            }

            // Preset buttons
            presetButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const times = btn.getAttribute('data-times');
                    addTimes(times);
                    
                    // Visual feedback
                    btn.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        btn.style.transform = '';
                    }, 100);
                });
            });

            // Add custom time button
            addTimeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const value = customTimeInput.value.trim();
                if (value) {
                    addTimes(value);
                    customTimeInput.value = '';
                    customTimeInput.focus();
                }
            });

            // Enter key on custom input
            customTimeInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addTimeBtn.click();
                }
            });

            // Initialize with existing times if editing
            const existingTimes = hiddenTimesInput.value;
            if (existingTimes) {
                addTimes(existingTimes);
            }

        });
    </script>
@endpush

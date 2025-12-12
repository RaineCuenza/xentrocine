@extends('layouts.app')

@section('title', 'My Bookings - XentroCine')
@section('page', 'my-bookings')

@section('content')
<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;">My Bookings</h1>
        <p class="muted">View and manage your movie reservations</p>
    </div>

    @if($bookings->isEmpty())
        <div class="empty-state" style="text-align: center; padding: 4rem 2rem; background: #f9fafb; border-radius: 12px;">
            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🎬</div>
            <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #374151;">No bookings yet</h2>
            <p class="muted" style="margin-bottom: 2rem;">Start by booking your first movie!</p>
            <a href="{{ route('booking') }}" class="btn btn--primary">Book Now</a>
        </div>
    @else
        <div class="bookings-list">
            @foreach($bookings as $booking)
                <article class="booking-card" style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid {{ $booking->status === 'Paid' ? '#10b981' : ($booking->status === 'Pending' ? '#f59e0b' : '#ef4444') }};">
                    <div class="booking-card__header" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div class="booking-reference" style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">
                                Ref: <strong>{{ $booking->reference }}</strong>
                            </div>
                            <div class="booking-date" style="font-size: 0.875rem; color: #9ca3af;">
                                Booked: {{ $booking->created_at->format('M d, Y h:i A') }}
                            </div>
                        </div>
                        <div>
                            <span class="status-badge status-badge--{{ strtolower($booking->status) }}" style="display: inline-block; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;">
                                {{ $booking->status }}
                            </span>
                        </div>
                    </div>

                    <div class="booking-card__body">
                        @foreach($booking->items as $item)
                            <div class="booking-item" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; padding: 1rem 0; @if(!$loop->last) border-bottom: 1px solid #e5e7eb; @endif">
                                <div>
                                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">Movie</div>
                                    <div style="font-weight: 600; color: #1f2937;">{{ $item->movie_title }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">Date & Time</div>
                                    <div style="font-weight: 500; color: #374151;">
                                        {{ \Carbon\Carbon::parse($item->booking_date)->format('M d, Y') }}<br>
                                        <span style="font-size: 0.875rem;">{{ date('h:i A', strtotime($item->showtime)) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">Cinema</div>
                                    <div style="font-weight: 500; color: #374151;">{{ $item->cinema }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">Quantity</div>
                                    <div style="font-weight: 500; color: #374151;">{{ $item->quantity }} pax</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">Price</div>
                                    <div style="font-weight: 600; color: #1f2937;">₱{{ number_format($item->price, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="booking-card__footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Amount</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">₱{{ number_format($booking->total, 2) }}</div>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            @if($booking->status === 'Paid' && $booking->entry_scanned_at)
                                <div style="padding: 0.5rem 1rem; background: #d1fae5; color: #065f46; border-radius: 8px; font-size: 0.875rem; font-weight: 600;">
                                    ✓ Checked In
                                </div>
                            @elseif($booking->status === 'Paid')
                                <a href="{{ route('qr-test') }}?booking={{ $booking->id }}" class="btn btn--ghost">View QR Code</a>
                            @elseif($booking->status === 'Pending')
                                <div style="padding: 0.5rem 1rem; background: #fef3c7; color: #92400e; border-radius: 8px; font-size: 0.875rem; font-weight: 600;">
                                    ⏳ Pay at Cinema
                                </div>
                            @endif
                            
                            @if($booking->status !== 'Cancelled' && !$booking->entry_scanned_at)
                                <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn--ghost" style="color: #ef4444;">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($booking->entry_scanned_at)
                        <div style="margin-top: 1rem; padding: 0.75rem; background: #f9fafb; border-radius: 8px; font-size: 0.875rem; color: #6b7280;">
                            <strong>Entry:</strong> Scanned at {{ $booking->entry_scanned_at->format('M d, Y h:i A') }}
                            @if($booking->checkedInBy)
                                by {{ $booking->checkedInBy->name }}
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <!-- Pagination if needed -->
        @if($bookings->hasPages())
            <div class="pagination-wrapper" style="margin-top: 2rem;">
                {{ $bookings->links() }}
            </div>
        @endif
    @endif
</div>

<style>
.status-badge--paid {
    background: #d1fae5;
    color: #065f46;
}

.status-badge--pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge--cancelled {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 640px) {
    .booking-item {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endsection

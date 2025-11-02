<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $firstItem = $this->booking->items->first();
        $movieTitle = $firstItem ? $firstItem->movie_title : 'Movie';
    $date = $firstItem ? \Illuminate\Support\Carbon::parse($firstItem->booking_date)->format('M d') : '';
        $time = $firstItem ? date('g:i A', strtotime($firstItem->showtime)) : '';
        
        return new Envelope(
            subject: "Your XentroCine E-Ticket • {$movieTitle} • {$date} {$time}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $qr = $this->generateQrCode();

        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'booking' => $this->booking,
                'qrCode' => $qr['base64'],
                'qrMime' => $qr['mime'],
            ],
        );
    }

    /**
     * Generate QR code as base64 string.
     * Prefer PNG (requires Imagick). Fallback to SVG when Imagick is unavailable
     * so development can proceed without native dependencies.
     */
    protected function generateQrCode(): array
    {
        $usePng = extension_loaded('imagick');

        // Generate QR code with the entry token
        $qrImage = QrCode::format($usePng ? 'png' : 'svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($this->booking->et_qr_token);

        return [
            'base64' => base64_encode($qrImage),
            'mime' => $usePng ? 'image/png' : 'image/svg+xml',
        ];
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

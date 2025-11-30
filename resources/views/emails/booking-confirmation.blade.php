<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XentroCine E-Ticket</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #e74c3c;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #e74c3c;
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        .status-paid {
            background-color: #27ae60;
            color: white;
        }
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .qr-section img {
            max-width: 300px;
            height: auto;
            border: 4px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .qr-note {
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            font-style: italic;
        }
        .details-section {
            margin: 30px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        .detail-value {
            color: #333;
            text-align: right;
        }
        .movie-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .seats-list {
            display: inline-block;
            background-color: #ecf0f1;
            padding: 4px 12px;
            border-radius: 4px;
            margin: 2px;
            font-size: 13px;
            font-weight: 600;
        }
        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #e74c3c;
        }
        .instructions {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .instructions h3 {
            margin-top: 0;
            color: #856404;
            font-size: 16px;
        }
        .instructions p {
            margin: 5px 0;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 XentroCine</h1>
            <p style="margin: 5px 0; color: #666;">Your E-Ticket is Ready!</p>
            @if($booking->status === 'Paid')
                <span class="status-badge status-paid">✓ PAID</span>
            @else
                <span class="status-badge status-pending">⏳ PENDING (Pay at Cinema)</span>
            @endif
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
            <img src="data:{{ $qrMime ?? 'image/png' }};base64,{{ $qrCode }}" alt="E-Ticket QR Code">
            <p class="qr-note">
                @if($booking->status === 'Paid')
                    Present this QR code at the cinema entrance for instant check-in.
                @else
                    Present this QR code at the cinema entrance. Staff will accept your cash payment and check you in.
                @endif
            </p>
            @if(app()->environment('local'))
                <p class="qr-note" style="margin-top:8px;color:#999">
                    Dev only: Token {{ $booking->et_qr_token }}
                </p>
            @endif
        </div>

        <!-- Booking Details -->
        <div class="details-section">
            @php
                $firstItem = $booking->items->first();
            @endphp
            
            @if($firstItem)
                <div class="movie-title">{{ $firstItem->movie_title }}</div>
                
                <div class="detail-row">
                    <span class="detail-label">Booking Reference</span>
                    <span class="detail-value">{{ $booking->id }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Date</span>
                    <span class="detail-value">{{ $firstItem->booking_date->format('l, F d, Y') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Showtime</span>
                    <span class="detail-value">{{ date('g:i A', strtotime($firstItem->showtime)) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Cinema</span>
                    <span class="detail-value">{{ $firstItem->cinema }}</span>
                </div>
                
                @php
                    $pax = (int) $booking->items->sum('quantity');
                @endphp
                <div class="detail-row">
                    <span class="detail-label">Pax</span>
                    <span class="detail-value">{{ $pax }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Customer</span>
                    <span class="detail-value">{{ $booking->name }}</span>
                </div>
                
                <div class="detail-row" style="border-top: 2px solid #e74c3c; margin-top: 10px; padding-top: 15px;">
                    <span class="detail-label" style="font-size: 18px;">Total Amount</span>
                    <span class="detail-value total-amount">₱{{ number_format($booking->total, 2) }}</span>
                </div>
            @endif
        </div>

        <!-- Instructions -->
        @if($booking->status === 'Paid')
            <div class="instructions">
                <h3>📱 Check-in Instructions</h3>
                <p>• Show this QR code to staff at the entrance</p>
                <p>• Staff will scan and verify your booking</p>
                <p>• You'll be checked in instantly</p>
                <p>• Arrive 10-15 minutes before showtime</p>
            </div>
        @else
            <div class="instructions">
                <h3>💰 Payment & Check-in Instructions</h3>
                <p>• Bring exact cash amount: <strong>₱{{ number_format($booking->total, 2) }}</strong></p>
                <p>• Show this QR code to staff at the entrance</p>
                <p>• Staff will scan, accept your payment, and check you in</p>
                <p>• Arrive 15-20 minutes before showtime</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for choosing XentroCine!</p>
            <p>Questions? Contact us at support@xentrocine.local</p>
            <p style="margin-top: 10px; font-size: 11px;">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>
</html>

// Bookings Routes
const express = require('express');
const router = express.Router();
const db = require('../config/database');

// Helper function to generate unique IDs
function generateId(prefix = 'bk') {
  const random = Math.random().toString(36).slice(2, 8);
  const timestamp = Date.now().toString(36).slice(-4);
  return `${prefix}_${random}${timestamp}`;
}

// Middleware to check authentication
function requireAuth(req, res, next) {
  if (!req.session.user) {
    return res.status(401).json({ error: 'Authentication required' });
  }
  next();
}

// Middleware to check if user is admin
function requireAdmin(req, res, next) {
  if (!req.session.user || req.session.user.role !== 'admin') {
    return res.status(403).json({ error: 'Admin access required' });
  }
  next();
}

// GET /api/bookings - Get user's bookings or all bookings (admin)
router.get('/', requireAuth, async (req, res) => {
  try {
    let query;
    let params;

    if (req.session.user.role === 'admin') {
      // Admin sees all bookings
      query = `
        SELECT b.*, 
               GROUP_CONCAT(DISTINCT bi.movie_title) as movies,
               GROUP_CONCAT(DISTINCT bi.seat_key ORDER BY bi.seat_key) as seats
        FROM bookings b
        LEFT JOIN booking_items bi ON b.id = bi.booking_id
        GROUP BY b.id
        ORDER BY b.created_at DESC
      `;
      params = [];
    } else {
      // Users see only their bookings
      query = `
        SELECT b.*, 
               GROUP_CONCAT(DISTINCT bi.movie_title) as movies,
               GROUP_CONCAT(DISTINCT bi.seat_key ORDER BY bi.seat_key) as seats
        FROM bookings b
        LEFT JOIN booking_items bi ON b.id = bi.booking_id
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.created_at DESC
      `;
      params = [req.session.user.id];
    }

    const [bookings] = await db.query(query, params);

    // Get detailed items for each booking
    const bookingsWithItems = await Promise.all(
      bookings.map(async (booking) => {
        const [items] = await db.query(
          `SELECT movie_id, movie_title, booking_date, 
                  TIME_FORMAT(showtime, '%H:%i') as showtime,
                  cinema, seat_key, price 
           FROM booking_items 
           WHERE booking_id = ?
           ORDER BY seat_key`,
          [booking.id]
        );

        return {
          ...booking,
          total: parseFloat(booking.total),
          items: items.map(item => ({
            ...item,
            price: parseFloat(item.price),
            date: item.booking_date,
            time: item.showtime,
            seats: [item.seat_key]
          }))
        };
      })
    );

    res.json(bookingsWithItems);
  } catch (error) {
    console.error('Get bookings error:', error);
    res.status(500).json({ error: 'Failed to fetch bookings' });
  }
});

// POST /api/bookings - Create new booking
router.post('/', requireAuth, async (req, res) => {
  const connection = await db.getConnection();
  
  try {
    await connection.beginTransaction();

    const { items } = req.body;

    if (!items || !Array.isArray(items) || items.length === 0) {
      await connection.rollback();
      return res.status(400).json({ error: 'Booking items are required' });
    }

    const bookingId = generateId('bk');
    const userId = req.session.user.id;
    const userName = req.session.user.name;
    const userEmail = req.session.user.email;

    // Calculate total
    let total = 0;
    const bookingItems = [];

    for (const item of items) {
      const { movieId, title, date, time, cinema, seats, seatPrice } = item;

      if (!movieId || !title || !date || !time || !cinema || !seats || !Array.isArray(seats)) {
        await connection.rollback();
        return res.status(400).json({ error: 'Invalid booking item data' });
      }

      // Check if seats are already occupied
      for (const seatKey of seats) {
        const [row, col] = seatKey.split('-').map(Number);
        
        const [existing] = await connection.query(
          `SELECT id FROM booking_items 
           WHERE movie_id = ? AND booking_date = ? AND showtime = ? 
           AND cinema = ? AND seat_key = ?
           AND booking_id IN (SELECT id FROM bookings WHERE status != 'Cancelled')`,
          [movieId, date, time, cinema, seatKey]
        );

        if (existing.length > 0) {
          await connection.rollback();
          return res.status(400).json({ 
            error: `Seat ${seatKey} is already reserved for this session` 
          });
        }

        bookingItems.push({
          movieId,
          title,
          date,
          time,
          cinema,
          row,
          col,
          seatKey,
          price: seatPrice
        });

        total += seatPrice;
      }
    }

    // Create booking record
    await connection.query(
      `INSERT INTO bookings (id, user_id, name, email, phone, reference, total, status) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [bookingId, userId, userName, userEmail, '', '', total, 'Paid']
    );

    // Insert booking items (seat reservations)
    for (const item of bookingItems) {
      await connection.query(
        `INSERT INTO booking_items 
         (booking_id, movie_id, movie_title, booking_date, showtime, cinema, seat_row, seat_col, seat_key, price) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [bookingId, item.movieId, item.title, item.date, item.time, item.cinema, 
         item.row, item.col, item.seatKey, item.price]
      );
    }

    await connection.commit();

    res.json({
      success: true,
      bookingId,
      message: 'Booking confirmed successfully'
    });
  } catch (error) {
    await connection.rollback();
    console.error('Create booking error:', error);
    res.status(500).json({ error: 'Failed to create booking' });
  } finally {
    connection.release();
  }
});

// PUT /api/bookings/:id/cancel - Cancel a booking
router.put('/:id/cancel', requireAuth, async (req, res) => {
  try {
    const bookingId = req.params.id;

    // Check if booking exists and belongs to user (or user is admin)
    const [bookings] = await db.query(
      'SELECT id, user_id, status FROM bookings WHERE id = ?',
      [bookingId]
    );

    if (bookings.length === 0) {
      return res.status(404).json({ error: 'Booking not found' });
    }

    const booking = bookings[0];

    if (req.session.user.role !== 'admin' && booking.user_id !== req.session.user.id) {
      return res.status(403).json({ error: 'Access denied' });
    }

    if (booking.status === 'Cancelled') {
      return res.status(400).json({ error: 'Booking already cancelled' });
    }

    // Update booking status
    await db.query(
      'UPDATE bookings SET status = ? WHERE id = ?',
      ['Cancelled', bookingId]
    );

    res.json({
      success: true,
      message: 'Booking cancelled successfully'
    });
  } catch (error) {
    console.error('Cancel booking error:', error);
    res.status(500).json({ error: 'Failed to cancel booking' });
  }
});

// DELETE /api/bookings/reset - Reset all bookings (admin only, for demo)
router.delete('/reset', requireAdmin, async (req, res) => {
  try {
    await db.query('DELETE FROM booking_items');
    await db.query('DELETE FROM bookings');

    res.json({
      success: true,
      message: 'All bookings reset successfully'
    });
  } catch (error) {
    console.error('Reset bookings error:', error);
    res.status(500).json({ error: 'Failed to reset bookings' });
  }
});

module.exports = router;

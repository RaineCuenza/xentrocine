// Seats Routes - Check seat availability
const express = require('express');
const router = express.Router();
const db = require('../config/database');

// GET /api/seats/occupied - Get occupied seats for a specific session
router.get('/occupied', async (req, res) => {
  try {
    const { movieId, date, time, cinema } = req.query;

    if (!movieId || !date || !time || !cinema) {
      return res.status(400).json({ 
        error: 'movieId, date, time, and cinema are required' 
      });
    }

    // Get occupied seats from non-cancelled bookings
    const [seats] = await db.query(
      `SELECT seat_key 
       FROM booking_items bi
       JOIN bookings b ON bi.booking_id = b.id
       WHERE bi.movie_id = ? 
       AND bi.booking_date = ? 
       AND bi.showtime = ? 
       AND bi.cinema = ?
       AND b.status != 'Cancelled'`,
      [movieId, date, time, cinema]
    );

    const occupiedSeats = seats.map(s => s.seat_key);

    res.json({
      sessionId: `${movieId}|${date}|${time}|${cinema}`,
      occupiedSeats
    });
  } catch (error) {
    console.error('Get occupied seats error:', error);
    res.status(500).json({ error: 'Failed to fetch occupied seats' });
  }
});

// POST /api/seats/check - Check if specific seats are available
router.post('/check', async (req, res) => {
  try {
    const { movieId, date, time, cinema, seats } = req.body;

    if (!movieId || !date || !time || !cinema || !seats || !Array.isArray(seats)) {
      return res.status(400).json({ 
        error: 'movieId, date, time, cinema, and seats array are required' 
      });
    }

    const unavailableSeats = [];

    for (const seatKey of seats) {
      const [existing] = await db.query(
        `SELECT id FROM booking_items bi
         JOIN bookings b ON bi.booking_id = b.id
         WHERE bi.movie_id = ? 
         AND bi.booking_date = ? 
         AND bi.showtime = ? 
         AND bi.cinema = ?
         AND bi.seat_key = ?
         AND b.status != 'Cancelled'`,
        [movieId, date, time, cinema, seatKey]
      );

      if (existing.length > 0) {
        unavailableSeats.push(seatKey);
      }
    }

    res.json({
      available: unavailableSeats.length === 0,
      unavailableSeats
    });
  } catch (error) {
    console.error('Check seats error:', error);
    res.status(500).json({ error: 'Failed to check seat availability' });
  }
});

module.exports = router;

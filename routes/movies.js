// Movies Routes
const express = require('express');
const router = express.Router();
const db = require('../config/database');

// Helper function to generate unique IDs
function generateId(prefix = 'mv') {
  const random = Math.random().toString(36).slice(2, 8);
  const timestamp = Date.now().toString(36).slice(-4);
  return `${prefix}_${random}${timestamp}`;
}

// Middleware to check if user is admin
function requireAdmin(req, res, next) {
  if (!req.session.user || req.session.user.role !== 'admin') {
    return res.status(403).json({ error: 'Admin access required' });
  }
  next();
}

// GET /api/movies - Get all movies with showtimes
router.get('/', async (req, res) => {
  try {
    const [movies] = await db.query('SELECT * FROM movies ORDER BY title');
    
    // Get showtimes for each movie
    const moviesWithShowtimes = await Promise.all(
      movies.map(async (movie) => {
        const [showtimes] = await db.query(
          'SELECT TIME_FORMAT(showtime, "%H:%i") as showtime FROM showtimes WHERE movie_id = ? ORDER BY showtime',
          [movie.id]
        );
        return {
          ...movie,
          price: parseFloat(movie.price),
          rating: parseFloat(movie.rating),
          showtimes: showtimes.map(s => s.showtime)
        };
      })
    );

    res.json(moviesWithShowtimes);
  } catch (error) {
    console.error('Get movies error:', error);
    res.status(500).json({ error: 'Failed to fetch movies' });
  }
});

// GET /api/movies/:id - Get single movie
router.get('/:id', async (req, res) => {
  try {
    const [movies] = await db.query('SELECT * FROM movies WHERE id = ?', [req.params.id]);
    
    if (movies.length === 0) {
      return res.status(404).json({ error: 'Movie not found' });
    }

    const movie = movies[0];
    const [showtimes] = await db.query(
      'SELECT TIME_FORMAT(showtime, "%H:%i") as showtime FROM showtimes WHERE movie_id = ? ORDER BY showtime',
      [movie.id]
    );

    res.json({
      ...movie,
      price: parseFloat(movie.price),
      rating: parseFloat(movie.rating),
      showtimes: showtimes.map(s => s.showtime)
    });
  } catch (error) {
    console.error('Get movie error:', error);
    res.status(500).json({ error: 'Failed to fetch movie' });
  }
});

// POST /api/movies - Create new movie (admin only)
router.post('/', requireAdmin, async (req, res) => {
  try {
    const { title, price, poster, rating, showtimes } = req.body;

    // Validation
    if (!title || !price || !showtimes || !Array.isArray(showtimes) || showtimes.length === 0) {
      return res.status(400).json({ error: 'Title, price, and showtimes are required' });
    }

    const movieId = generateId('mv');

    // Insert movie
    await db.query(
      'INSERT INTO movies (id, title, price, poster, rating) VALUES (?, ?, ?, ?, ?)',
      [movieId, title.trim(), price, poster || null, rating || 0]
    );

    // Insert showtimes
    for (const time of showtimes) {
      await db.query(
        'INSERT INTO showtimes (movie_id, showtime) VALUES (?, ?)',
        [movieId, time]
      );
    }

    res.json({
      success: true,
      id: movieId,
      message: 'Movie created successfully'
    });
  } catch (error) {
    console.error('Create movie error:', error);
    res.status(500).json({ error: 'Failed to create movie' });
  }
});

// PUT /api/movies/:id - Update movie (admin only)
router.put('/:id', requireAdmin, async (req, res) => {
  try {
    const { title, price, poster, rating, showtimes } = req.body;
    const movieId = req.params.id;

    // Check if movie exists
    const [existing] = await db.query('SELECT id FROM movies WHERE id = ?', [movieId]);
    if (existing.length === 0) {
      return res.status(404).json({ error: 'Movie not found' });
    }

    // Update movie
    await db.query(
      'UPDATE movies SET title = ?, price = ?, poster = ?, rating = ? WHERE id = ?',
      [title.trim(), price, poster || null, rating || 0, movieId]
    );

    // Update showtimes if provided
    if (showtimes && Array.isArray(showtimes)) {
      // Delete old showtimes
      await db.query('DELETE FROM showtimes WHERE movie_id = ?', [movieId]);
      
      // Insert new showtimes
      for (const time of showtimes) {
        await db.query(
          'INSERT INTO showtimes (movie_id, showtime) VALUES (?, ?)',
          [movieId, time]
        );
      }
    }

    res.json({
      success: true,
      message: 'Movie updated successfully'
    });
  } catch (error) {
    console.error('Update movie error:', error);
    res.status(500).json({ error: 'Failed to update movie' });
  }
});

// DELETE /api/movies/:id - Delete movie (admin only)
router.delete('/:id', requireAdmin, async (req, res) => {
  try {
    const [result] = await db.query('DELETE FROM movies WHERE id = ?', [req.params.id]);
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'Movie not found' });
    }

    res.json({
      success: true,
      message: 'Movie deleted successfully'
    });
  } catch (error) {
    console.error('Delete movie error:', error);
    res.status(500).json({ error: 'Failed to delete movie' });
  }
});

module.exports = router;

// Authentication Routes
const express = require('express');
const router = express.Router();
const bcrypt = require('bcrypt');
const db = require('../config/database');

// Salt rounds for bcrypt (higher = more secure but slower)
const SALT_ROUNDS = 10;

// Helper function to generate unique IDs
function generateId(prefix = 'u') {
  const random = Math.random().toString(36).slice(2, 8);
  const timestamp = Date.now().toString(36).slice(-4);
  return `${prefix}_${random}${timestamp}`;
}

// POST /api/auth/signup - Register new user
router.post('/signup', async (req, res) => {
  try {
    const { name, email, password, role } = req.body;

    // Validation
    if (!name || !email || !password) {
      return res.status(400).json({ error: 'Please fill all fields' });
    }

    const emailLower = email.toLowerCase().trim();

    // Check if user already exists
    const [existing] = await db.query('SELECT id FROM users WHERE email = ?', [emailLower]);
    if (existing.length > 0) {
      return res.status(400).json({ error: 'Email already exists' });
    }

    // Hash password before storing
    const hashedPassword = await bcrypt.hash(password, SALT_ROUNDS);

    // Create user
    const userId = generateId('u');
    const userRole = role === 'admin' ? 'admin' : 'client';

    await db.query(
      'INSERT INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, ?)',
      [userId, name.trim(), emailLower, hashedPassword, userRole]
    );

    // Create session
    req.session.user = {
      id: userId,
      name: name.trim(),
      email: emailLower,
      role: userRole
    };

    res.json({
      success: true,
      user: req.session.user
    });
  } catch (error) {
    console.error('Signup error:', error);
    res.status(500).json({ error: 'Server error during signup' });
  }
});

// POST /api/auth/login - User login
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(400).json({ error: 'Please provide email and password' });
    }

    const emailLower = email.toLowerCase().trim();

    // Find user
    const [users] = await db.query(
      'SELECT id, name, email, password, role FROM users WHERE email = ?',
      [emailLower]
    );

    if (users.length === 0) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const user = users[0];

    // Verify password using bcrypt
    const isPasswordValid = await bcrypt.compare(password, user.password);
    
    if (!isPasswordValid) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Create session
    req.session.user = {
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.role
    };

    res.json({
      success: true,
      user: req.session.user
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ error: 'Server error during login' });
  }
});

// POST /api/auth/logout - User logout
router.post('/logout', (req, res) => {
  req.session.destroy((err) => {
    if (err) {
      console.error('Logout error:', err);
      return res.status(500).json({ error: 'Failed to logout' });
    }
    res.json({ success: true });
  });
});

// GET /api/auth/session - Check current session
router.get('/session', (req, res) => {
  if (req.session.user) {
    res.json({ user: req.session.user });
  } else {
    res.json({ user: null });
  }
});

module.exports = router;

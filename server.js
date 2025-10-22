// XentroCine Backend Server
// Node.js + Express + MySQL
require('dotenv').config();
const express = require('express');
const session = require('express-session');
const MySQLStore = require('express-mysql-session')(session);
const cors = require('cors');
const path = require('path');
const db = require('./config/database');

// Import routes
const authRoutes = require('./routes/auth');
const moviesRoutes = require('./routes/movies');
const bookingsRoutes = require('./routes/bookings');
const seatsRoutes = require('./routes/seats');

// Initialize Express app
const app = express();
const PORT = process.env.PORT || 3000;

// Session store configuration
const sessionStore = new MySQLStore({
  clearExpired: true,
  checkExpirationInterval: 900000, // 15 minutes
  expiration: 86400000, // 1 day
  createDatabaseTable: true,
  schema: {
    tableName: 'sessions',
    columnNames: {
      session_id: 'session_id',
      expires: 'expires',
      data: 'data'
    }
  }
}, db);

// Middleware
app.use(cors({
  origin: ['http://localhost:3000', 'http://127.0.0.1:3000', 'http://localhost:5500', 'http://127.0.0.1:5500'],
  credentials: true
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Session configuration
app.use(session({
  key: 'xentrocine_session',
  secret: process.env.SESSION_SECRET || 'your-secret-key-change-this',
  store: sessionStore,
  resave: false,
  saveUninitialized: false,
  cookie: {
    maxAge: 1000 * 60 * 60 * 24, // 1 day
    httpOnly: true,
    secure: false // Set to true if using HTTPS
  }
}));

// Serve static files (HTML, CSS, JS, images)
app.use(express.static(path.join(__dirname)));

// API Routes
app.use('/api/auth', authRoutes);
app.use('/api/movies', moviesRoutes);
app.use('/api/bookings', bookingsRoutes);
app.use('/api/seats', seatsRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    message: 'XentroCine API is running',
    timestamp: new Date().toISOString()
  });
});

// Serve HTML pages
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'index.html'));
});

app.get('/booking', (req, res) => {
  res.sendFile(path.join(__dirname, 'booking.html'));
});

app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'login.html'));
});

app.get('/admin', (req, res) => {
  res.sendFile(path.join(__dirname, 'admin.html'));
});

app.get('/about', (req, res) => {
  res.sendFile(path.join(__dirname, 'about.html'));
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({ error: 'Endpoint not found' });
});

// Error handler
app.use((err, req, res, next) => {
  console.error('Server error:', err);
  res.status(500).json({ error: 'Internal server error' });
});

// Start server
app.listen(PORT, () => {
  console.log('');
  console.log('🎬 ================================ 🎬');
  console.log('   XentroCine Server Running!');
  console.log('🎬 ================================ 🎬');
  console.log('');
  console.log(`   🌐 Server: http://localhost:${PORT}`);
  console.log(`   📊 API:    http://localhost:${PORT}/api`);
  console.log(`   🏥 Health: http://localhost:${PORT}/api/health`);
  console.log('');
  console.log('   Press Ctrl+C to stop');
  console.log('');
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM signal received: closing server');
  server.close(() => {
    console.log('Server closed');
    process.exit(0);
  });
});

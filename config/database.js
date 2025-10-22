// Database Configuration and Connection Pool
require('dotenv').config();
const mysql = require('mysql2');

// Create connection pool for better performance
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'xentrocine_db',
  port: process.env.DB_PORT || 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Test database connection
pool.getConnection((err, connection) => {
  if (err) {
    console.error('❌ Database connection failed:', err.message);
    console.error('Please check your XAMPP MySQL server and .env configuration');
    process.exit(1);
  }
  console.log('✅ Database connected successfully');
  connection.release();
});

// Export promise-based pool for async/await usage
const promisePool = pool.promise();

module.exports = promisePool;

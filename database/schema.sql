-- XentroCine Database Schema
-- MySQL Database for Movie Booking System

-- Drop existing tables if they exist (for clean setup)
DROP TABLE IF EXISTS booking_items;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS showtimes;
DROP TABLE IF EXISTS movies;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS users;

-- Users table (clients and admins)
CREATE TABLE users (
  id VARCHAR(50) PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('client', 'admin') DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movies table
CREATE TABLE movies (
  id VARCHAR(50) PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  price DECIMAL(10, 2) NOT NULL,
  poster VARCHAR(500),
  rating DECIMAL(3, 1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Showtimes table (normalized from movies)
CREATE TABLE showtimes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  movie_id VARCHAR(50) NOT NULL,
  showtime TIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
  INDEX idx_movie_id (movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings table (main booking records)
CREATE TABLE bookings (
  id VARCHAR(50) PRIMARY KEY,
  user_id VARCHAR(50),
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  phone VARCHAR(20),
  reference VARCHAR(100),
  total DECIMAL(10, 2) NOT NULL,
  status ENUM('Pending', 'Paid', 'Cancelled') DEFAULT 'Paid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_id (user_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Booking items (individual seat reservations)
CREATE TABLE booking_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id VARCHAR(50) NOT NULL,
  movie_id VARCHAR(50) NOT NULL,
  movie_title VARCHAR(200) NOT NULL,
  booking_date DATE NOT NULL,
  showtime TIME NOT NULL,
  cinema VARCHAR(50) NOT NULL,
  seat_row INT NOT NULL,
  seat_col INT NOT NULL,
  seat_key VARCHAR(20) NOT NULL,
  price DECIMAL(10, 2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
  INDEX idx_booking_id (booking_id),
  INDEX idx_movie_id (movie_id),
  INDEX idx_session (movie_id, booking_date, showtime, cinema),
  INDEX idx_seat (seat_row, seat_col),
  UNIQUE KEY unique_seat_session (movie_id, booking_date, showtime, cinema, seat_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions table (for user authentication)
CREATE TABLE sessions (
  session_id VARCHAR(128) PRIMARY KEY,
  expires BIGINT UNSIGNED NOT NULL,
  data TEXT,
  INDEX idx_expires (expires)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

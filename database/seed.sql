-- XentroCine Seed Data
-- Initial data for testing and demo purposes

-- Insert default admin user
-- Email: admin@xentrocine.local
-- Password: Admin123! (hashed with bcrypt)
INSERT INTO users (id, name, email, password, role) VALUES
('u_admin001', 'Administrator', 'admin@xentrocine.local', '$2b$10$IszjvRbWsVnW8qw5yFhIP.gK8qlitE4dPsk0IwRBcC9VZuLJrVoO2', 'admin');

-- Insert sample client users
-- Password for both: password123 (hashed with bcrypt)
INSERT INTO users (id, name, email, password, role) VALUES
('u_client001', 'John Doe', 'john@example.com', '$2b$10$NNhROgOb.I0YC0RNZ2Qgbu6u58dowoqUAeA3/juSthYk.dRiSH7D6', 'client'),
('u_client002', 'Jane Smith', 'jane@example.com', '$2b$10$DQ0BSzRCuqhD0r1ymXsL8uPnnZD.kfimDHbYbTXmMada5zaZHZMgK', 'client');

-- Insert sample movies
INSERT INTO movies (id, title, price, poster, rating) VALUES
('mv100', 'Star Quest', 300.00, 'images/starquest.jpg', 4.5),
('mv200', 'The Last Laugh', 320.00, 'images/thelastlaugh.jpg', 4.2),
('mv300', 'Hidden Tide', 350.00, 'images/hiddentide.jpg', 4.7);

-- Insert showtimes for Star Quest
INSERT INTO showtimes (movie_id, showtime) VALUES
('mv100', '13:00:00'),
('mv100', '15:30:00'),
('mv100', '18:00:00'),
('mv100', '20:30:00');

-- Insert showtimes for The Last Laugh
INSERT INTO showtimes (movie_id, showtime) VALUES
('mv200', '12:00:00'),
('mv200', '14:15:00'),
('mv200', '17:00:00'),
('mv200', '19:45:00'),
('mv200', '21:30:00');

-- Insert showtimes for Hidden Tide
INSERT INTO showtimes (movie_id, showtime) VALUES
('mv300', '11:00:00'),
('mv300', '13:45:00'),
('mv300', '16:15:00'),
('mv300', '18:45:00');

-- Sample booking (optional - for testing)
-- INSERT INTO bookings (id, user_id, name, email, phone, reference, total, status) VALUES
-- ('bk_sample001', 'u_client001', 'John Doe', 'john@example.com', '09123456789', 'REF001', 600.00, 'Paid');

-- Sample booking items (optional - for testing)
-- INSERT INTO booking_items (booking_id, movie_id, movie_title, booking_date, showtime, cinema, seat_row, seat_col, seat_key, price) VALUES
-- ('bk_sample001', 'mv100', 'Star Quest', '2025-10-25', '13:00:00', 'Cinema 1', 5, 7, '5-7', 300.00),
-- ('bk_sample001', 'mv100', 'Star Quest', '2025-10-25', '13:00:00', 'Cinema 1', 5, 8, '5-8', 300.00);

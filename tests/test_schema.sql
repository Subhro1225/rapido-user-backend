-- tests/test_schema.sql
-- Run this against rapido_clone after sourcing schema.sql to verify structure.

SHOW TABLES;

DESCRIBE users;
DESCRIBE drivers;
DESCRIBE rides;
DESCRIBE payments;

-- Seed users (password_hash = bcrypt of 'password')
INSERT INTO users (name, mobile, password_hash)
VALUES ('John Doe', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Seed drivers
INSERT INTO drivers (name, mobile, vehicle_number)
VALUES ('Mike Smith', '1234567890', 'KA-01-AB-1234');

-- OTP must be stored as bcrypt hash (schema column is VARCHAR(60)).
-- This hash represents the raw OTP '4832'.
INSERT INTO rides (user_id, driver_id, pickup_location, destination, distance_km, fare, otp)
VALUES (1, 1, 'Koramangala', 'Indiranagar', 5.50, 85.00,
        '$2y$10$5cDEGbBM/WdHATBxMbFkNuYqy.K3bXVz/Ner.6RVsBzBKbW8B7kkS');

-- Seed a payment
INSERT INTO payments (ride_id, user_id, amount, payment_method)
VALUES (1, 1, 85.00, 'CASH');

-- Verify
SELECT * FROM rides;
SELECT * FROM payments;

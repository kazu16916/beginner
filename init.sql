CREATE DATABASE IF NOT EXISTS security_demo;
USE security_demo;

CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(64) NOT NULL,
    email VARCHAR(100),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert test users (passwords are SHA256 hashed)
-- amechan:CT314 -> SHA256
INSERT INTO Users (username, password, email, is_admin) VALUES 
('amechan', '7325de1719c557c4ec6430812f6bf9bbfee4efb3b60dd3303bae8f8006c5e0db', 'amechan@example.com', TRUE),
('test', '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08', 'test@example.com', FALSE);

INSERT INTO products (name, description, price) VALUES
('desktop', 'laptop 16GB RAM, 512GB SSD', 89800.00),
('smartphone', 'new 128GB', 78000.00),
('tablet', 'slim 10in', 45000.00),
('earphone', 'cool', 12800.00),
('4Kmonitor', '27in 4K', 35000.00),
('mouse', 'game', 8500.00),
('keyboard', 'RGB LED', 15000.00);
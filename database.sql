-- MySQL schema for Elite Diecast store (XAMPP defaults)
-- Usage:
-- 1) Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2) Create database by running this entire script
-- 3) Update config.php with your credentials if needed

CREATE DATABASE IF NOT EXISTS `car_showroom` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `car_showroom`;

-- Admins table for admin authentication
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact messages (from Contact Us form)
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products (diecast models)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `scale` VARCHAR(10) NOT NULL,            -- e.g. 1:18, 1:24
  `variant` VARCHAR(120) NULL,             -- e.g. GT3 RS
  `year` SMALLINT NULL,
  `type` VARCHAR(50) NULL,                 -- e.g. Diecast Sports, Diecast JDM
  `price` DECIMAL(10,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `image_url` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  KEY `idx_items_product` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wishlists
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_product` (`user_id`, `product_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments (Razorpay integration)
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `razorpay_order_id` VARCHAR(100) NOT NULL UNIQUE,
  `razorpay_payment_id` VARCHAR(100) NULL,
  `razorpay_signature` VARCHAR(255) NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `status` ENUM('created','pending','success','failed') NOT NULL DEFAULT 'created',
  `error_reason` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_user` (`user_id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_razorpay_order` (`razorpay_order_id`),
  KEY `idx_payments_status` (`status`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default admin account
-- Email: admin@gmail.com, Password: Admin@1234
INSERT INTO `admins` (`name`, `email`, `password_hash`) VALUES
('System Admin', 'admin@gmail.com', '$2y$10$Ks3g809GYRfPzOMXuefKo.N9fAgJRBj0GaleX2AswBdtGYSLudgli');

-- Seed sample products matching current homepage
INSERT INTO `products` (`name`, `brand`, `scale`, `variant`, `year`, `type`, `price`, `stock`, `image_url`) VALUES
('Huracán EVO', 'Lamborghini', '1:18', 'EVO', 2024, 'Diecast Supercar', 129.00, 10, 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=800&h=500&fit=crop'),
('SF90 Stradale', 'Ferrari', '1:24', 'Stradale', 2024, 'Diecast Sports', 59.00, 25, 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&h=500&fit=crop'),
('911 GT3 RS', 'Porsche', '1:18', 'GT3 RS', 2024, 'Diecast Sports', 119.00, 12, 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&h=500&fit=crop'),
('GT-R R35', 'Nissan', '1:24', 'R35', 2024, 'Diecast JDM', 49.00, 30, 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&h=500&fit=crop'),
('G63', 'Mercedes-AMG', '1:24', 'G63', 2024, 'Diecast SUV', 54.00, 20, 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=500&fit=crop'),
('Supra MK4', 'Toyota', '1:24', 'MK4', 2024, 'Diecast JDM', 45.00, 18, 'https://images.unsplash.com/photo-1523986371872-9d3ba2e2f642?w=800&h=500&fit=crop');



-- ============================================
-- Flutter Chat & Products System - Database Schema
-- ============================================

-- Drop tables if they exist (for fresh install)
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;

-- ============================================
-- Table: users
-- Guest users with username-only authentication
-- ============================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(20) NOT NULL UNIQUE,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: messages
-- Chat messages supporting text, image, and video
-- ============================================
CREATE TABLE `messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `room_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `type` ENUM('text', 'image', 'video') NOT NULL DEFAULT 'text',
  `content` TEXT NOT NULL,
  `client_msg_id` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_room_created` (`room_id`, `created_at` DESC),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_client_msg_id` (`client_msg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: products
-- Products catalog managed by admin
-- ============================================
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `description` TEXT,
  `image_url` VARCHAR(500),
  `category` VARCHAR(100),
  `contact_link` VARCHAR(500),
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_category` (`category`),
  INDEX `idx_created_at` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: admins
-- Admin users for dashboard access
-- ============================================
CREATE TABLE `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: settings
-- Global app settings (single row)
-- ============================================
CREATE TABLE `settings` (
  `id` INT UNSIGNED PRIMARY KEY DEFAULT 1,
  `app_name` VARCHAR(255) NOT NULL DEFAULT 'Flutter Chat',
  `default_contact_link` VARCHAR(500),
  `welcome_message` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert default admin user
-- Email: admin@example.com
-- Password: admin123
-- ============================================
INSERT INTO `admins` (`email`, `password_hash`) 
VALUES ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Note: Change this password after first login!

-- ============================================
-- Insert default settings
-- ============================================
INSERT INTO `settings` (`id`, `app_name`, `default_contact_link`, `welcome_message`) 
VALUES (
  1, 
  'Flutter Chat & Products', 
  'https://wa.me/1234567890', 
  'مرحباً بك في تطبيق الدردشة والمنتجات!'
);

-- ============================================
-- Sample products (optional)
-- ============================================
INSERT INTO `products` (`name`, `price`, `description`, `image_url`, `category`, `contact_link`, `is_active`) 
VALUES 
(
  'iPhone 15 Pro',
  999.99,
  'أحدث هاتف من Apple مع شريحة A17 Pro',
  'https://via.placeholder.com/400x400.png?text=iPhone+15+Pro',
  'Electronics',
  'https://wa.me/1234567890',
  1
),
(
  'Samsung Galaxy S24',
  899.99,
  'هاتف Samsung الرائد مع كاميرا 200MP',
  'https://via.placeholder.com/400x400.png?text=Galaxy+S24',
  'Electronics',
  'https://wa.me/1234567890',
  1
),
(
  'MacBook Pro M3',
  1999.99,
  'لابتوب قوي للمحترفين مع شريحة M3',
  'https://via.placeholder.com/400x400.png?text=MacBook+Pro',
  'Computers',
  'https://wa.me/1234567890',
  1
);

-- ============================================
-- End of schema.sql
-- ============================================

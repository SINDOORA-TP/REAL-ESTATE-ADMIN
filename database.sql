-- Magaram Real Estate Database Schema
-- Character Set: utf8mb4
-- Collation: utf8mb4_unicode_ci

CREATE DATABASE IF NOT EXISTS `magaram_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `magaram_db`;

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin: username 'admin', password 'admin123'
-- Hash generated via password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`username`, `password_hash`, `name`, `email`)
VALUES ('admin', '$2y$10$oXbWd9m7jHn9P9CjR0o1fe8M1.J8K1Z5gXGzV6E.h4L5PzK.45N7e', 'Magaram Executive Admin', 'admin@magaramrealestate.com')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- 2. Properties Table
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `category` ENUM('Residential', 'Commercial', 'Villa', 'Apartment', 'Plot') NOT NULL DEFAULT 'Residential',
    `location` VARCHAR(255) NOT NULL,
    `price` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `price_display` VARCHAR(100) DEFAULT NULL,
    `area_sqft` INT NOT NULL DEFAULT 0,
    `bedrooms` INT NOT NULL DEFAULT 0,
    `bathrooms` INT NOT NULL DEFAULT 0,
    `description` TEXT NOT NULL,
    `status` ENUM('Available', 'Sold', 'Upcoming') NOT NULL DEFAULT 'Available',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_sample` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`),
    INDEX `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Property Images Table
CREATE TABLE IF NOT EXISTS `property_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    INDEX `idx_property_id` (`property_id`),
    CONSTRAINT `fk_property_images` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Amenities Table
CREATE TABLE IF NOT EXISTS `amenities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) NOT NULL DEFAULT 'sparkle'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Property Amenities Junction Table
CREATE TABLE IF NOT EXISTS `property_amenities` (
    `property_id` INT NOT NULL,
    `amenity_id` INT NOT NULL,
    PRIMARY KEY (`property_id`, `amenity_id`),
    CONSTRAINT `fk_pa_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pa_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Enquiries Table
CREATE TABLE IF NOT EXISTS `enquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NULL,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `message` TEXT,
    `type` ENUM('Enquiry', 'Site Visit', 'Brochure') NOT NULL DEFAULT 'Enquiry',
    `preferred_date` DATE NULL,
    `status` ENUM('New', 'Contacted', 'Completed') NOT NULL DEFAULT 'New',
    `notes` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Testimonials Table (Real & Permitted only, with is_approved flag)
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `designation` VARCHAR(150) NOT NULL,
    `text` TEXT NOT NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
    `is_sample` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Settings Table (Key-Value site configurations)
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Services Table
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `icon` VARCHAR(50) NOT NULL DEFAULT 'crown',
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Gallery Table
CREATE TABLE IF NOT EXISTS `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'Exterior',
    `image_path` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Standard Amenities
INSERT INTO `amenities` (`name`, `icon`) VALUES
('Infinity Pool', 'pool'),
('Private Sun Terrace', 'sun'),
('Smart Home Automation', 'cpu'),
('Italian Marble Flooring', 'gem'),
('Private Elevators', 'elevator'),
('24/7 Concierge & Security', 'shield'),
('State-of-the-art Gym & Spa', 'activity'),
('Multi-car Covered Parking', 'car'),
('Designer Landscaped Gardens', 'tree'),
('Helipad Access', 'compass')
ON DUPLICATE KEY UPDATE `name` = `name`;

-- Seed Site Settings (With sample markers as specified in Work Plan)
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Magaram Real Estate'),
('tagline', 'Curating Extraordinary Living Spaces in Coimbatore'),
('contact_email', 'contact@magaramrealestate.com'),
('contact_phone', '+91 98430 12345'),
('office_address', 'Magaram Towers, Level 5, Race Course Road, Coimbatore, Tamil Nadu - 641018'),
('hero_headline_1', 'Experience'),
('hero_headline_2', 'Luxury Living'),
('hero_subtext', 'Bespoke residences, signature penthouses, and prime commercial estates crafted with architectural precision.'),
('show_stats', '1'),
('stats_note', '[Sample Data - Update with Real Figures]'),
('stat_1_val', '15+'),
('stat_1_lbl', 'Years of Heritage [Sample]'),
('stat_2_val', '75+'),
('stat_2_lbl', 'Bespoke Projects [Sample]'),
('stat_3_val', '₹350Cr+'),
('stat_3_lbl', 'Properties Transacted [Sample]'),
('stat_4_val', '99.2%'),
('stat_4_lbl', 'Client Satisfaction [Sample]'),
('show_testimonials', '1'),
('about_preview', 'At Magaram Real Estate, we bridge discerning patrons with timeless architectural masterworks across Coimbatore and the Western Ghats foothills.'),
('map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3916.326292398491!2d76.9712165757467!3d11.01183348915159!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba859ad33cfd2db%3A0x8aa50c60f4ff2b7!2sRace%20Course%2C%20Coimbatore%2C%20Tamil%20Nadu!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- Seed Default Services
INSERT INTO `services` (`title`, `description`, `icon`, `sort_order`) VALUES
('Luxury Residential Brokerage', 'Exclusive representation for high-net-worth individuals acquiring premier penthouses, villas, and sprawling estates in Coimbatore.', 'home', 1),
('Signature Commercial Spaces', 'Advising corporations and private investors on trophy office towers, IT corridor campuses, and commercial assets.', 'building', 2),
('Private Investment Advisory', 'Strategic real estate portfolio planning, acquisition vetting, and high-capital appreciation opportunity sourcing.', 'trending-up', 3),
('Bespoke Architectural Project Management', 'Turnkey management from concept blueprint to interior finishing for bespoke estate developments.', 'compass', 4)
ON DUPLICATE KEY UPDATE `title` = `title`;

-- Seed Sample Testimonials (Marked clearly as sample data)
INSERT INTO `testimonials` (`name`, `designation`, `text`, `rating`, `is_approved`, `is_sample`) VALUES
('Vikramaditya Rao', 'Founder, Apex Capital [Sample]', 'Magaram delivered an experience beyond what traditional firms offer. The level of privacy, architectural insight, and seamless transaction for our Race Course penthouse was unmatched.', 5, 1, 1),
('Dr. Aisha Merchant', 'Chief Neurosurgeon [Sample]', 'From private viewings to closing paperwork, every single detail was handled with precision and dignity. Truly the gold standard of luxury real estate advisory in Coimbatore.', 5, 1, 1),
('Rajeshwari Devi', 'Industrialist & Art Collector [Sample]', 'Finding a property that accommodated our private grounds in Vadavalli seemed impossible until Magaram brought us their off-market collection.', 5, 1, 1);

-- Seed Sample Properties (Coimbatore luxury portfolio - marked clearly as sample data)
INSERT INTO `properties` (`id`, `title`, `slug`, `category`, `location`, `price`, `price_display`, `area_sqft`, `bedrooms`, `bathrooms`, `description`, `status`, `is_featured`, `is_sample`) VALUES
(1, 'The Sovereign Penthouse [Sample]', 'the-sovereign-penthouse-coimbatore', 'Apartment', 'Race Course, Coimbatore', 85000000.00, '₹8.50 Cr', 5600, 4, 5, 'An ultra-exclusive double-height penthouse overlooking the lush green canopy of Race Course Road in Coimbatore. Features private biometric elevator access, an expansive open terrace with sky deck plunge pool, imported Calacatta marble, and 360-degree skyline vistas.', 'Available', 1, 1),
(2, 'The Elysian Sanctuary Villa [Sample]', 'the-elysian-sanctuary-villa-vadavalli', 'Villa', 'Vadavalli Foothills, Coimbatore', 125000000.00, '₹12.50 Cr', 8200, 5, 6, 'Set against the serene Western Ghats foothills in Vadavalli, this contemporary architectural estate features landscaped courtyards, natural stone facades, private temperature-controlled pool, home automation, and staff quarters.', 'Available', 1, 1),
(3, 'Prestige Apex Commercial Tower [Sample]', 'prestige-apex-commercial-peelamedu', 'Commercial', 'Avinashi Road, Peelamedu, Coimbatore', 360000000.00, '₹36.00 Cr', 28000, 0, 10, 'Grade-A corporate commercial tower positioned along Coimbatore\'s prime Avinashi Road arterial corridor. LEED Gold certified, high-speed destination elevators, column-free floor plates, and executive boardroom suites.', 'Available', 1, 1),
(4, 'The Aurum Signature Suites [Sample]', 'the-aurum-signature-suites-rs-puram', 'Apartment', 'R.S. Puram, Coimbatore', 68000000.00, '₹6.80 Cr', 4200, 4, 4, 'A boutique development of private single-floor residences in the heart of heritage R.S. Puram. Featuring soundproof acoustic glazing, private master suite with custom walk-in dressing chambers, and Italian modular fittings.', 'Available', 1, 1),
(5, 'Serene Palm Orchards Estate [Sample]', 'serene-palm-orchards-thondamuthur', 'Villa', 'Siruvani Foothills, Coimbatore', 145000000.00, '₹14.50 Cr', 9500, 6, 7, 'Sprawling private gated farm estate with organic coconut orchards, Olympic-length private swimming pool, independent guest clubhouse pavilion, and 24/7 security perimeter.', 'Upcoming', 1, 1),
(6, 'The Opus Tech Pinnacle [Sample]', 'the-opus-tech-pinnacle-saravanampatti', 'Commercial', 'Saravanampatti IT Corridor, Coimbatore', 240000000.00, '₹24.00 Cr', 22000, 0, 8, 'High-yield commercial tech headquarters designed for global SaaS and engineering enterprises in Coimbatore\'s premier IT hub.', 'Sold', 0, 1)
ON DUPLICATE KEY UPDATE `title` = `title`;

-- Property Amenities Links
INSERT INTO `property_amenities` (`property_id`, `amenity_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 8),
(2, 1), (2, 2), (2, 3), (2, 4), (2, 6), (2, 7), (2, 8), (2, 9),
(3, 3), (3, 5), (3, 6), (3, 8),
(4, 3), (4, 4), (4, 5), (4, 6), (4, 8),
(5, 1), (5, 2), (5, 6), (5, 8), (5, 9),
(6, 3), (6, 5), (6, 6), (6, 8)
ON DUPLICATE KEY UPDATE `property_id` = `property_id`;


USE kidssmart_app;

-- Activities table (unified from all scrapers)
CREATE TABLE IF NOT EXISTS activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100),
    suburb VARCHAR(100),
    postcode VARCHAR(10),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(500),
    age_range VARCHAR(50),
    cost VARCHAR(100),
    schedule TEXT,
    image_url VARCHAR(500),
    source_url VARCHAR(500) UNIQUE,  -- Prevent duplicates
    source_name VARCHAR(100),  -- NEW: Track which crawler (activeactivities, kidsbook, etc.)
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_approved BOOLEAN DEFAULT FALSE,
    INDEX idx_category (category),
    INDEX idx_suburb (suburb),
    INDEX idx_postcode (postcode),
    INDEX idx_source (source_name),
    INDEX idx_approved (is_approved)
);

-- Categories reference table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT
);

-- Locations/suburbs reference table
CREATE TABLE IF NOT EXISTS locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    suburb VARCHAR(100) NOT NULL,
    postcode VARCHAR(10) NOT NULL,
    state VARCHAR(50),
    UNIQUE KEY unique_suburb_postcode (suburb, postcode)
);

-- Insert common categories (examples)
INSERT IGNORE INTO categories (category_name) VALUES
    ('Sports'),
    ('Arts & Crafts'),
    ('Music'),
    ('Dance'),
    ('Swimming'),
    ('Education'),
    ('Before/After School Care'),
    ('Holiday Programs');

-- Insert Scraper logging
CREATE TABLE IF NOT EXISTS scraping_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    scraper_name VARCHAR(100) NOT NULL,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    message TEXT,
    run_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
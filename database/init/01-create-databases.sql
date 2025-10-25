-- Create databases
CREATE DATABASE IF NOT EXISTS kidssmart_app;
CREATE DATABASE IF NOT EXISTS kidssmart_users;

-- Create users
CREATE USER IF NOT EXISTS 'app_user'@'%' IDENTIFIED BY 'AppPass123!';
CREATE USER IF NOT EXISTS 'scraper_user'@'%' IDENTIFIED BY 'ScraperPass123!';

-- Grant permissions
-- App user has full access to both databases
GRANT ALL PRIVILEGES ON kidssmart_app.* TO 'app_user'@'%';
GRANT ALL PRIVILEGES ON kidssmart_users.* TO 'app_user'@'%';

-- Scraper user only has write access to app database
GRANT SELECT, INSERT, UPDATE ON kidssmart_app.* TO 'scraper_user'@'%';

FLUSH PRIVILEGES;
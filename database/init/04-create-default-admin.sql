USE kidssmart_users;

-- Add admin columns to users table if they don't exist
-- This ensures compatibility with the role-based admin system

-- Add is_admin column (for admin privileges - backward compatibility)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'kidssmart_users' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'is_admin') = 0,
    "ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE",
    "SELECT 'Column is_admin already exists'"
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add admin_level column for role hierarchy
SET @sql = (SELECT IF(
    (SELECT COUNT(*) 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'kidssmart_users' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'admin_level') = 0,
    "ALTER TABLE users ADD COLUMN admin_level TINYINT DEFAULT 0 COMMENT '0=User, 1=Admin, 2=SuperAdmin'",
    "SELECT 'Column admin_level already exists'"
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add last_login column (for tracking admin login times)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'kidssmart_users' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'last_login') = 0,
    "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL",
    "SELECT 'Column last_login already exists'"
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add can_manage_admins column (for super admin privileges)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'kidssmart_users' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'can_manage_admins') = 0,
    "ALTER TABLE users ADD COLUMN can_manage_admins BOOLEAN DEFAULT FALSE COMMENT 'Can create/modify other admin accounts'",
    "SELECT 'Column can_manage_admins already exists'"
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add can_access_system_settings column (for system configuration access)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'kidssmart_users' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'can_access_system_settings') = 0,
    "ALTER TABLE users ADD COLUMN can_access_system_settings BOOLEAN DEFAULT FALSE COMMENT 'Can access system configuration'",
    "SELECT 'Column can_access_system_settings already exists'"
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create admin user accounts with role hierarchy
-- Default Password: KidsSmartAdmin2025!
-- IMPORTANT: These hashes are generated using PHP password_hash() with PASSWORD_BCRYPT
-- They are compatible with PHP's password_verify() function

-- Generate fresh password hash using MySQL's SHA2 (temporary solution)
-- Note: PHP will need to verify using password_verify, so we use a known good hash

-- LEVEL 1 ADMIN: Regular Administrator
INSERT IGNORE INTO users (
    username,
    email, 
    password_hash,
    first_name,
    last_name,
    is_admin,
    admin_level,
    can_manage_admins,
    can_access_system_settings,
    is_active,
    is_verified,
    created_at
) VALUES (
    'admin',
    'admin@kidssmart.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is bcrypt for 'password'
    'System',
    'Administrator',
    TRUE,
    1,
    FALSE,
    FALSE,
    TRUE,
    TRUE,
    NOW()
);

-- LEVEL 2 SUPER ADMIN: Super Administrator  
INSERT IGNORE INTO users (
    username,
    email,
    password_hash,
    first_name,
    last_name,
    is_admin,
    admin_level,
    can_manage_admins,
    can_access_system_settings,
    is_active,
    is_verified,
    created_at
) VALUES (
    'superadmin',
    'superadmin@kidssmart.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is bcrypt for 'password'
    'Super',
    'Administrator',
    TRUE,
    2,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    NOW()
);

-- Add indexes for performance optimization
CREATE INDEX IF NOT EXISTS idx_admin_level ON users(admin_level);
CREATE INDEX IF NOT EXISTS idx_admin_permissions ON users(is_admin, admin_level, is_active);
CREATE INDEX IF NOT EXISTS idx_can_manage_admins ON users(can_manage_admins);

-- IMPORTANT NOTICE:
-- The password for both 'admin' and 'superadmin' accounts is: password
-- Change this immediately after first login for security!
-- The original KidsSmartAdmin2025! password hash was causing compatibility issues
-- This uses a standard bcrypt hash that works with PHP password_verify()
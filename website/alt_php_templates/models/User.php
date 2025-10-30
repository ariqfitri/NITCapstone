<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $suburb;
    public $postcode;
    public $child_age_range;
    public $preferences;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password_hash=:password_hash, 
                      first_name=:first_name, last_name=:last_name, suburb=:suburb, 
                      postcode=:postcode, child_age_range=:child_age_range";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->suburb = htmlspecialchars(strip_tags($this->suburb));
        $this->postcode = htmlspecialchars(strip_tags($this->postcode));
        $this->child_age_range = htmlspecialchars(strip_tags($this->child_age_range));

        // Bind data
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":suburb", $this->suburb);
        $stmt->bindParam(":postcode", $this->postcode);
        $stmt->bindParam(":child_age_range", $this->child_age_range);

        if ($stmt->execute()) {
            $this->user_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Check if username exists
    public function usernameExists() {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Login user
    public function login() {
        $query = "SELECT user_id, username, email, password_hash, first_name, last_name, suburb, postcode, child_age_range 
                  FROM " . $this->table_name . " 
                  WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->bindParam(2, $this->username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($this->password_hash, $row['password_hash'])) {
                $this->user_id = $row['user_id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->suburb = $row['suburb'];
                $this->postcode = $row['postcode'];
                $this->child_age_range = $row['child_age_range'];
                return true;
            }
        }
        return false;
    }

    // Get user by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->suburb = $row['suburb'];
            $this->postcode = $row['postcode'];
            $this->child_age_range = $row['child_age_range'];
            return true;
        }
        return false;
    }

    // Update user profile
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET first_name=:first_name, last_name=:last_name, suburb=:suburb, 
                      postcode=:postcode, child_age_range=:child_age_range 
                  WHERE user_id=:user_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":suburb", $this->suburb);
        $stmt->bindParam(":postcode", $this->postcode);
        $stmt->bindParam(":child_age_range", $this->child_age_range);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    // Change password
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password_hash=:password_hash WHERE user_id=:user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":user_id", $this->user_id);
        return $stmt->execute();
    }

    // Get total users count
    public function getTotalUsersCount() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in getTotalUsersCount: " . $e->getMessage());
            return 0;
        }
    }

    // Get recent users
    public function getRecentUsers($limit = 5) {
        try {
            $query = "SELECT username, email, created_at 
                    FROM " . $this->table_name . " 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getRecentUsers: " . $e->getMessage());
            return [];
        }
    }

    // Login after signing up
    public function loginAfterRegistration($plain_password) {
        // Store the original password_hash temporarily
        $original_password_hash = $this->password_hash;
        
        // Temporarily set the plain password for verification
        $this->password_hash = $plain_password;
        
        // Try to login
        $login_success = $this->login();
        
        // Restore the original password hash
        $this->password_hash = $original_password_hash;
        
        return $login_success;
    }

    // Get new users today
    public function getNewUsersToday() {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                     WHERE DATE(created_at) = CURDATE()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting new users today: " . $e->getMessage());
            return 0;
        }
    }

    // Get active users today
    public function getActiveUsersToday() {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                     WHERE is_active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting active users: " . $e->getMessage());
            return 0;
        }
    }

    // Get active users this week (with activity tracking)
    public function getActiveUsersThisWeek() {
        try {
            // Try to get from activity logs first, fallback to all active users
            $query = "SELECT COUNT(DISTINCT user_id) as count 
                     FROM user_activity_logs 
                     WHERE activity_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return $result['count'];
            }
            
            // Fallback to active users if activity table doesn't exist
            return $this->getActiveUsersToday();
        } catch (PDOException $e) {
            // Fallback to active users count
            return $this->getActiveUsersToday();
        }
    }

    // Get user growth statistics
    public function getUserGrowthStats() {
        try {
            $query = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_users
                FROM " . $this->table_name . " 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user growth stats: " . $e->getMessage());
            return [];
        }
    }

    // Get user by email (for admin purposes)
    public function getUserByEmail($email) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user by email: " . $e->getMessage());
            return false;
        }
    }

    // Get user engagement metrics
    public function getUserEngagement($user_id) {
        try {
            $metrics = [];
            
            // Get favorites count
            try {
                $query = "SELECT COUNT(*) as count FROM favourites WHERE user_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                $metrics['favorites'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (PDOException $e) {
                $metrics['favorites'] = 0;
            }
            
            // Get reviews count
            try {
                $query = "SELECT COUNT(*) as count FROM reviews WHERE user_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                $metrics['reviews'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (PDOException $e) {
                $metrics['reviews'] = 0;
            }
            
            // Get search history count
            try {
                $query = "SELECT COUNT(*) as count FROM search_logs WHERE user_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                $metrics['searches'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (PDOException $e) {
                $metrics['searches'] = 0;
            }
            
            return $metrics;
        } catch (PDOException $e) {
            return ['favorites' => 0, 'reviews' => 0, 'searches' => 0];
        }
    }

    // Update user last login
    public function updateLastLogin($user_id) {
        try {
            $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }

    // Log user activity (for tracking)
    public function logActivity($user_id, $activity_type, $activity_data = null) {
        try {
            $query = "INSERT INTO user_activity_logs (user_id, activity_type, activity_data, activity_time) 
                     VALUES (?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id, $activity_type, json_encode($activity_data)]);
        } catch (PDOException $e) {
            // Table might not exist, ignore silently
            return false;
        }
    }

    // Get user statistics for admin
    public function getUserStats($user_id) {
        try {
            $query = "SELECT 
                u.*,
                (SELECT COUNT(*) FROM favourites f WHERE f.user_id = u.user_id) as favorite_count,
                (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.user_id) as review_count
                FROM " . $this->table_name . " u
                WHERE u.user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user stats: " . $e->getMessage());
            return false;
        }
    }

}
?>
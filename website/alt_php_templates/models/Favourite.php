<?php
class Favourite {
    private $conn;
    private $table_name = "favourites";

    public $favourite_id;
    public $user_id;
    public $activity_id;

    public function __construct($db) {
        // ✅ IMPORTANT: This model should use the users database connection
        // because favourites table is in kidssmart_users database
        $this->conn = $db;
    }

    // Add to favourites
    public function create() {
        try {
            // ✅ FIXED: Get activity details from app database first
            $activity = $this->getActivityDetails($this->activity_id);
            if (!$activity) {
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, activity_id, activity_title, activity_url, activity_image, activity_age_range, activity_category) 
                     VALUES (:user_id, :activity_id, :activity_title, :activity_url, :activity_image, :activity_age_range, :activity_category)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":activity_id", $this->activity_id);
            $stmt->bindParam(":activity_title", $activity['title']);
            $stmt->bindParam(":activity_url", $activity['source_url']);
            $stmt->bindParam(":activity_image", $activity['image_url']);
            $stmt->bindParam(":activity_age_range", $activity['age_range']);
            $stmt->bindParam(":activity_category", $activity['category']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating favourite: " . $e->getMessage());
            return false;
        }
    }

    // Remove from favourites
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id AND activity_id = :activity_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":activity_id", $this->activity_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting favourite: " . $e->getMessage());
            return false;
        }
    }

    // Check if activity is favourited
    public function isFavourited() {
        try {
            $query = "SELECT favourite_id FROM " . $this->table_name . " WHERE user_id = :user_id AND activity_id = :activity_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":activity_id", $this->activity_id);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking if favourited: " . $e->getMessage());
            return false;
        }
    }

    // Get user's favourites with activity details
    public function getUserFavourites($user_id) {
        try {
            // ✅ FIXED: Since favourites table now stores activity details, 
            // we can get most info directly from favourites table
            $query = "SELECT 
                        f.favourite_id,
                        f.activity_id,
                        f.activity_title as title,
                        f.activity_category as category,
                        f.activity_image as image_url,
                        f.activity_age_range as age_range,
                        f.created_at,
                        'Unknown' as suburb
                      FROM " . $this->table_name . " f
                      WHERE f.user_id = :user_id
                      ORDER BY f.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user favourites: " . $e->getMessage());
            return [];
        }
    }

    // ✅ NEW: Helper method to get activity details from app database
    private function getActivityDetails($activity_id) {
        try {
            // Create connection to app database
            $appDatabase = new Database('kidssmart_app');
            $appDb = $appDatabase->getConnection();
            
            $query = "SELECT activity_id, title, category, suburb, image_url, age_range, source_url 
                     FROM activities 
                     WHERE activity_id = :activity_id AND is_approved = 1 
                     LIMIT 1";
            
            $stmt = $appDb->prepare($query);
            $stmt->bindParam(":activity_id", $activity_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting activity details: " . $e->getMessage());
            return false;
        }
    }

    // ✅ NEW: Get favourites with live activity data (alternative method)
    public function getUserFavouritesWithLiveData($user_id) {
        try {
            // Create connection to app database for joining
            $appDatabase = new Database('kidssmart_app');
            $appDb = $appDatabase->getConnection();
            
            // First get favourite activity IDs
            $query = "SELECT activity_id, created_at FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            $favourites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($favourites as $fav) {
                // Get current activity data
                $activityQuery = "SELECT activity_id, title, category, suburb, postcode, image_url, age_range 
                                 FROM activities 
                                 WHERE activity_id = :activity_id AND is_approved = 1";
                $activityStmt = $appDb->prepare($activityQuery);
                $activityStmt->bindParam(":activity_id", $fav['activity_id']);
                $activityStmt->execute();
                $activity = $activityStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($activity) {
                    $activity['created_at'] = $fav['created_at'];
                    $result[] = $activity;
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting user favourites with live data: " . $e->getMessage());
            return [];
        }
    }
}
<?php
class Program {
    private $conn;
    private $table_name = "activities";

    public function __construct($db) {
        // Use the app database connection for activities
        $this->conn = $db;
    }

    // Get featured programs
    public function getFeaturedPrograms($limit = 6) {
        try {
            $query = "SELECT activity_id, title, description, category, suburb, postcode, image_url, age_range 
                      FROM " . $this->table_name . " 
                      WHERE is_approved = 1 
                      ORDER BY activity_id DESC 
                      LIMIT " . (int)$limit;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getFeaturedPrograms: " . $e->getMessage());
            return [];
        }
    }

    // Get all categories
    public function getCategories() {
        try {
            $query = "SELECT DISTINCT category FROM " . $this->table_name . " WHERE category IS NOT NULL AND category != '' AND is_approved = 1 ORDER BY category";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = $row['category'];
            }
            return $categories;
        } catch (PDOException $e) {
            error_log("Database error in getCategories: " . $e->getMessage());
            return [];
        }
    }

    // Get all suburbs
    public function getSuburbs() {
        try {
            $query = "SELECT DISTINCT suburb FROM " . $this->table_name . " WHERE suburb IS NOT NULL AND suburb != '' AND is_approved = 1 ORDER BY suburb";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $suburbs = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $suburbs[] = $row['suburb'];
            }
            return $suburbs;
        } catch (PDOException $e) {
            error_log("Database error in getSuburbs: " . $e->getMessage());
            return [];
        }
    }

    // Get total programs count
    public function getTotalProgramsCount() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE is_approved = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in getTotalProgramsCount: " . $e->getMessage());
            return 0;
        }
    }

    // Search programs
    public function searchPrograms($search_term = '', $category = '', $suburb = '', $page = 1, $limit = 12) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT * FROM " . $this->table_name . " WHERE is_approved = 1";
            $params = [];
            
            if (!empty($search_term)) {
                $query .= " AND (title LIKE ? OR description LIKE ? OR suburb LIKE ?)";
                $search_param = "%$search_term%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($category)) {
                $query .= " AND category = ?";
                $params[] = $category;
            }
            
            if (!empty($suburb)) {
                $query .= " AND suburb = ?";
                $params[] = $suburb;
            }
            
            $query .= " ORDER BY title ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $this->conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in searchPrograms: " . $e->getMessage());
            return [];
        }
    }

    // Get program by ID
    public function getProgramById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE activity_id = ? AND is_approved = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getProgramById: " . $e->getMessage());
            return null;
        }
    }

    // Get count for search results
    public function getSearchCount($search_term = '', $category = '', $suburb = '') {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE is_approved = 1";
            $params = [];
            
            if (!empty($search_term)) {
                $query .= " AND (title LIKE ? OR description LIKE ? OR suburb LIKE ?)";
                $search_param = "%$search_term%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($category)) {
                $query .= " AND category = ?";
                $params[] = $category;
            }
            
            if (!empty($suburb)) {
                $query .= " AND suburb = ?";
                $params[] = $suburb;
            }
            
            $stmt = $this->conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in getSearchCount: " . $e->getMessage());
            return 0;
        }
    }

    // Get pending activities (for admin)
    public function getPendingActivities() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE is_approved = 0 ORDER BY activity_id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getPendingActivities: " . $e->getMessage());
            return [];
        }
    }

    // Get approved activities (for admin)
    public function getApprovedActivities() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE is_approved = 1 ORDER BY activity_id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getApprovedActivities: " . $e->getMessage());
            return [];
        }
    }

    // Get recommended activities based on user preferences
    public function getRecommendedActivities($suburb = '', $age_range = '', $limit = 6) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE is_approved = 1";
            $params = [];
            
            if (!empty($suburb)) {
                $query .= " AND suburb = ?";
                $params[] = $suburb;
            }
            
            if (!empty($age_range)) {
                $query .= " AND (age_range LIKE ? OR age_range IS NULL)";
                $params[] = "%$age_range%";
            }
            
            $query .= " ORDER BY RAND() LIMIT " . (int)$limit;
            
            $stmt = $this->conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getRecommendedActivities: " . $e->getMessage());
            return [];
        }
    }

    // Get category statistics
    public function getCategoryStats() {
        try {
            $query = "SELECT category, COUNT(*) as count 
                    FROM " . $this->table_name . " 
                    WHERE category IS NOT NULL AND category != '' AND is_approved = 1
                    GROUP BY category 
                    ORDER BY count DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getCategoryStats: " . $e->getMessage());
            return [];
        }
    }

    // Get recent activities
    public function getRecentActivities($days = 7) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                    WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                    ORDER BY scraped_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getRecentActivities: " . $e->getMessage());
            return [];
        }
    }

    // Get popular activities
    public function getPopularActivities($limit = 5) {
        try {
            // Try to get by view count first, fallback to recent if view_count doesn't exist
            $query = "SELECT title, activity_id, category, suburb, view_count
                     FROM " . $this->table_name . " 
                     WHERE is_approved = 1 
                     ORDER BY view_count DESC, activity_id DESC 
                     LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback if view_count column doesn't exist
            try {
                $query = "SELECT title, activity_id, category, suburb, 0 as view_count
                         FROM " . $this->table_name . " 
                         WHERE is_approved = 1 
                         ORDER BY activity_id DESC 
                         LIMIT ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e2) {
                error_log("Error getting popular activities: " . $e2->getMessage());
                return [];
            }
        }
    }

    // Update activity view count
    public function updateViewCount($activity_id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET view_count = COALESCE(view_count, 0) + 1, last_viewed = NOW() 
                     WHERE activity_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$activity_id]);
        } catch (PDOException $e) {
            // Ignore if view_count column doesn't exist
            return false;
        }
    }

    // Get activity statistics for admin
    public function getActivityStats() {
        try {
            $query = "SELECT 
                COUNT(*) as total_activities,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_activities,
                SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_activities,
                COUNT(DISTINCT category) as total_categories,
                COUNT(DISTINCT suburb) as total_suburbs,
                COUNT(DISTINCT source_name) as total_sources
                FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting activity stats: " . $e->getMessage());
            return [
                'total_activities' => 0,
                'approved_activities' => 0,
                'pending_activities' => 0,
                'total_categories' => 0,
                'total_suburbs' => 0,
                'total_sources' => 0
            ];
        }
    }

    // Get activities by source
    public function getActivitiesBySource($source_name = null) {
        try {
            $query = "SELECT source_name, COUNT(*) as count, MAX(scraped_at) as last_updated
                     FROM " . $this->table_name . " 
                     WHERE source_name IS NOT NULL";
            $params = [];
            
            if ($source_name) {
                $query .= " AND source_name = ?";
                $params[] = $source_name;
            }
            
            $query .= " GROUP BY source_name ORDER BY count DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting activities by source: " . $e->getMessage());
            return [];
        }
    }

    // Search activities for admin (includes unapproved)
    public function searchActivitiesAdmin($search_term = '', $category = '', $suburb = '', $status = '', $source = '', $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
            $params = [];
            
            if (!empty($search_term)) {
                $query .= " AND (title LIKE ? OR description LIKE ?)";
                $search_param = "%$search_term%";
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($category)) {
                $query .= " AND category = ?";
                $params[] = $category;
            }
            
            if (!empty($suburb)) {
                $query .= " AND suburb = ?";
                $params[] = $suburb;
            }
            
            if ($status === 'approved') {
                $query .= " AND is_approved = 1";
            } elseif ($status === 'pending') {
                $query .= " AND is_approved = 0";
            }
            
            if (!empty($source)) {
                $query .= " AND source_name = ?";
                $params[] = $source;
            }
            
            $query .= " ORDER BY activity_id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in searchActivitiesAdmin: " . $e->getMessage());
            return [];
        }
    }

}
?>
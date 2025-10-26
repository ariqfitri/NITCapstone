<?php
class Program {
    private $conn;
    private $table_name = "activities";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get featured programs
    public function getFeaturedPrograms($limit = 6) {
        $query = "SELECT activity_id, title, description, category, suburb, postcode, image_url, age_range 
                  FROM " . $this->table_name . " 
                  WHERE is_approved = 1 
                  ORDER BY activity_id DESC 
                  LIMIT " . (int)$limit;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all categories
    public function getCategories() {
        $query = "SELECT DISTINCT category FROM " . $this->table_name . " WHERE category IS NOT NULL AND category != '' AND is_approved = 1 ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = $row['category'];
        }
        return $categories;
    }

    // Get all suburbs
    public function getSuburbs() {
        $query = "SELECT DISTINCT suburb FROM " . $this->table_name . " WHERE suburb IS NOT NULL AND suburb != '' AND is_approved = 1 ORDER BY suburb";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $suburbs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $suburbs[] = $row['suburb'];
        }
        return $suburbs;
    }

    // Get total programs count
    public function getTotalProgramsCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE is_approved = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Search programs - FIXED VERSION
    public function searchPrograms($search_term = '', $category = '', $suburb = '', $page = 1, $limit = 12) {
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
    }

    // Get program by ID
    public function getProgramById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE activity_id = ? AND is_approved = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get count for search results (for pagination)
    public function getSearchCount($search_term = '', $category = '', $suburb = '') {
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
        return $row['total'];
    }
}
?>
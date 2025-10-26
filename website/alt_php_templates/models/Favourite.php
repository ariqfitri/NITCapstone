<?php
class Favourite {
    private $conn;
    private $table_name = "php_user_favourites";

    public $favourite_id;
    public $user_id;
    public $activity_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add to favourites
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (user_id, activity_id) VALUES (:user_id, :activity_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":activity_id", $this->activity_id);
        return $stmt->execute();
    }

    // Remove from favourites
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id AND activity_id = :activity_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":activity_id", $this->activity_id);
        return $stmt->execute();
    }

    // Check if activity is favourited
    public function isFavourited() {
        $query = "SELECT favourite_id FROM " . $this->table_name . " WHERE user_id = :user_id AND activity_id = :activity_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":activity_id", $this->activity_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Get user's favourites
    public function getUserFavourites($user_id) {
        $query = "SELECT f.*, a.title, a.category, a.suburb, a.image_url 
                  FROM " . $this->table_name . " f
                  JOIN kidssmart_app.activities a ON f.activity_id = a.activity_id
                  WHERE f.user_id = :user_id
                  ORDER BY f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
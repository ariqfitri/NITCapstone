<?php
class Scraper {
    private $conn;
    private $table_name = "scraping_logs";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Log scraper execution
    public function logScraperRun($scraper_name, $status, $message = '') {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (scraper_name, status, message, run_at) 
                     VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$scraper_name, $status, $message]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error logging scraper run: " . $e->getMessage());
            return false;
        }
    }

    // Get scraper statistics
    public function getScraperStats() {
        try {
            $query = "SELECT source_name, COUNT(*) as count, MAX(scraped_at) as last_scraped 
                     FROM activities 
                     WHERE source_name IS NOT NULL 
                     GROUP BY source_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting scraper stats: " . $e->getMessage());
            return [];
        }
    }

    // Get recent scraper runs
    public function getRecentRuns($limit = 10) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     ORDER BY run_at DESC 
                     LIMIT ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent runs: " . $e->getMessage());
            return [];
        }
    }
}
?>
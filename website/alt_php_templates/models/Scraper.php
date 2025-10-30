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

    // Get failed scrapers (recently failed)
    public function getFailedScrapers() {
        try {
            $query = "SELECT DISTINCT scraper_name 
                     FROM " . $this->table_name . " 
                     WHERE status = 'failed' 
                     AND run_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     ORDER BY run_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting failed scrapers: " . $e->getMessage());
            return [];
        }
    }

    // Get scraper performance metrics
    public function getScraperPerformance($scraper_name = null, $days = 7) {
        try {
            $where_clause = $scraper_name ? "AND scraper_name = ?" : "";
            $query = "SELECT 
                scraper_name,
                COUNT(*) as total_runs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_runs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_runs,
                MAX(run_at) as last_run
                FROM " . $this->table_name . " 
                WHERE run_at >= DATE_SUB(NOW(), INTERVAL ? DAY) $where_clause
                GROUP BY scraper_name
                ORDER BY last_run DESC";
            
            $stmt = $this->conn->prepare($query);
            $params = [$days];
            if ($scraper_name) {
                $params[] = $scraper_name;
            }
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting scraper performance: " . $e->getMessage());
            return [];
        }
    }

    // Get scraper health status
    public function getScraperHealth() {
        try {
            $health = [];
            
            // Check last run times
            $query = "SELECT 
                scraper_name,
                MAX(run_at) as last_run,
                MAX(CASE WHEN status = 'completed' THEN run_at END) as last_success
                FROM " . $this->table_name . " 
                GROUP BY scraper_name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $scrapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($scrapers as $scraper) {
                $hours_since_last_run = $scraper['last_run'] ? 
                    (time() - strtotime($scraper['last_run'])) / 3600 : 999;
                $hours_since_success = $scraper['last_success'] ? 
                    (time() - strtotime($scraper['last_success'])) / 3600 : 999;
                
                $status = 'healthy';
                if ($hours_since_success > 48) {
                    $status = 'critical';
                } elseif ($hours_since_success > 24) {
                    $status = 'warning';
                }
                
                $health[$scraper['scraper_name']] = [
                    'status' => $status,
                    'last_run' => $scraper['last_run'],
                    'last_success' => $scraper['last_success'],
                    'hours_since_success' => round($hours_since_success, 1)
                ];
            }
            
            return $health;
        } catch (PDOException $e) {
            error_log("Error getting scraper health: " . $e->getMessage());
            return [];
        }
    }

    // Update scraper run with completion time
    public function updateScraperRun($log_id, $status, $message = '') {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET status = ?, message = ?, completed_at = NOW() 
                     WHERE log_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$status, $message, $log_id]);
        } catch (PDOException $e) {
            error_log("Error updating scraper run: " . $e->getMessage());
            return false;
        }
    }

    // Get scraper logs with filters
    public function getScraperLogs($scraper_name = null, $status = null, $limit = 50) {
        try {
            $where_conditions = [];
            $params = [];
            
            if ($scraper_name) {
                $where_conditions[] = "scraper_name = ?";
                $params[] = $scraper_name;
            }
            
            if ($status) {
                $where_conditions[] = "status = ?";
                $params[] = $status;
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT * FROM " . $this->table_name . " 
                     $where_clause 
                     ORDER BY run_at DESC 
                     LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting scraper logs: " . $e->getMessage());
            return [];
        }
    }

    // Clean old logs (keep only recent ones)
    public function cleanOldLogs($days_to_keep = 30) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE run_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$days_to_keep]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error cleaning old logs: " . $e->getMessage());
            return false;
        }
    }

    // Get scraper activity timeline
    public function getActivityTimeline($days = 7) {
        try {
            $query = "SELECT 
                DATE(run_at) as date,
                scraper_name,
                COUNT(*) as runs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM " . $this->table_name . " 
                WHERE run_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(run_at), scraper_name
                ORDER BY date DESC, scraper_name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting activity timeline: " . $e->getMessage());
            return [];
        }
    }

    // Get data freshness report
    public function getDataFreshness() {
        try {
            $query = "SELECT 
                source_name,
                COUNT(*) as total_activities,
                MAX(scraped_at) as latest_data,
                MIN(scraped_at) as oldest_data,
                AVG(TIMESTAMPDIFF(DAY, scraped_at, NOW())) as avg_age_days
                FROM activities 
                WHERE source_name IS NOT NULL
                GROUP BY source_name
                ORDER BY latest_data DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting data freshness: " . $e->getMessage());
            return [];
        }
    }

    // Get system load during scraper runs
    public function getSystemLoad() {
        try {
            $query = "SELECT 
                COUNT(CASE WHEN status = 'started' THEN 1 END) as running_scrapers,
                COUNT(CASE WHEN run_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as runs_last_hour,
                AVG(CASE WHEN status = 'completed' AND completed_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, run_at, completed_at) END) as avg_runtime_seconds
                FROM " . $this->table_name . " 
                WHERE run_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting system load: " . $e->getMessage());
            return ['running_scrapers' => 0, 'runs_last_hour' => 0, 'avg_runtime_seconds' => 0];
        }
    }

}
?>
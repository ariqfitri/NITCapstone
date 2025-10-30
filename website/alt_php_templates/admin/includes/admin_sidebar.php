<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3 sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                    <?php if (basename($_SERVER['PHP_SELF']) == 'dashboard.php'): ?>
                        <span class="sr-only">(current)</span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_scrapers.php' ? 'active' : '' ?>" href="admin_scrapers.php">
                    <i class="fas fa-spider me-2"></i>
                    Scraper Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'activities.php' ? 'active' : '' ?>" href="activities.php">
                    <i class="fas fa-running me-2"></i>
                    Activity Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>" href="categories.php">
                    <i class="fas fa-tags me-2"></i>
                    Categories
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
            <span>Reports</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>" href="logs.php">
                    <i class="fas fa-clipboard-list me-2"></i>
                    System Logs
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
            <span>Settings</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    System Settings
                </a>
            </li>
        </ul>

        <!-- Quick Stats -->
        <div class="mt-4 px-3">
            <h6 class="sidebar-heading text-muted text-uppercase">Quick Stats</h6>
            <div class="card bg-light border-0">
                <div class="card-body p-2">
                    <small class="text-muted">
                        <?php
                        // Quick stats for sidebar
                        try {
                            $appDatabase = new Database('kidssmart_app');
                            $appDb = $appDatabase->getConnection();
                            
                            $pendingStmt = $appDb->query("SELECT COUNT(*) as count FROM activities WHERE is_approved = 0");
                            $pendingCount = $pendingStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            
                            $totalStmt = $appDb->query("SELECT COUNT(*) as count FROM activities");
                            $totalCount = $totalStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            
                            echo "<div class='d-flex justify-content-between'>";
                            echo "<span>Total:</span><span class='fw-bold'>$totalCount</span>";
                            echo "</div>";
                            echo "<div class='d-flex justify-content-between'>";
                            echo "<span>Pending:</span><span class='fw-bold text-warning'>$pendingCount</span>";
                            echo "</div>";
                        } catch (Exception $e) {
                            echo "<span class='text-muted'>Stats unavailable</span>";
                        }
                        ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.sidebar .nav-link {
    color: #333;
    transition: all 0.2s ease;
}

.sidebar .nav-link:hover {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
    font-weight: 500;
}

.sidebar .nav-link i {
    width: 16px;
    text-align: center;
}

.sidebar-heading {
    font-size: 0.75rem;
    font-weight: 600;
}

@media (max-width: 767.98px) {
    .sidebar {
        position: relative;
        height: auto;
    }
    
    .sidebar-sticky {
        height: auto;
        overflow-y: visible;
    }
}
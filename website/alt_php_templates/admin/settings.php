<?php
// Set page title
$page_title = 'System Settings';

// Session and auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle settings update
if ($_POST['action'] ?? false) {
    try {
        // Here you would save settings to database or config file
        $message = "Settings saved successfully!";
    } catch (Exception $e) {
        $error = "Error saving settings: " . $e->getMessage();
    }
}

// Load current settings (example values)
$settings = [
    'site_name' => 'KidsSmart',
    'site_email' => 'admin@kidssmart.com',
    'activities_per_page' => 20,
    'auto_approve' => false,
    'scraper_schedule' => 'daily',
    'maintenance_mode' => false
];
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-cog me-2"></i>System Settings
                </h1>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <form method="post">
                        <input type="hidden" name="action" value="save_settings">
                        
                        <!-- General Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">General Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" class="form-control" name="site_name" 
                                           value="<?= htmlspecialchars($settings['site_name']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Admin Email</label>
                                    <input type="email" class="form-control" name="site_email" 
                                           value="<?= htmlspecialchars($settings['site_email']) ?>">
                                    <small class="text-muted">Email for system notifications</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Activities Per Page</label>
                                    <input type="number" class="form-control" name="activities_per_page" 
                                           value="<?= $settings['activities_per_page'] ?>" min="10" max="100">
                                </div>
                            </div>
                        </div>

                        <!-- Activity Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Activity Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="auto_approve" 
                                           id="autoApprove" <?= $settings['auto_approve'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="autoApprove">
                                        Auto-approve new activities
                                    </label>
                                    <small class="d-block text-muted">Automatically approve activities from scrapers</small>
                                </div>
                            </div>
                        </div>

                        <!-- Scraper Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Scraper Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Scraper Schedule</label>
                                    <select class="form-select" name="scraper_schedule">
                                        <option value="hourly" <?= $settings['scraper_schedule'] === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                                        <option value="daily" <?= $settings['scraper_schedule'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                                        <option value="weekly" <?= $settings['scraper_schedule'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                        <option value="manual" <?= $settings['scraper_schedule'] === 'manual' ? 'selected' : '' ?>>Manual Only</option>
                                    </select>
                                    <small class="text-muted">How often to run scrapers automatically</small>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">System Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                           id="maintenanceMode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="maintenanceMode">
                                        Maintenance Mode
                                    </label>
                                    <small class="d-block text-muted">Disable public access to the site</small>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="d-flex justify-content-end mb-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Side Panel -->
                <div class="col-lg-4">
                    <!-- System Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?= PHP_VERSION ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Software:</strong></td>
                                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>MySQL Version:</strong></td>
                                    <td>
                                        <?php
                                        try {
                                            require_once '../config/database.php';
                                            $db = new Database('kidssmart_app');
                                            $version = $db->getConnection()->query('SELECT VERSION()')->fetchColumn();
                                            echo $version;
                                        } catch (Exception $e) {
                                            echo 'Unknown';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Danger Zone</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">These actions cannot be undone!</p>
                            <button class="btn btn-outline-danger w-100 mb-2" disabled>
                                <i class="fas fa-database me-1"></i> Clear All Activities
                            </button>
                            <button class="btn btn-outline-danger w-100" disabled>
                                <i class="fas fa-trash me-1"></i> Reset Database
                            </button>
                            <small class="text-muted d-block mt-2">
                                Contact system administrator to perform these actions.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
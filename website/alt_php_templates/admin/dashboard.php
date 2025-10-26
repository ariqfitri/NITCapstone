<?php
session_start();
require_once '../config/database.php';
require_once '../models/Program.php';

// Check if admin is logged in
if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$program = new Program($db);

// Handle actions
if ($_POST['action'] ?? false) {
    $activity_id = $_POST['activity_id'] ?? 0;
    
    if ($_POST['action'] === 'approve' && $activity_id) {
        $stmt = $db->prepare("UPDATE activities SET is_approved = 1 WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        $message = "Activity approved successfully!";
    } elseif ($_POST['action'] === 'reject' && $activity_id) {
        $stmt = $db->prepare("UPDATE activities SET is_approved = 0 WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        $message = "Activity rejected successfully!";
    } elseif ($_POST['action'] === 'delete' && $activity_id) {
        $stmt = $db->prepare("DELETE FROM activities WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        $message = "Activity deleted successfully!";
    } elseif ($_POST['action'] === 'approve_all') {
        $stmt = $db->prepare("UPDATE activities SET is_approved = 1");
        $stmt->execute();
        $message = "All activities approved successfully!";
    }
}

// Get pending and approved activities
$pending_activities = $program->getPendingActivities();
$approved_activities = $program->getApprovedActivities();
$total_pending = count($pending_activities);
$total_approved = count($approved_activities);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">KidsSmart Admin</span>
            <div class="navbar-nav ms-auto">
                <a href="../index.php" class="nav-link" target="_blank">View Site</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h4><?= $total_pending ?></h4>
                        <p>Pending Approval</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h4><?= $total_approved ?></h4>
                        <p>Approved Activities</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h4><?= $total_pending + $total_approved ?></h4>
                        <p>Total Activities</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <form method="post" class="d-grid">
                            <input type="hidden" name="action" value="approve_all">
                            <button type="submit" class="btn btn-warning btn-sm" 
                                    onclick="return confirm('Approve ALL pending activities?')">
                                Approve All Pending
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Activities -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h4 class="mb-0">Pending Approval (<?= $total_pending ?>)</h4>
            </div>
            <div class="card-body">
                <?php if ($total_pending > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Source</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                            <?php if (!empty($activity['description'])): ?>
                                                <br><small class="text-muted"><?= substr(htmlspecialchars($activity['description']), 0, 100) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['category'] ?? 'N/A') ?></td>
                                        <td>
                                            <?= htmlspecialchars($activity['suburb'] ?? 'N/A') ?>
                                            <?php if (!empty($activity['postcode'])): ?>
                                                , <?= htmlspecialchars($activity['postcode']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['source_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-warning btn-sm">Reject</button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Delete this activity permanently?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No pending activities for approval.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approved Activities -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Approved Activities (<?= $total_approved ?>)</h4>
            </div>
            <div class="card-body">
                <?php if ($total_approved > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Source</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved_activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                            <?php if (!empty($activity['description'])): ?>
                                                <br><small class="text-muted"><?= substr(htmlspecialchars($activity['description']), 0, 100) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['category'] ?? 'N/A') ?></td>
                                        <td>
                                            <?= htmlspecialchars($activity['suburb'] ?? 'N/A') ?>
                                            <?php if (!empty($activity['postcode'])): ?>
                                                , <?= htmlspecialchars($activity['postcode']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['source_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-warning btn-sm">Unapprove</button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Delete this activity permanently?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No approved activities yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/User.php';

redirect_if_not_logged_in();

$database = new Database('kidssmart_users');
$db = $database->getConnection();
$user = new User($db);

$user->user_id = get_current_user_id();
$user->readOne();

$errors = [];
$success = false;

if ($_POST['update_profile'] ?? false) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid form submission";
    } else {
        $user->first_name = sanitize_input($_POST['first_name'] ?? '');
        $user->last_name = sanitize_input($_POST['last_name'] ?? '');
        $user->suburb = sanitize_input($_POST['suburb'] ?? '');
        $user->postcode = sanitize_input($_POST['postcode'] ?? '');
        $user->child_age_range = sanitize_input($_POST['child_age_range'] ?? '');

        if (empty($user->first_name)) {
            $errors[] = "First name is required";
        }

        if (empty($errors)) {
            if ($user->update()) {
                // Update session data
                $_SESSION['user_data']['first_name'] = $user->first_name;
                $_SESSION['user_data']['last_name'] = $user->last_name;
                $success = true;
            } else {
                $errors[] = "Failed to update profile";
            }
        }
    }
}

if ($_POST['change_password'] ?? false) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid form submission";
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password)) {
            $errors[] = "Current password is required";
        }
        if (empty($new_password)) {
            $errors[] = "New password is required";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }

        if (empty($errors)) {
            // Verify current password
            $temp_user = new User($db);
            $temp_user->username = $user->username;
            $temp_user->password_hash = $current_password;
            
            if ($temp_user->login()) {
                $user->password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                if ($user->updatePassword()) {
                    $success = true;
                    $password_success = "Password updated successfully!";
                } else {
                    $errors[] = "Failed to update password";
                }
            } else {
                $errors[] = "Current password is incorrect";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">My Profile</h1>

                <?php if ($success && isset($password_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $password_success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Profile updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?= $error ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Profile Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?= htmlspecialchars($user->first_name) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?= htmlspecialchars($user->last_name) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user->username) ?>" disabled>
                                        <div class="form-text">Username cannot be changed</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user->email) ?>" disabled>
                                        <div class="form-text">Email cannot be changed</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="suburb" class="form-label">Suburb</label>
                                        <input type="text" class="form-control" id="suburb" name="suburb" 
                                               value="<?= htmlspecialchars($user->suburb) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="postcode" class="form-label">Postcode</label>
                                        <input type="text" class="form-control" id="postcode" name="postcode" 
                                               value="<?= htmlspecialchars($user->postcode) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="child_age_range" class="form-label">Child's Age Range</label>
                                <select class="form-select" id="child_age_range" name="child_age_range">
                                    <option value="">Select age range</option>
                                    <option value="0-3 years" <?= $user->child_age_range == '0-3 years' ? 'selected' : '' ?>>0-3 years</option>
                                    <option value="4-6 years" <?= $user->child_age_range == '4-6 years' ? 'selected' : '' ?>>4-6 years</option>
                                    <option value="7-12 years" <?= $user->child_age_range == '7-12 years' ? 'selected' : '' ?>>7-12 years</option>
                                    <option value="13-18 years" <?= $user->child_age_range == '13-18 years' ? 'selected' : '' ?>>13-18 years</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_profile" value="1" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Change Password</h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">At least 6 characters</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="change_password" value="1" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
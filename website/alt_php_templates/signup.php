<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/User.php';

redirect_if_logged_in();

$database = new Database('kidssmart_users');
$db = $database->getConnection();
$user = new User($db);

$errors = [];
$success = false;

if ($_POST['signup'] ?? false) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid form submission";
    } else {
        // Get form data
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $suburb = sanitize_input($_POST['suburb'] ?? '');
        $postcode = sanitize_input($_POST['postcode'] ?? '');
        $child_age_range = sanitize_input($_POST['child_age_range'] ?? '');

        // Validation
        if (empty($username)) $errors[] = "Username is required";
        if (empty($email)) $errors[] = "Email is required";
        if (empty($password)) $errors[] = "Password is required";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";

        // Check if username/email exists
        $user->username = $username;
        if ($user->usernameExists()) $errors[] = "Username already taken";

        $user->email = $email;
        if ($user->emailExists()) $errors[] = "Email already registered";

        if (empty($errors)) {
            $user->username = $username;
            $user->email = $email;
            $user->password_hash = password_hash($password, PASSWORD_DEFAULT);
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->suburb = $suburb;
            $user->postcode = $postcode;
            $user->child_age_range = $child_age_range;

            if ($user->create()) {
                $success = true;
                // Auto-login after successful registration
                if ($user->login()) {
                    $_SESSION['user_id'] = $user->user_id;
                    $_SESSION['user_data'] = [
                        'username' => $user->username,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name
                    ];
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $errors[] = "Registration failed. Please try again.";
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
    <title>Sign Up - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Create Your Account</h2>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Registration successful! Redirecting...
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <div><?= $error ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?= $_POST['first_name'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?= $_POST['last_name'] ?? '' ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= $_POST['username'] ?? '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= $_POST['email'] ?? '' ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">At least 6 characters</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="suburb" class="form-label">Suburb</label>
                                        <input type="text" class="form-control" id="suburb" name="suburb" 
                                               value="<?= $_POST['suburb'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="postcode" class="form-label">Postcode</label>
                                        <input type="text" class="form-control" id="postcode" name="postcode" 
                                               value="<?= $_POST['postcode'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="child_age_range" class="form-label">Child's Age Range</label>
                                <select class="form-select" id="child_age_range" name="child_age_range">
                                    <option value="">Select age range</option>
                                    <option value="0-3 years" <?= ($_POST['child_age_range'] ?? '') == '0-3 years' ? 'selected' : '' ?>>0-3 years</option>
                                    <option value="4-6 years" <?= ($_POST['child_age_range'] ?? '') == '4-6 years' ? 'selected' : '' ?>>4-6 years</option>
                                    <option value="7-12 years" <?= ($_POST['child_age_range'] ?? '') == '7-12 years' ? 'selected' : '' ?>>7-12 years</option>
                                    <option value="13-18 years" <?= ($_POST['child_age_range'] ?? '') == '13-18 years' ? 'selected' : '' ?>>13-18 years</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="signup" value="1" class="btn btn-primary btn-lg">Create Account</button>
                            </div>

                            <div class="text-center mt-3">
                                <p>Already have an account? <a href="login.php">Login here</a></p>
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
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>KidsSmart</h5>
                <p>Helping parents find the best activities for their children.</p>
            </div>
            <div class="col-md-3">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white">Home</a></li>
                    <li><a href="search.php" class="text-white">Find Activities</a></li>
                    <li><a href="categories.php" class="text-white">Categories</a></li>
                    <li><a href="about.php" class="text-white">About Us</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Account</h5>
                <ul class="list-unstyled">
                    <?php if (is_logged_in()): ?>
                        <li><a href="dashboard.php" class="text-white">Dashboard</a></li>
                        <li><a href="profile.php" class="text-white">Profile</a></li>
                        <li><a href="favourites.php" class="text-white">Favourites</a></li>
                        <li><a href="logout.php" class="text-white">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="text-white">Login</a></li>
                        <li><a href="signup.php" class="text-white">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p>&copy; 2025 KidsSmart. All rights reserved.</p>
        </div>
    </div>
</footer>
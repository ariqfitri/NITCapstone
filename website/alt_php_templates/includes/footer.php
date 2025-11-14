<footer class="bg-dark text-white mt-5" style="padding: 50px 0 20px 0;">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5><i class="fas fa-child me-2"></i>KidsSmart</h5>
                <p>Helping parents find the best activities for their children. Safe, fun, and educational experiences in your local area.</p>
                <div class="social-links">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
            </div>
            <div class="col-md-2">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white">Home</a></li>
                    <li><a href="search.php" class="text-white">Find Activities</a></li>
                    <li><a href="categories.php" class="text-white">Categories</a></li>
                    <li><a href="about.php" class="text-white">About Us</a></li>
                    <li><a href="contact.php" class="text-white">Contact</a></li>
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
            <div class="col-md-3">
                <h5>Legal</h5>
                <ul class="list-unstyled">
                    <li><a href="privacy.php" class="text-white">Privacy Policy</a></li>
                    <li><a href="terms.php" class="text-white">Terms &amp; Conditions</a></li>
                    <li><a href="sitemap.php" class="text-white">Site Map</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p>&copy; 2025 KidsSmart. All rights reserved. | ABN: 12 345 678 901</p>
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>Secure &amp; Safe | 
                <i class="fas fa-heart me-1"></i>Trusted by Parents
            </small>
        </div>
    </div>
</footer>
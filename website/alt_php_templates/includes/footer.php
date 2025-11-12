<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold">
                    <i class="fas fa-child text-primary"></i> KidsSmart
                </h5>
                <p class="mb-3">Helping parents find the best activities for their children. Safe, fun, and educational experiences in your local area.</p>
                
                <!-- Social Media Links -->
                <div class="social-links">
                    <a href="#" class="text-white me-3" aria-label="Facebook">
                        <i class="fab fa-facebook-f fa-lg"></i>
                    </a>
                    <a href="#" class="text-white me-3" aria-label="Twitter">
                        <i class="fab fa-twitter fa-lg"></i>
                    </a>
                    <a href="#" class="text-white me-3" aria-label="Instagram">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <a href="#" class="text-white" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in fa-lg"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-3 mb-4">
                <h6 class="fw-bold">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="search.php" class="text-white-50 text-decoration-none">Find Activities</a></li>
                    <li class="mb-2"><a href="categories.php" class="text-white-50 text-decoration-none">Categories</a></li>
                    <li class="mb-2"><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-3 mb-4">
                <h6 class="fw-bold">Account</h6>
                <ul class="list-unstyled">
                    <?php if (is_logged_in()): ?>
                        <li class="mb-2"><a href="dashboard.php" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li class="mb-2"><a href="profile.php" class="text-white-50 text-decoration-none">Profile</a></li>
                        <li class="mb-2"><a href="favourites.php" class="text-white-50 text-decoration-none">Favourites</a></li>
                        <li class="mb-2"><a href="logout.php" class="text-white-50 text-decoration-none">Logout</a></li>
                    <?php else: ?>
                        <li class="mb-2"><a href="login.php" class="text-white-50 text-decoration-none">Login</a></li>
                        <li class="mb-2"><a href="signup.php" class="text-white-50 text-decoration-none">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-3 mb-4">
                <h6 class="fw-bold">Legal</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="privacy-policy.php" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                    <li class="mb-2"><a href="terms-conditions.php" class="text-white-50 text-decoration-none">Terms & Conditions</a></li>
                    <li class="mb-2"><a href="cookie-policy.php" class="text-white-50 text-decoration-none">Cookie Policy</a></li>
                    <li class="mb-2"><a href="sitemap.php" class="text-white-50 text-decoration-none">Site Map</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-3 mb-4">
                <h6 class="fw-bold">Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="help.php" class="text-white-50 text-decoration-none">Help Center</a></li>
                    <li class="mb-2"><a href="faq.php" class="text-white-50 text-decoration-none">FAQ</a></li>
                    <li class="mb-2"><a href="feedback.php" class="text-white-50 text-decoration-none">Feedback</a></li>
                    <li class="mb-2"><a href="report-issue.php" class="text-white-50 text-decoration-none">Report Issue</a></li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4">
        
        <!-- Copyright and Additional Info -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-white-50">
                    &copy; <?= date('Y') ?> KidsSmart. All rights reserved. 
                    <span class="d-none d-md-inline">| ABN: 12 345 678 901</span>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-white-50">
                    <small>
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure & Safe | 
                        <i class="fas fa-award me-1"></i>
                        Trusted by Parents
                    </small>
                </p>
            </div>
        </div>
    </div>
</footer>
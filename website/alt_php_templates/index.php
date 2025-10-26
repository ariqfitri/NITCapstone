<?php
    require_once 'config/db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSmart - Find After-School Programs</title>
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #f6c23e;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fc;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .logo h1 {
            color: var(--primary);
            font-size: 24px;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--primary);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s, transform 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: #3a5ccc;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #e5b53a;
        }
        
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('https://images.unsplash.com/photo-1564429097439-923c82a4e5cd');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .search-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .form-group {
            flex: 1 1 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-btn {
            margin-top: 24px;
            height: 42px;
        }
        
        .section {
            padding: 60px 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-header h2 {
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .section-header p {
            color: #777;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .program-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .program-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .program-img {
            height: 200px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .program-img img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .program-details {
            padding: 20px;
        }
        
        .program-category {
            display: inline-block;
            background-color: var(--info);
            color: white;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .program-title {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .program-info {
            margin-bottom: 15px;
            color: #777;
        }
        
        .program-rating {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stars {
            color: var(--warning);
            margin-right: 5px;
        }
        
        .review-count {
            color: #777;
            font-size: 14px;
        }
        
        .program-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .program-price {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .feature-card {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            background-color: var(--light);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary);
            font-size: 30px;
        }
        
        .feature-title {
            font-size: 20px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-logo {
            margin-bottom: 15px;
        }
        
        .footer-logo h2 {
            color: white;
            font-size: 24px;
        }
        
        .footer-description {
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: background-color 0.3s;
        }
        
        .social-links a:hover {
            background-color: var(--primary);
        }
        
        .footer-title {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            text-align: center;
            color: #aaa;
            font-size: 14px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--primary);
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            font-size: 24px;
        }
        
        .auth-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .auth-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .auth-form button {
            width: 100%;
        }
        
        .form-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .form-footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-btn {
                margin-top: 0;
                height: auto;
                width: 100%;
            }
            
            .hero {
                padding: 60px 0;
            }
            
            .hero h2 {
                font-size: 28px;
            }
            
            .section {
                padding: 40px 0;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="https://via.placeholder.com/40x40" alt="KidsSmart Logo">
                    <h1>KidsSmart</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Programs</a></li>
                        <li><a href="#">About</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#" id="loginBtn">Login</a></li>
                        <li><a href="#" id="registerBtn" class="btn btn-primary">Register</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>Find the Perfect After-School Programs for Your Child</h2>
            <p>Discover and compare hundreds of quality programs in Melbourne and surrounding areas</p>
            
            <div class="search-container">
                <form class="search-form">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <select id="location" class="form-control">
                            <option value="">All Suburbs</option>
                            <option value="footscray">Footscray</option>
                            <option value="brunswick">Brunswick</option>
                            <option value="carlton">Carlton</option>
                            <option value="hawthorn">Hawthorn</option>
                            <option value="malvern">Malvern</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" class="form-control">
                            <option value="">All Categories</option>
                            <option value="art">Art</option>
                            <option value="sports">Sports</option>
                            <option value="music">Music</option>
                            <option value="stem">STEM</option>
                            <option value="dance">Dance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ageGroup">Age Group</label>
                        <select id="ageGroup" class="form-control">
                            <option value="">All Ages</option>
                            <option value="3-5">3-5 years</option>
                            <option value="6-8">6-8 years</option>
                            <option value="9-12">9-12 years</option>
                            <option value="13-18">13-18 years</option>
                        </select>
                    </div>
                    <button type="submit" class="btn search-btn">Search Programs</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Featured Programs Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>Featured Programs</h2>
                <p>Discover top-rated after-school activities for your children</p>
            </div>
            
            <div class="programs-grid">
                <!-- Program 1 -->
                <div class="program-card">
                    <div class="program-img">
                        <img src="https://via.placeholder.com/300x200" alt="Robyn's Room Art Class">
                    </div>
                    <div class="program-details">
                        <span class="program-category">Art</span>
                        <h3 class="program-title">Robyn's Room - Creative Art and Craft</h3>
                        <div class="program-info">
                            <p>Mornington, VIC 3931</p>
                            <p>Ages: 5-12 years</p>
                        </div>
                        <div class="program-rating">
                            <div class="stars">★★★★★</div>
                            <span class="review-count">(18 reviews)</span>
                        </div>
                        <div class="program-footer">
                            <span class="program-price">$20/session</span>
                            <a href="#" class="btn btn-secondary">View Details</a>
                        </div>
                    </div>
                </div>
                
                <!-- Program 2 -->
                <div class="program-card">
                    <div class="program-img">
                        <img src="https://via.placeholder.com/300x200" alt="Paint With Me Melbourne">
                    </div>
                    <div class="program-details">
                        <span class="program-category">Art</span>
                        <h3 class="program-title">Paint With Me Melbourne</h3>
                        <div class="program-info">
                            <p>Northcote, VIC 3070</p>
                            <p>Ages: 8-14 years</p>
                        </div>
                        <div class="program-rating">
                            <div class="stars">★★★★☆</div>
                            <span class="review-count">(12 reviews)</span>
                        </div>
                        <div class="program-footer">
                            <span class="program-price">$25/session</span>
                            <a href="#" class="btn btn-secondary">View Details</a>
                        </div>
                    </div>
                </div>
                
                <!-- Program 3 -->
                <div class="program-card">
                    <div class="program-img">
                        <img src="https://via.placeholder.com/300x200" alt="Angel Art Studio">
                    </div>
                    <div class="program-details">
                        <span class="program-category">Art</span>
                        <h3 class="program-title">Angel Art Studio</h3>
                        <div class="program-info">
                            <p>Heidelberg Heights, VIC 3081</p>
                            <p>Ages: 9-15 years</p>
                        </div>
                        <div class="program-rating">
                            <div class="stars">★★★★☆</div>
                            <span class="review-count">(9 reviews)</span>
                        </div>
                        <div class="program-footer">
                            <span class="program-price">$22/session</span>
                            <a href="#" class="btn btn-secondary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section" style="background-color: #f1f5fe;">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose KidsSmart</h2>
                <p>Our platform makes finding after-school programs simple and stress-free</p>
            </div>
            
            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3 class="feature-title">Easy Search</h3>
                    <p>Find programs by location, category, age group, and more with our advanced filters</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3 class="feature-title">Trusted Reviews</h3>
                    <p>Read authentic reviews from parents who have experienced the programs firsthand</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3 class="feature-title">Stay Updated</h3>
                    <p>Receive notifications about new programs and special offers in your area</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="#" class="btn btn-primary">Browse All Programs</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div>
                    <div class="footer-logo">
                        <h2>KidsSmart</h2>
                    </div>
                    <p class="footer-description">Melbourne's first centralized platform for discovering after-school programs for children.</p>
                    <div class="social-links">
                        <a href="#"><span>f</span></a>
                        <a href="#"><span>t</span></a>
                        <a href="#"><span>in</span></a>
                        <a href="#"><span>ig</span></a>
                    </div>
                </div>
                
                <div>
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Programs</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">Categories</h3>
                    <ul class="footer-links">
                        <li><a href="#">Art & Craft</a></li>
                        <li><a href="#">Sports & Activities</a></li>
                        <li><a href="#">Music & Dance</a></li>
                        <li><a href="#">STEM & Education</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">Contact Us</h3>
                    <ul class="footer-links">
                        <li>Email: info@kidssmart.com</li>
                        <li>Phone: (03) 1234 5678</li>
                        <li>Address: Melbourne, VIC 3000</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 KidsSmart. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2>Login</h2>
            </div>
            <form class="auth-form">
                <div>
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" required>
                </div>
                <div>
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <div class="form-footer">
                    <p>Don't have an account? <a href="#" id="switchToRegister">Register</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2>Register</h2>
            </div>
            <form class="auth-form">
                <div>
                    <label for="registerFirstName">First Name</label>
                    <input type="text" id="registerFirstName" required>
                </div>
                <div>
                    <label for="registerLastName">Last Name</label>
                    <input type="text" id="registerLastName" required>
                </div>
                <div>
                    <label for="registerEmail">Email</label>
                    <input type="email" id="registerEmail" required>
                </div>
                <div>
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" required>
                </div>
                <div>
                    <label for="registerSuburb">Suburb</label>
                    <input type="text" id="registerSuburb" required>
                </div>
                <button type="submit" class="btn btn-primary">Register</button>
                <div class="form-footer">
                    <p>Already have an account? <a href="#" id="switchToLogin">Login</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const closeBtns = document.getElementsByClassName('close');
        const switchToRegister = document.getElementById('switchToRegister');
        const switchToLogin = document.getElementById('switchToLogin');

        loginBtn.onclick = function() {
            loginModal.style.display = 'block';
        }

        registerBtn.onclick = function() {
            registerModal.style.display = 'block';
        }

        for (let i = 0; i < closeBtns.length; i++) {
            closeBtns[i].onclick = function() {
                loginModal.style.display = 'none';
                registerModal.style.display = 'none';
            }
        }

        switchToRegister.onclick = function(e) {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'block';
        }

        switchToLogin.onclick = function(e) {
            e.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'block';
        }

        window.onclick = function(event) {
            if (event.target == loginModal) {
                loginModal.style.display = 'none';
            } else if (event.target == registerModal) {
                registerModal.style.display = 'none';
            }
        }

        // Form submission (simulating database connection)
        document.querySelectorAll('.auth-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                alert('This would connect to the KidsSmart database in a real implementation.');
                loginModal.style.display = 'none';
                registerModal.style.display = 'none';
            });
        });

        // Search form (simulating database query)
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const location = document.getElementById('location').value;
            const category = document.getElementById('category').value;
            const ageGroup = document.getElementById('ageGroup').value;
            
            alert(`Searching for programs with: \nLocation: ${location || 'All'}\nCategory: ${category || 'All'}\nAge Group: ${ageGroup || 'All'}`);
            
            // In a real implementation, this would redirect to a results page or update the current view with filtered results
        });
    </script>
</body>
</html>
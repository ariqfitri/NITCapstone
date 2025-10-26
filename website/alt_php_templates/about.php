<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-child"></i> KidsSmart
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="search.php"><i class="fas fa-search"></i> Find Activities</a>
                <a class="nav-link" href="categories.php"><i class="fas fa-list"></i> Categories</a>
                <a class="nav-link active" href="about.php"><i class="fas fa-info-circle"></i> About</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="text-center mb-4">About KidsSmart</h1>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Our Mission</h3>
                        <p class="card-text">
                            KidsSmart is dedicated to helping parents and guardians find the perfect activities 
                            for their children. We believe every child deserves access to quality sports, arts, 
                            education, and recreational programs that help them grow, learn, and have fun.
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">What We Offer</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h5><i class="fas fa-search text-primary"></i> Comprehensive Search</h5>
                                <p>Find activities by category, location, age range, and more.</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5><i class="fas fa-map-marker-alt text-primary"></i> Local Focus</h5>
                                <p>Discover programs in your neighborhood and surrounding areas.</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5><i class="fas fa-info-circle text-primary"></i> Detailed Information</h5>
                                <p>Get all the details you need - schedules, costs, contact info, and more.</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5><i class="fas fa-sync-alt text-primary"></i> Always Updated</h5>
                                <p>Our database is continuously updated with new programs and information.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">For Activity Providers</h3>
                        <p>
                            Are you an activity provider? We're always looking to expand our database with 
                            quality programs for children. While we currently aggregate information from 
                            various sources, we're working on direct submission features for providers.
                        </p>
                        <p class="mb-0">
                            <strong>Contact us to learn more about featuring your programs.</strong>
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title">Get Started Today</h3>
                        <p class="card-text">
                            Ready to find amazing activities for your children? Start exploring now!
                        </p>
                        <a href="search.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-search"></i> Find Activities
                        </a>
                        <a href="categories.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-list"></i> Browse Categories
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="text-center">
                <p>&copy; 2024 KidsSmart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
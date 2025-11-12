<?php
// Privacy Policy Page
$page_title = 'Privacy Policy - KidsSmart';
$page_description = 'Learn how KidsSmart collects, uses, and protects your personal information. Our commitment to your privacy and data security.';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once 'config/auth.php';

// Last updated date
$last_updated = 'December 15, 2024';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/privacy-policy.php') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="static/images/favicon.ico">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
    
    <style>
        .legal-document {
            line-height: 1.7;
        }
        .section-header {
            color: #0d6efd;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 20px;
            margin: 20px 0;
        }
        .contact-info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .toc {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .toc a {
            text-decoration: none;
            color: #495057;
            padding: 5px 0;
            display: block;
        }
        .toc a:hover {
            color: #0d6efd;
            padding-left: 10px;
            transition: all 0.2s ease;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-4 mb-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Privacy Policy</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <article class="legal-document">
                    <!-- Header -->
                    <div class="mb-4">
                        <h1 class="display-5 fw-bold text-primary">Privacy Policy</h1>
                        <p class="lead text-muted">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Last updated: <?= $last_updated ?>
                        </p>
                    </div>

                    <!-- Key Points Highlight -->
                    <div class="highlight-box">
                        <h4><i class="fas fa-key text-primary me-2"></i>Key Points</h4>
                        <ul class="mb-0">
                            <li>We never sell your personal information</li>
                            <li>We only collect information necessary to provide our services</li>
                            <li>You have full control over your data and can delete it anytime</li>
                            <li>We use industry-standard security measures to protect your information</li>
                            <li>We're transparent about how we use your data</li>
                        </ul>
                    </div>

                    <!-- Table of Contents -->
                    <div class="toc">
                        <h5><i class="fas fa-list me-2"></i>Table of Contents</h5>
                        <a href="#information-we-collect">1. Information We Collect</a>
                        <a href="#how-we-use">2. How We Use Your Information</a>
                        <a href="#information-sharing">3. Information Sharing</a>
                        <a href="#data-security">4. Data Security</a>
                        <a href="#your-rights">5. Your Rights</a>
                        <a href="#cookies">6. Cookies and Tracking</a>
                        <a href="#childrens-privacy">7. Children's Privacy</a>
                        <a href="#data-retention">8. Data Retention</a>
                        <a href="#contact-us">9. Contact Us</a>
                    </div>

                    <!-- Introduction -->
                    <section>
                        <p>
                            At KidsSmart, we take your privacy seriously. This Privacy Policy explains how we collect, 
                            use, disclose, and safeguard your information when you use our website and services. 
                            We are committed to protecting your personal information and being transparent about our 
                            data practices.
                        </p>
                    </section>

                    <!-- Section 1: Information We Collect -->
                    <section id="information-we-collect">
                        <h2 class="section-header">1. Information We Collect</h2>
                        
                        <h4>Personal Information You Provide</h4>
                        <ul>
                            <li><strong>Account Information:</strong> Name, email address, username, and password</li>
                            <li><strong>Profile Information:</strong> Suburb, postcode, child's age range, and preferences</li>
                            <li><strong>Communication:</strong> Messages through contact forms or support</li>
                        </ul>

                        <h4>Information Automatically Collected</h4>
                        <ul>
                            <li><strong>Usage Data:</strong> Pages visited, search queries, interaction patterns</li>
                            <li><strong>Device Information:</strong> IP address, browser type, device information</li>
                            <li><strong>Location Data:</strong> General location based on IP address</li>
                        </ul>
                    </section>

                    <!-- Section 2: How We Use Information -->
                    <section id="how-we-use">
                        <h2 class="section-header">2. How We Use Your Information</h2>
                        
                        <ul>
                            <li>Provide and maintain our activity finder service</li>
                            <li>Process your searches and provide relevant results</li>
                            <li>Send account-related notifications</li>
                            <li>Improve our services and user experience</li>
                            <li>Ensure security and prevent fraud</li>
                        </ul>
                    </section>

                    <!-- Section 3: Information Sharing -->
                    <section id="information-sharing">
                        <h2 class="section-header">3. Information Sharing</h2>
                        
                        <div class="highlight-box">
                            <strong>We do not sell your personal information to third parties.</strong>
                        </div>

                        <p>We may share information only in limited circumstances:</p>
                        <ul>
                            <li>With service providers who help operate our website</li>
                            <li>With activity providers when you express interest (with consent)</li>
                            <li>When required by law or to protect our rights</li>
                            <li>In case of business transfers (with notification)</li>
                        </ul>
                    </section>

                    <!-- Section 4: Data Security -->
                    <section id="data-security">
                        <h2 class="section-header">4. Data Security</h2>
                        
                        <p>We implement security measures including:</p>
                        <ul>
                            <li>SSL/TLS encryption for data transmission</li>
                            <li>Secure password hashing</li>
                            <li>Regular security updates and monitoring</li>
                            <li>Access controls and staff training</li>
                        </ul>
                    </section>

                    <!-- Section 5: Your Rights -->
                    <section id="your-rights">
                        <h2 class="section-header">5. Your Rights</h2>
                        
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access your personal information</li>
                            <li>Update or correct your data</li>
                            <li>Delete your account and data</li>
                            <li>Opt-out of marketing communications</li>
                            <li>Download your data</li>
                        </ul>

                        <div class="contact-info">
                            <h5><i class="fas fa-envelope me-2"></i>Exercise Your Rights</h5>
                            <p>Contact us at <strong>privacy@kidssmart.com</strong> to exercise your rights.</p>
                        </div>
                    </section>

                    <!-- Section 6: Cookies -->
                    <section id="cookies">
                        <h2 class="section-header">6. Cookies and Tracking</h2>
                        
                        <p>We use cookies to:</p>
                        <ul>
                            <li>Keep you logged in</li>
                            <li>Remember your preferences</li>
                            <li>Analyze website usage</li>
                            <li>Improve user experience</li>
                        </ul>

                        <p>You can control cookies through your browser settings.</p>
                    </section>

                    <!-- Section 7: Children's Privacy -->
                    <section id="childrens-privacy">
                        <h2 class="section-header">7. Children's Privacy</h2>
                        
                        <div class="highlight-box">
                            <h5><i class="fas fa-child text-primary me-2"></i>Protection of Children</h5>
                            <p class="mb-0">
                                We do not knowingly collect personal information from children under 13. 
                                Our service is for parents and guardians.
                            </p>
                        </div>
                    </section>

                    <!-- Section 8: Data Retention -->
                    <section id="data-retention">
                        <h2 class="section-header">8. Data Retention</h2>
                        
                        <p>We retain your information:</p>
                        <ul>
                            <li>While your account is active</li>
                            <li>For 3 years after account deletion (legal compliance)</li>
                            <li>Analytics data is anonymized after 24 months</li>
                        </ul>
                    </section>

                    <!-- Section 9: Contact Us -->
                    <section id="contact-us">
                        <h2 class="section-header">9. Contact Us</h2>
                        
                        <div class="contact-info">
                            <h5><i class="fas fa-envelope me-2"></i>Privacy Questions?</h5>
                            <p>Contact our Privacy Officer:</p>
                            <ul class="list-unstyled">
                                <li><strong>Email:</strong> privacy@kidssmart.com</li>
                                <li><strong>Mail:</strong> Privacy Officer, KidsSmart, PO Box 12345, Melbourne VIC 3000</li>
                                <li><strong>Phone:</strong> +61 3 1234 5678</li>
                            </ul>
                        </div>
                    </section>
                </article>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="position-sticky" style="top: 20px;">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i>Privacy Controls</h5>
                        </div>
                        <div class="card-body">
                            <?php if (is_logged_in()): ?>
                                <a href="profile.php" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </a>
                                <a href="data-download.php" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                                    <i class="fas fa-download me-2"></i>Download My Data
                                </a>
                                <a href="delete-account.php" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="fas fa-trash me-2"></i>Delete Account
                                </a>
                            <?php else: ?>
                                <a href="signup.php" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-user-plus me-2"></i>Sign Up
                                </a>
                                <a href="login.php" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Privacy Commitment -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Our Commitment</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>No selling of data</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Transparent practices</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Strong security</li>
                                <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Your data control</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
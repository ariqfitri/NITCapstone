<?php
// Terms & Conditions Page
$page_title = 'Terms & Conditions - KidsSmart';
$page_description = 'Read our Terms & Conditions to understand the rules and guidelines for using KidsSmart services.';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once 'config/auth.php';

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
    
    <link rel="canonical" href="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/terms-conditions.php') ?>">
    <link rel="icon" type="image/x-icon" href="static/images/favicon.ico">
    
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
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
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
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Terms & Conditions</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <article class="legal-document">
                    <div class="mb-4">
                        <h1 class="display-5 fw-bold text-primary">Terms & Conditions</h1>
                        <p class="lead text-muted">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Last updated: <?= $last_updated ?>
                        </p>
                    </div>

                    <!-- Important Notice -->
                    <div class="warning-box">
                        <h4><i class="fas fa-exclamation-triangle text-warning me-2"></i>Important Notice</h4>
                        <p class="mb-0">
                            By using KidsSmart, you agree to these Terms & Conditions. Please read them carefully. 
                            If you don't agree with these terms, please don't use our services.
                        </p>
                    </div>

                    <!-- Table of Contents -->
                    <div class="toc">
                        <h5><i class="fas fa-list me-2"></i>Table of Contents</h5>
                        <a href="#agreement">1. Agreement to Terms</a>
                        <a href="#services">2. Description of Services</a>
                        <a href="#user-accounts">3. User Accounts</a>
                        <a href="#acceptable-use">4. Acceptable Use</a>
                        <a href="#content">5. Content and Intellectual Property</a>
                        <a href="#disclaimers">6. Disclaimers</a>
                        <a href="#limitation-liability">7. Limitation of Liability</a>
                        <a href="#termination">8. Termination</a>
                        <a href="#governing-law">9. Governing Law</a>
                        <a href="#contact-information">10. Contact Information</a>
                    </div>

                    <!-- Section 1: Agreement -->
                    <section id="agreement">
                        <h2 class="section-header">1. Agreement to Terms</h2>
                        <p>
                            These Terms & Conditions ("Terms") govern your use of the KidsSmart website and services 
                            operated by KidsSmart Pty Ltd ("we," "us," or "our"). By accessing or using our services, 
                            you agree to be bound by these Terms.
                        </p>
                        <p>
                            We reserve the right to modify these Terms at any time. Changes will be effective immediately 
                            upon posting. Your continued use of the service after changes constitutes acceptance of the new Terms.
                        </p>
                    </section>

                    <!-- Section 2: Services -->
                    <section id="services">
                        <h2 class="section-header">2. Description of Services</h2>
                        <p>KidsSmart provides:</p>
                        <ul>
                            <li>An online platform to search for children's activities</li>
                            <li>Information about activity providers and programs</li>
                            <li>User accounts to save preferences and favorites</li>
                            <li>Communication tools to connect with activity providers</li>
                        </ul>

                        <div class="highlight-box">
                            <h5><i class="fas fa-info-circle text-primary me-2"></i>Important Clarification</h5>
                            <p class="mb-0">
                                KidsSmart is an information platform only. We do not directly provide activities, 
                                classes, or programs. We connect parents with activity providers but are not responsible 
                                for the quality, safety, or delivery of activities.
                            </p>
                        </div>
                    </section>

                    <!-- Section 3: User Accounts -->
                    <section id="user-accounts">
                        <h2 class="section-header">3. User Accounts</h2>
                        
                        <h4>Account Creation</h4>
                        <p>To use certain features, you must create an account. You agree to:</p>
                        <ul>
                            <li>Provide accurate, current, and complete information</li>
                            <li>Maintain and update your information as needed</li>
                            <li>Keep your password secure and confidential</li>
                            <li>Be responsible for all activities under your account</li>
                        </ul>

                        <h4>Age Requirements</h4>
                        <ul>
                            <li>You must be at least 18 years old to create an account</li>
                            <li>If you are under 18, you need parental consent</li>
                            <li>We may verify your age or request documentation</li>
                        </ul>

                        <h4>Account Security</h4>
                        <ul>
                            <li>You are responsible for maintaining account security</li>
                            <li>Notify us immediately of any unauthorized access</li>
                            <li>We are not liable for losses due to unauthorized use</li>
                        </ul>
                    </section>

                    <!-- Section 4: Acceptable Use -->
                    <section id="acceptable-use">
                        <h2 class="section-header">4. Acceptable Use</h2>
                        
                        <h4>Permitted Uses</h4>
                        <p>You may use our services to:</p>
                        <ul>
                            <li>Search for and learn about children's activities</li>
                            <li>Create and manage your user profile</li>
                            <li>Save favorite activities and preferences</li>
                            <li>Contact activity providers through our platform</li>
                        </ul>

                        <h4>Prohibited Uses</h4>
                        <p>You may NOT:</p>
                        <ul>
                            <li>Use the service for any illegal purpose</li>
                            <li>Impersonate others or provide false information</li>
                            <li>Attempt to gain unauthorized access to our systems</li>
                            <li>Upload malicious software or spam</li>
                            <li>Collect user information for commercial purposes</li>
                            <li>Interfere with the proper functioning of the service</li>
                            <li>Use automated systems to access the service excessively</li>
                        </ul>

                        <div class="warning-box">
                            <h5><i class="fas fa-ban text-warning me-2"></i>Violations</h5>
                            <p class="mb-0">
                                Violation of these terms may result in account suspension or termination 
                                and may be reported to law enforcement if illegal activity is suspected.
                            </p>
                        </div>
                    </section>

                    <!-- Section 5: Content -->
                    <section id="content">
                        <h2 class="section-header">5. Content and Intellectual Property</h2>
                        
                        <h4>Our Content</h4>
                        <p>
                            All content on KidsSmart, including text, graphics, logos, images, and software, 
                            is our property or licensed to us and is protected by copyright and other intellectual property laws.
                        </p>

                        <h4>User-Generated Content</h4>
                        <p>When you submit content (reviews, comments, etc.), you:</p>
                        <ul>
                            <li>Grant us a license to use, modify, and display your content</li>
                            <li>Confirm you have the right to submit the content</li>
                            <li>Agree the content doesn't violate any laws or rights</li>
                            <li>Understand we may remove content at our discretion</li>
                        </ul>

                        <h4>Third-Party Content</h4>
                        <p>
                            Activity information is provided by third-party providers. We don't control or 
                            guarantee the accuracy of this information and aren't responsible for its content.
                        </p>
                    </section>

                    <!-- Section 6: Disclaimers -->
                    <section id="disclaimers">
                        <h2 class="section-header">6. Disclaimers</h2>
                        
                        <div class="warning-box">
                            <h5><i class="fas fa-exclamation-triangle text-warning me-2"></i>No Warranties</h5>
                            <p class="mb-0">
                                Our services are provided "as is" without warranties of any kind. We don't guarantee 
                                the service will be uninterrupted, error-free, or meet your specific needs.
                            </p>
                        </div>

                        <h4>Activity Provider Disclaimer</h4>
                        <ul>
                            <li>We don't screen or verify activity providers</li>
                            <li>We don't guarantee the quality or safety of activities</li>
                            <li>We aren't responsible for interactions between users and providers</li>
                            <li>Parents should conduct their own due diligence</li>
                        </ul>

                        <h4>Information Accuracy</h4>
                        <ul>
                            <li>Activity information may be outdated or inaccurate</li>
                            <li>Prices, schedules, and availability may change</li>
                            <li>Always verify details directly with providers</li>
                        </ul>
                    </section>

                    <!-- Section 7: Limitation of Liability -->
                    <section id="limitation-liability">
                        <h2 class="section-header">7. Limitation of Liability</h2>
                        
                        <div class="highlight-box">
                            <h5><i class="fas fa-shield-alt text-primary me-2"></i>Liability Limits</h5>
                            <p>
                                To the maximum extent permitted by law, KidsSmart shall not be liable for any 
                                indirect, incidental, special, or consequential damages arising from your use of our services.
                            </p>
                            <p class="mb-0">
                                Our total liability shall not exceed the amount you paid us in the 12 months 
                                preceding the incident (if any).
                            </p>
                        </div>

                        <h4>Specific Exclusions</h4>
                        <p>We are not liable for:</p>
                        <ul>
                            <li>Injury or harm occurring during activities</li>
                            <li>Disputes between users and activity providers</li>
                            <li>Loss of data or service interruptions</li>
                            <li>Third-party content or conduct</li>
                            <li>Unauthorized access to your account</li>
                        </ul>
                    </section>

                    <!-- Section 8: Termination -->
                    <section id="termination">
                        <h2 class="section-header">8. Termination</h2>
                        
                        <h4>Your Right to Terminate</h4>
                        <p>You may terminate your account at any time by:</p>
                        <ul>
                            <li>Using the account deletion feature in your profile</li>
                            <li>Contacting us at support@kidssmart.com</li>
                            <li>Simply stopping use of the service</li>
                        </ul>

                        <h4>Our Right to Terminate</h4>
                        <p>We may terminate or suspend your account if:</p>
                        <ul>
                            <li>You violate these Terms</li>
                            <li>We suspect fraudulent or illegal activity</li>
                            <li>You provide false information</li>
                            <li>We decide to discontinue the service</li>
                        </ul>

                        <h4>Effect of Termination</h4>
                        <ul>
                            <li>Your access to the service will be discontinued</li>
                            <li>Your data may be deleted (see our Privacy Policy)</li>
                            <li>Certain provisions of these Terms will survive termination</li>
                        </ul>
                    </section>

                    <!-- Section 9: Governing Law -->
                    <section id="governing-law">
                        <h2 class="section-header">9. Governing Law</h2>
                        
                        <p>
                            These Terms are governed by the laws of Victoria, Australia. Any disputes will be 
                            resolved in the courts of Victoria, Australia.
                        </p>

                        <h4>Dispute Resolution</h4>
                        <p>Before pursuing legal action, we encourage you to:</p>
                        <ul>
                            <li>Contact us directly to resolve the issue</li>
                            <li>Allow 30 days for us to respond and address concerns</li>
                            <li>Consider mediation if direct resolution fails</li>
                        </ul>

                        <h4>Class Action Waiver</h4>
                        <p>
                            You agree to resolve disputes individually and waive the right to participate 
                            in class action lawsuits or class-wide arbitration.
                        </p>
                    </section>

                    <!-- Section 10: Contact -->
                    <section id="contact-information">
                        <h2 class="section-header">10. Contact Information</h2>
                        
                        <div class="highlight-box">
                            <h5><i class="fas fa-envelope text-primary me-2"></i>Get in Touch</h5>
                            <p>Questions about these Terms? Contact us:</p>
                            <ul class="list-unstyled">
                                <li><strong>Email:</strong> legal@kidssmart.com</li>
                                <li><strong>Mail:</strong> Legal Department, KidsSmart Pty Ltd, PO Box 12345, Melbourne VIC 3000</li>
                                <li><strong>Phone:</strong> +61 3 1234 5678</li>
                            </ul>
                            <p class="mb-0">
                                <small class="text-muted">
                                    Business hours: Monday-Friday 9AM-5PM AEST
                                </small>
                            </p>
                        </div>
                    </section>

                    <!-- Additional Information -->
                    <section class="mt-5 p-4 bg-light rounded">
                        <h4><i class="fas fa-info-circle text-primary me-2"></i>Additional Legal Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><a href="privacy-policy.php" class="text-decoration-none"><i class="fas fa-user-shield me-2"></i>Privacy Policy</a></li>
                                    <li><a href="cookie-policy.php" class="text-decoration-none"><i class="fas fa-cookie-bite me-2"></i>Cookie Policy</a></li>
                                    <li><a href="sitemap.php" class="text-decoration-none"><i class="fas fa-sitemap me-2"></i>Site Map</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><a href="faq.php" class="text-decoration-none"><i class="fas fa-question-circle me-2"></i>FAQ</a></li>
                                    <li><a href="contact.php" class="text-decoration-none"><i class="fas fa-envelope me-2"></i>Contact Support</a></li>
                                    <li><a href="help.php" class="text-decoration-none"><i class="fas fa-life-ring me-2"></i>Help Center</a></li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </article>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="position-sticky" style="top: 20px;">
                    <!-- Legal Summary -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>Key Points Summary</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Free service to find activities</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Must be 18+ to create account</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>We don't provide activities directly</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Verify providers independently</li>
                                <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Account can be deleted anytime</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Account Actions</h5>
                        </div>
                        <div class="card-body">
                            <?php if (is_logged_in()): ?>
                                <a href="profile.php" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-user me-2"></i>View Profile
                                </a>
                                <a href="delete-account.php" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="fas fa-trash me-2"></i>Delete Account
                                </a>
                            <?php else: ?>
                                <a href="signup.php" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </a>
                                <a href="login.php" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Need Help -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Need Help?</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Questions about our terms or policies?</p>
                            <a href="contact.php" class="btn btn-success btn-sm w-100 mb-2">
                                <i class="fas fa-envelope me-2"></i>Contact Support
                            </a>
                            <a href="faq.php" class="btn btn-outline-success btn-sm w-100">
                                <i class="fas fa-question me-2"></i>View FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Smooth scrolling for anchor links -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tocLinks = document.querySelectorAll('.toc a[href^="#"]');
            tocLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    if (targetSection) {
                        targetSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
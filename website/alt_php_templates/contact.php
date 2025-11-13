<?php
require_once __DIR__ . '/config/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="text-center mb-4">Contact KidsSmart</h1>
                <p class="lead text-center">We'd love to hear from you! Get in touch with any questions, suggestions, or feedback.</p>
                
                <div class="row mt-5">
                    <div class="col-md-4 text-center mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                                <h5>Email</h5>
                                <p>info@kidssmart.com.au</p>
                                <a href="mailto:info@kidssmart.com.au" class="btn btn-outline-primary">Send Email</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                                <h5>Phone</h5>
                                <p>1300 KIDS SMART<br>(1300 543 776)</p>
                                <a href="tel:1300543776" class="btn btn-outline-primary">Call Us</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                                <h5>Business Hours</h5>
                                <p>Monday - Friday<br>9:00 AM - 5:00 PM AEST</p>
                                <small class="text-muted">Closed weekends & public holidays</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-5">
                    <div class="card-header">
                        <h4 class="mb-0">Send Us a Message</h4>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" class="form-control" id="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" id="subject">
                                    <option>General Inquiry</option>
                                    <option>Report an Issue</option>
                                    <option>Suggest an Activity Provider</option>
                                    <option>Partnership Opportunity</option>
                                    <option>Technical Support</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" rows="5" placeholder="Tell us how we can help you..." required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-5 mb-5">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#" class="btn btn-outline-primary me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="btn btn-outline-info me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-danger me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline-primary"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
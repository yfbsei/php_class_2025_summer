<?php
require_once '../config.php';

$page_title = 'About Us';
$page_description = 'Learn about BlogPlatform - our mission, values, and the community we\'re building.';

// Get some stats for the about page
$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'published'");
$total_posts = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM comments WHERE status = 'approved'");
$total_comments = $stmt->fetchColumn();

include '../includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-4">About BlogPlatform</h1>
            <p class="lead">
                Empowering voices, connecting minds, and fostering meaningful conversations through the power of shared stories.
            </p>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- Stats Section -->
    <div class="row mb-5">
        <div class="col-md-4 text-center mb-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_posts); ?></div>
                <div class="stat-label">Published Posts</div>
            </div>
        </div>
        <div class="col-md-4 text-center mb-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Community Members</div>
            </div>
        </div>
        <div class="col-md-4 text-center mb-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_comments); ?></div>
                <div class="stat-label">Comments & Discussions</div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body p-5">
                    <h2 class="h3 mb-4">Our Story</h2>
                    <p class="lead">
                        BlogPlatform was born from a simple belief: everyone has a story worth telling, and everyone deserves to be heard.
                    </p>
                    
                    <p>
                        In an age where information moves at lightning speed and attention spans grow shorter, we wanted to create a space where thoughtful, meaningful content could thrive. A place where writers could craft their narratives without distraction, and readers could discover perspectives that challenge, inspire, and enlighten.
                    </p>
                    
                    <h3 class="h4 mt-4 mb-3">What Makes Us Different</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Community-Driven</h5>
                                    <p class="text-muted mb-0">Our platform is shaped by our users. Every feature, every improvement comes from listening to our community.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-shield-alt fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Privacy-Focused</h5>
                                    <p class="text-muted mb-0">Your data is yours. We believe in transparent, ethical data practices and user privacy.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-rocket fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Innovation</h5>
                                    <p class="text-muted mb-0">We're constantly evolving, implementing new features and technologies to enhance your experience.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-heart fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Quality Content</h5>
                                    <p class="text-muted mb-0">We prioritize substance over noise, fostering an environment where quality content rises to the top.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="h4 mt-4 mb-3">Our Mission</h3>
                    <p>
                        To democratize publishing and create a platform where diverse voices can share their knowledge, experiences, and perspectives with the world. We believe that when people share their stories, we all become richer for it.
                    </p>
                    
                    <h3 class="h4 mt-4 mb-3">Join Our Community</h3>
                    <p>
                        Whether you're a seasoned writer or just starting your journey, BlogPlatform welcomes you. Share your insights, connect with like-minded individuals, and be part of a community that values authentic, meaningful content.
                    </p>
                    
                    <?php if (!isLoggedIn()): ?>
                        <div class="text-center mt-4">
                            <a href="register.php" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-user-plus me-2"></i>Join Us Today
                            </a>
                            <a href="index.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Explore Posts
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Section -->
    <div class="row mt-5">
        <div class="col-lg-8 mx-auto">
            <div class="card bg-light">
                <div class="card-body text-center p-5">
                    <h3 class="h4 mb-3">Get In Touch</h3>
                    <p class="text-muted mb-4">
                        Have questions, suggestions, or just want to say hello? We'd love to hear from you.
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                            <div>contact@blogplatform.com</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                            <div>+1 (555) 123-4567</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                            <div>San Francisco, CA</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
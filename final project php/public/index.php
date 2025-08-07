<?php
require_once '../config.php';

$page_title = 'Home';
$page_description = 'Discover amazing content from our community of writers and thought leaders.';

// Get published posts with author info
$stmt = $pdo->prepare("
    SELECT p.*, u.username as author_name 
    FROM posts p 
    JOIN users u ON p.author_id = u.id 
    WHERE p.status = 'published' 
    ORDER BY p.created_at DESC 
    LIMIT 12
");
$stmt->execute();
$posts = $stmt->fetchAll();

// Get recent comments for sidebar
$stmt = $pdo->prepare("
    SELECT c.*, p.title as post_title, p.slug as post_slug, u.username as author_name 
    FROM comments c 
    JOIN posts p ON c.post_id = p.id 
    JOIN users u ON c.author_id = u.id 
    WHERE c.status = 'approved' AND p.status = 'published'
    ORDER BY c.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_comments = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-4">Welcome to BlogPlatform</h1>
            <p class="lead mb-4">
                Discover amazing stories, insights, and ideas from our community of passionate writers and thought leaders.
            </p>
            <?php if (!isLoggedIn()): ?>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="register.php" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Join Our Community
                    </a>
                    <a href="about.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-info-circle me-2"></i>Learn More
                    </a>
                </div>
            <?php else: ?>
                <a href="../admin/dashboard.php" class="btn btn-light btn-lg px-4">
                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0">Latest Posts</h2>
                <div class="text-muted">
                    <i class="fas fa-rss me-1"></i><?php echo count($posts); ?> posts
                </div>
            </div>
            
            <?php if ($posts): ?>
                <div class="row">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6 mb-4">
                            <article class="card post-card h-100">
                                <?php if ($post['featured_image']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                         class="card-img-top" style="height: 200px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>">
                                <?php else: ?>
                                    <div class="post-image">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="post-meta">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($post['author_name']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-calendar me-1"></i><?php echo timeAgo($post['created_at']); ?>
                                    </div>
                                    
                                    <h5 class="post-title">
                                        <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <p class="post-excerpt flex-grow-1">
                                        <?php echo htmlspecialchars($post['excerpt'] ?: truncate(strip_tags($post['content']))); ?>
                                    </p>
                                    
                                    <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                       class="btn btn-outline-primary btn-sm mt-auto">
                                        <i class="fas fa-arrow-right me-1"></i>Read More
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Posts Yet</h3>
                    <p class="text-muted">Be the first to share your thoughts with the community!</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="../admin/add_post.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Write Your First Post
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <aside class="sidebar">
                <h5><i class="fas fa-comments me-2"></i>Recent Comments</h5>
                <?php if ($recent_comments): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_comments as $comment): ?>
                            <div class="list-group-item border-0 px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-primary">
                                            <?php echo htmlspecialchars($comment['author_name']); ?>
                                        </h6>
                                        <p class="mb-1 small">
                                            <?php echo htmlspecialchars(truncate($comment['content'], 80)); ?>
                                        </p>
                                        <small class="text-muted">
                                            on <a href="post.php?slug=<?php echo urlencode($comment['post_slug']); ?>" 
                                                  class="text-decoration-none">
                                                <?php echo htmlspecialchars(truncate($comment['post_title'], 30)); ?>
                                            </a>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($comment['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No comments yet.</p>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <h5><i class="fas fa-info-circle me-2"></i>About BlogPlatform</h5>
                <p class="text-muted">
                    A modern platform for sharing ideas, connecting with readers, and building meaningful conversations around the topics that matter most.
                </p>
                <a href="about.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i>Learn More
                </a>
            </aside>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
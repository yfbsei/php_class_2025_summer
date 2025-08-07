<?php
require_once '../config.php';

$slug = $_GET['slug'] ?? '';
$error = '';
$success = '';

if (empty($slug)) {
    header('Location: index.php');
    exit();
}

// Get post with author info
$stmt = $pdo->prepare("
    SELECT p.*, u.username as author_name, u.profile_image as author_image
    FROM posts p 
    JOIN users u ON p.author_id = u.id 
    WHERE p.slug = ? AND p.status = 'published'
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit();
}

// Get comments for this post
$stmt = $pdo->prepare("
    SELECT c.*, u.username as author_name, u.profile_image as author_image
    FROM comments c 
    JOIN users u ON c.author_id = u.id 
    WHERE c.post_id = ? AND c.status = 'approved'
    ORDER BY c.created_at ASC
");
$stmt->execute([$post['id']]);
$comments = $stmt->fetchAll();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn()) {
    $comment_content = trim($_POST['content'] ?? '');
    
    if (empty($comment_content)) {
        $error = 'Please enter a comment.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, author_id, content) VALUES (?, ?, ?)");
        if ($stmt->execute([$post['id'], $_SESSION['user_id'], $comment_content])) {
            $_SESSION['success_message'] = 'Your comment has been posted!';
            header('Location: post.php?slug=' . urlencode($slug));
            exit();
        } else {
            $error = 'Failed to post comment. Please try again.';
        }
    }
}

$page_title = $post['title'];
$page_description = $post['excerpt'] ?: truncate(strip_tags($post['content']));

include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <article class="card">
                <?php if ($post['featured_image']): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                         class="card-img-top" style="height: 400px; object-fit: cover;" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>">
                <?php endif; ?>
                
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                        
                        <div class="d-flex align-items-center justify-content-center text-muted mb-4">
                            <?php if ($post['author_image']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($post['author_image']); ?>" 
                                     class="rounded-circle me-3" width="40" height="40" alt="Author">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($post['author_name']); ?></div>
                                <div class="small">
                                    <i class="fas fa-calendar me-1"></i><?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-clock me-1"></i><?php echo timeAgo($post['created_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>
                    
                    <hr class="my-5">
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Posts
                        </a>
                        
                        <div class="text-muted">
                            <i class="fas fa-comments me-1"></i><?php echo count($comments); ?> 
                            <?php echo count($comments) == 1 ? 'comment' : 'comments'; ?>
                        </div>
                    </div>
                </div>
            </article>
            
            <!-- Comments Section -->
            <div class="comments-section">
                <h3 class="h4 mb-4">
                    <i class="fas fa-comments me-2"></i>Comments (<?php echo count($comments); ?>)
                </h3>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Comment Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Leave a Comment</h5>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" 
                                              placeholder="Share your thoughts..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Post Comment
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <a href="login.php" class="alert-link">Login</a> or 
                        <a href="register.php" class="alert-link">register</a> to join the conversation.
                    </div>
                <?php endif; ?>
                
                <!-- Comments List -->
                <?php if ($comments): ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <div class="d-flex align-items-center">
                                        <?php if ($comment['author_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($comment['author_image']); ?>" 
                                                 class="rounded-circle me-2" width="32" height="32" alt="Commenter">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 32px; height: 32px; font-size: 14px;">
                                                <?php echo strtoupper(substr($comment['author_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></span>
                                    </div>
                                    <span class="comment-date"><?php echo timeAgo($comment['created_at']); ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <h5>No comments yet</h5>
                        <p>Be the first to share your thoughts!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
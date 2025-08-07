<?php
require_once '../config.php';
requireLogin();

// Handle delete request
if (isset($_GET['delete']) && isAdmin()) {
    $post_id = (int)$_GET['delete'];
    
    // Get post info for file cleanup
    $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if ($post) {
        // Delete featured image if exists
        if ($post['featured_image'] && file_exists('../uploads/' . $post['featured_image'])) {
            unlink('../uploads/' . $post['featured_image']);
        }
        
        // Delete post (comments will be deleted by CASCADE)
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        
        $_SESSION['success_message'] = 'Post deleted successfully!';
        header('Location: posts.php');
        exit();
    }
}

// Get posts based on user role
if (isAdmin()) {
    // Admin sees all posts
    $stmt = $pdo->prepare("
        SELECT p.*, u.username as author_name, 
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        JOIN users u ON p.author_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
} else {
    // Regular users see only their posts
    $stmt = $pdo->prepare("
        SELECT p.*, u.username as author_name,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        JOIN users u ON p.author_id = u.id 
        WHERE p.author_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

$posts = $stmt->fetchAll();

$page_title = 'Manage Posts';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?php echo isAdmin() ? 'All Posts' : 'My Posts'; ?>
                </h1>
                <a href="add_post.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Post
                </a>
            </div>
        </div>
    </div>

    <?php if ($posts): ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <?php if (isAdmin()): ?>
                                    <th>Author</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <?php if ($post['featured_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                 alt="Featured Image" 
                                                 style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 40px; border-radius: 4px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                            <?php if ($post['excerpt']): ?>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars(truncate($post['excerpt'], 80)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php if (isAdmin()): ?>
                                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if ($post['status'] == 'published'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-eye me-1"></i>Published
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-file-alt me-1"></i>Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-comments me-1"></i><?php echo $post['comment_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                            <div class="text-muted"><?php echo timeAgo($post['created_at']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($post['status'] == 'published'): ?>
                                                <a href="../public/post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank" title="View Post">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (isAdmin() || $post['author_id'] == $_SESSION['user_id']): ?>
                                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" title="Edit Post">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if (isAdmin()): ?>
                                                    <a href="posts.php?delete=<?php echo $post['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger" title="Delete Post"
                                                       onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-edit fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No Posts Found</h3>
                <p class="text-muted mb-4">
                    <?php echo isAdmin() ? 'No posts have been created yet.' : "You haven't written any posts yet."; ?>
                </p>
                <a href="add_post.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Create Your First Post
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
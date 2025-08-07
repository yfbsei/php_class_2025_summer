<?php
require_once '../config.php';
requireLogin();

// Get user statistics
$user_id = $_SESSION['user_id'];

$stats = [
    'posts' => $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?"),
    'published_posts' => $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'published'"),
    'draft_posts' => $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'draft'"),
    'comments_received' => $pdo->prepare("SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.author_id = ?"),
];

foreach ($stats as $key => $stmt) {
    $stmt->execute([$user_id]);
    $stats[$key] = $stmt->fetchColumn();
}

// Admin-only stats
if (isAdmin()) {
    $admin_stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_posts' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
        'total_comments' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
        'pending_comments' => $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    ];
}

// Get recent posts
$stmt = $pdo->prepare("SELECT * FROM posts WHERE author_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_posts = $stmt->fetchAll();

// Get recent comments on user's posts
$stmt = $pdo->prepare("
    SELECT c.*, p.title as post_title, u.username as commenter_name 
    FROM comments c 
    JOIN posts p ON c.post_id = p.id 
    JOIN users u ON c.author_id = u.id 
    WHERE p.author_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_comments = $stmt->fetchAll();

$page_title = 'Dashboard';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</small>
            </h1>
        </div>
    </div>

    <!-- User Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-edit fa-2x text-primary mb-2"></i>
                    <div class="h2 text-primary"><?php echo $stats['posts']; ?></div>
                    <div class="text-muted">Total Posts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-eye fa-2x text-success mb-2"></i>
                    <div class="h2 text-success"><?php echo $stats['published_posts']; ?></div>
                    <div class="text-muted">Published</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-2x text-warning mb-2"></i>
                    <div class="h2 text-warning"><?php echo $stats['draft_posts']; ?></div>
                    <div class="text-muted">Drafts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-comments fa-2x text-info mb-2"></i>
                    <div class="h2 text-info"><?php echo $stats['comments_received']; ?></div>
                    <div class="text-muted">Comments</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isAdmin()): ?>
    <!-- Admin Stats -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3"><i class="fas fa-crown me-2 text-warning"></i>Admin Overview</h4>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <div class="h2"><?php echo $admin_stats['total_users']; ?></div>
                    <div>Total Users</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-newspaper fa-2x mb-2"></i>
                    <div class="h2"><?php echo $admin_stats['total_posts']; ?></div>
                    <div>All Posts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-comments fa-2x mb-2"></i>
                    <div class="h2"><?php echo $admin_stats['total_comments']; ?></div>
                    <div>All Comments</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <div class="h2"><?php echo $admin_stats['pending_comments']; ?></div>
                    <div>Pending</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Recent Posts -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Recent Posts</h5>
                    <a href="add_post.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>New Post
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($recent_posts): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_posts as $post): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                <?php if ($post['featured_image']): ?>
                                                    <i class="fas fa-image text-success ms-1" title="Has featured image"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($post['status'] == 'published'): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo timeAgo($post['created_at']); ?></td>
                                            <td>
                                                <a href="../public/post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No posts yet</h5>
                            <p class="text-muted mb-3">Start sharing your thoughts with the world!</p>
                            <a href="add_post.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Your First Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Comments -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="add_post.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>New Post
                        </a>
                        <a href="posts.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Manage Posts
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                        <?php endif; ?>
                        <a href="profile.php" class="btn btn-outline-info">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Recent Comments</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_comments): ?>
                        <?php foreach ($recent_comments as $comment): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <div class="small text-muted">
                                    <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong> 
                                    commented on <em><?php echo htmlspecialchars(truncate($comment['post_title'], 30)); ?></em>
                                </div>
                                <div class="small mt-1">
                                    <?php echo htmlspecialchars(truncate($comment['content'], 60)); ?>
                                </div>
                                <div class="small text-muted"><?php echo timeAgo($comment['created_at']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No comments on your posts yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
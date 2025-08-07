<?php
require_once '../config.php';
requireLogin();
requireAdmin();

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'You cannot delete your own account.';
    } else {
        // Get user info for cleanup
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Delete profile image if exists
            if ($user['profile_image'] && file_exists('../uploads/' . $user['profile_image'])) {
                unlink('../uploads/' . $user['profile_image']);
            }
            
            // Delete user (posts and comments will be deleted by CASCADE)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $_SESSION['success_message'] = 'User deleted successfully!';
        }
    }
    
    header('Location: users.php');
    exit();
}

// Handle role update
if (isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    
    if ($user_id != $_SESSION['user_id'] && in_array($new_role, ['user', 'admin'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        $_SESSION['success_message'] = 'User role updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Cannot update role for this user.';
    }
    
    header('Location: users.php');
    exit();
}

// Get all users with stats
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as post_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM users u
    LEFT JOIN posts p ON u.id = p.author_id
    LEFT JOIN comments c ON u.id = c.author_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

$page_title = 'Manage Users';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-users me-2"></i>Manage Users
                </h1>
                <div class="text-muted">
                    <i class="fas fa-info-circle me-1"></i><?php echo count($users); ?> total users
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Posts</th>
                            <th>Comments</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($user['profile_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                 alt="Profile" class="rounded-circle me-3" width="40" height="40">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-info">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <i class="fas fa-edit me-1"></i><?php echo $user['post_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <i class="fas fa-comments me-1"></i><?php echo $user['comment_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        <div class="text-muted"><?php echo timeAgo($user['created_at']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Delete User"
                                           onclick="return confirm('Are you sure you want to delete this user? This will also delete all their posts and comments. This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Current user</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <div class="h3"><?php echo count($users); ?></div>
                    <div class="text-muted">Total Users</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-crown fa-2x text-warning mb-2"></i>
                    <div class="h3">
                        <?php echo count(array_filter($users, function($u) { return $u['role'] == 'admin'; })); ?>
                    </div>
                    <div class="text-muted">Administrators</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-edit fa-2x text-success mb-2"></i>
                    <div class="h3">
                        <?php echo count(array_filter($users, function($u) { return $u['post_count'] > 0; })); ?>
                    </div>
                    <div class="text-muted">Active Writers</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-user-plus fa-2x text-info mb-2"></i>
                    <div class="h3">
                        <?php echo count(array_filter($users, function($u) { 
                            return strtotime($u['created_at']) > strtotime('-30 days'); 
                        })); ?>
                    </div>
                    <div class="text-muted">New This Month</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
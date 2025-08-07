<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            $hashed_password = $user['password']; // Keep current password by default
            
            // Check if password update is requested
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = 'Current password is required to set a new password.';
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($error)) {
                $profile_image = $user['profile_image']; // Keep existing image by default
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                        $error = 'Please upload a valid image file (JPEG, PNG, or GIF).';
                    } elseif ($_FILES['profile_image']['size'] > $max_size) {
                        $error = 'Image file is too large. Maximum size is 5MB.';
                    } else {
                        // Delete old image if exists
                        if ($user['profile_image'] && file_exists('../uploads/' . $user['profile_image'])) {
                            unlink('../uploads/' . $user['profile_image']);
                        }
                        
                        // Create uploads directory if it doesn't exist
                        if (!file_exists('../uploads')) {
                            mkdir('../uploads', 0777, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $profile_image = 'profile_' . uniqid() . '.' . $file_extension;
                        $upload_path = '../uploads/' . $profile_image;
                        
                        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                            $error = 'Failed to upload image. Please try again.';
                            $profile_image = $user['profile_image'];
                        }
                    }
                }
                
                // Handle image removal
                if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                    if ($user['profile_image'] && file_exists('../uploads/' . $user['profile_image'])) {
                        unlink('../uploads/' . $user['profile_image']);
                    }
                    $profile_image = null;
                }
                
                if (empty($error)) {
                    // Update user profile
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_image = ? WHERE id = ?");
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $profile_image, $_SESSION['user_id']])) {
                        $_SESSION['username'] = $username;
                        $_SESSION['success_message'] = 'Profile updated successfully!';
                        
                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user = $stmt->fetch();
                        
                        header('Location: profile.php');
                        exit();
                    } else {
                        $error = 'Failed to update profile. Please try again.';
                    }
                }
            }
        }
    }
}

$page_title = 'My Profile';
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-user-edit me-2"></i>My Profile
                    </h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="profile-section">
                                    <?php if ($user['profile_image']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                             alt="Profile Image" class="profile-avatar mb-3">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                            <label class="form-check-label" for="remove_image">
                                                Remove current photo
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <div class="profile-avatar bg-light d-flex align-items-center justify-content-center mb-3">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-camera me-1"></i>
                                            <?php echo $user['profile_image'] ? 'Change Photo' : 'Upload Photo'; ?>
                                        </label>
                                        <input type="file" id="profile_image" name="profile_image" 
                                               class="d-none" accept="image/*" onchange="previewImage(this)">
                                        <div class="form-text mt-2">Max 5MB. JPEG, PNG, or GIF</div>
                                    </div>
                                    
                                    <div id="imagePreview" style="display: none;">
                                        <img id="preview" src="" class="profile-avatar">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-1"></i>Username *
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email Address *
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        <span class="text-muted">Account Information</span>
                                    </div>
                                    <div class="bg-light p-3 rounded">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <small class="text-muted d-block">Role:</small>
                                                <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted d-block">Member since:</small>
                                                <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5 class="mb-3">
                                    <i class="fas fa-lock me-2"></i>Change Password (Optional)
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                    <div class="form-text">Required only if changing password</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" minlength="6">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between pt-4 border-top">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- User Statistics -->
            <?php
            // Get user stats
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $post_count = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'published'");
            $stmt->execute([$_SESSION['user_id']]);
            $published_count = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE author_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $comment_count = $stmt->fetchColumn();
            ?>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Your Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <div class="border-end">
                                <div class="h3 text-primary"><?php echo $post_count; ?></div>
                                <div class="text-muted">Total Posts</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-end">
                                <div class="h3 text-success"><?php echo $published_count; ?></div>
                                <div class="text-muted">Published</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="h3 text-info"><?php echo $comment_count; ?></div>
                            <div class="text-muted">Comments Made</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});

// Clear password fields validation on new password change
document.getElementById('new_password').addEventListener('input', function() {
    const confirmField = document.getElementById('confirm_password');
    if (confirmField.value) {
        confirmField.dispatchEvent(new Event('input'));
    }
});
</script>

<?php include '../includes/footer.php'; ?>
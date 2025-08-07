<?php
require_once '../config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../admin/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Handle profile image upload
            $profile_image = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                    $error = 'Please upload a valid image file (JPEG, PNG, or GIF).';
                } elseif ($_FILES['profile_image']['size'] > $max_size) {
                    $error = 'Image file is too large. Maximum size is 5MB.';
                } else {
                    // Create uploads directory if it doesn't exist
                    if (!file_exists('../uploads')) {
                        mkdir('../uploads', 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $profile_image = 'profile_' . uniqid() . '.' . $file_extension;
                    $upload_path = '../uploads/' . $profile_image;
                    
                    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $error = 'Failed to upload image. Please try again.';
                        $profile_image = null;
                    }
                }
            }
            
            if (empty($error)) {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_image) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password, $profile_image])) {
                    $_SESSION['success_message'] = 'Registration successful! You can now login.';
                    header('Location: login.php');
                    exit();
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$page_title = 'Register';
$page_description = 'Join our community of writers and readers. Create your account to start sharing your ideas.';

include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-user-plus me-2"></i>Join Our Community
                    </h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <div class="row">
                            <div class="col-md-12 text-center mb-4">
                                <div class="profile-upload-container">
                                    <div class="profile-preview mb-3">
                                        <img id="profilePreview" src="https://via.placeholder.com/120x120/667eea/ffffff?text=Photo" 
                                             class="profile-avatar" style="width: 120px; height: 120px;" alt="Profile Preview">
                                    </div>
                                    <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-camera me-1"></i>Upload Photo (Optional)
                                    </label>
                                    <input type="file" id="profile_image" name="profile_image" 
                                           class="d-none" accept="image/*" onchange="previewImage(this)">
                                    <div class="form-text">Max 5MB. JPEG, PNG, or GIF</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>Username *
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   placeholder="Choose a unique username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email Address *
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Password *
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="At least 6 characters" minlength="6" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Confirm Password *
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php" class="text-decoration-none">Login here</a>
                        </p>
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
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
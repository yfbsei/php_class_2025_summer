<?php
require_once '../config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../admin/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            $_SESSION['success_message'] = 'Welcome back, ' . $user['username'] . '!';
            header('Location: ../admin/dashboard.php');
            exit();
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

$page_title = 'Login';
$page_description = 'Login to your account to access your dashboard and start writing.';

include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header text-center">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>Welcome Back
                    </h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>Username or Email
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   placeholder="Enter your username or email" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">Don't have an account? 
                            <a href="register.php" class="text-decoration-none">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Demo Credentials -->
            <div class="card mt-3 bg-light">
                <div class="card-body text-center p-3">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-info-circle me-1"></i>Demo Credentials
                    </h6>
                    <div class="row">
                        <div class="col-6">
                            <small class="d-block"><strong>Admin:</strong></small>
                            <small>admin / admin123</small>
                        </div>
                        <div class="col-6">
                            <small class="d-block"><strong>User:</strong></small>
                            <small>john_writer / admin123</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
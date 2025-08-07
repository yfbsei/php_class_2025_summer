<?php
require_once '../config.php';
include '../includes/header.php';

// Fetch some public products to display
$stmt = $pdo->query("SELECT p.*, u.username FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 6");
$products = $stmt->fetchAll();
?>

<div class="hero-section">
    <div class="container">
        <h1 class="display-4 mb-4">Welcome to CRUD Application</h1>
        <p class="lead">A complete solution for content management with user authentication and file uploads</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-light btn-lg me-3">Get Started</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        <?php else: ?>
            <a href="../admin/dashboard.php" class="btn btn-light btn-lg">Go to Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-4">
        <div class="feature-box">
            <h3>Create & Manage</h3>
            <p>Create, read, update, and delete your content with ease. Full CRUD functionality at your fingertips.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-box">
            <h3>Secure Access</h3>
            <p>Admin-level pages are protected with user authentication. Your content stays safe and secure.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-box">
            <h3>File Uploads</h3>
            <p>Upload images and files to enhance your content. Visual elements make your data more engaging.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h2 class="text-center mb-4">Recent Products</h2>
    </div>
    <?php if ($products): ?>
        <?php foreach ($products as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($product['image']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Product Image">
                    <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <span class="text-muted">No Image</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <p class="card-text">
                            <small class="text-muted">By <?php echo htmlspecialchars($product['username']); ?></small>
                        </p>
                        <h6 class="text-primary">$<?php echo number_format($product['price'], 2); ?></h6>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <p class="text-center text-muted">No products available yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
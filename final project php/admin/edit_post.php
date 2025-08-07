<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// Get post ID
$post_id = (int)($_GET['id'] ?? 0);

// Fetch post details (check ownership unless admin)
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND author_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
}

$post = $stmt->fetch();

if (!$post) {
    $_SESSION['error_message'] = 'Post not found or access denied.';
    header('Location: posts.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } else {
        // Generate slug if title changed
        $slug = $post['slug'];
        if ($title !== $post['title']) {
            $base_slug = createSlug($title);
            $new_slug = $base_slug;
            $counter = 1;
            
            // Check if new slug exists (excluding current post)
            do {
                $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
                $stmt->execute([$new_slug, $post_id]);
                if ($stmt->fetch()) {
                    $new_slug = $base_slug . '-' . $counter;
                    $counter++;
                } else {
                    $slug = $new_slug;
                    break;
                }
            } while (true);
        }
        
        $featured_image = $post['featured_image'];
        
        // Handle new featured image upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['featured_image']['type'], $allowed_types)) {
                $error = 'Please upload a valid image file (JPEG, PNG, or GIF).';
            } elseif ($_FILES['featured_image']['size'] > $max_size) {
                $error = 'Image file is too large. Maximum size is 5MB.';
            } else {
                // Delete old image if exists
                if ($post['featured_image'] && file_exists('../uploads/' . $post['featured_image'])) {
                    unlink('../uploads/' . $post['featured_image']);
                }
                
                // Create uploads directory if it doesn't exist
                if (!file_exists('../uploads')) {
                    mkdir('../uploads', 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                $featured_image = 'post_' . uniqid() . '.' . $file_extension;
                $upload_path = '../uploads/' . $featured_image;
                
                if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                    $error = 'Failed to upload image. Please try again.';
                    $featured_image = $post['featured_image'];
                }
            }
        }
        
        // Handle image removal
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            if ($post['featured_image'] && file_exists('../uploads/' . $post['featured_image'])) {
                unlink('../uploads/' . $post['featured_image']);
            }
            $featured_image = null;
        }
        
        if (empty($error)) {
            // Auto-generate excerpt if not provided
            if (empty($excerpt)) {
                $excerpt = truncate(strip_tags($content), 200);
            }
            
            // Update post in database
            $stmt = $pdo->prepare("
                UPDATE posts 
                SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, status = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $status, $post_id])) {
                $_SESSION['success_message'] = 'Post updated successfully!';
                header('Location: posts.php');
                exit();
            } else {
                $error = 'Failed to update post. Please try again.';
            }
        }
    }
} else {
    // Pre-populate form with existing data
    $_POST['title'] = $post['title'];
    $_POST['content'] = $post['content'];
    $_POST['excerpt'] = $post['excerpt'];
    $_POST['status'] = $post['status'];
}

$page_title = 'Edit Post';
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Post
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
                            <div class="col-lg-8">
                                <div class="mb-4">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading me-1"></i>Title *
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                    <div class="form-text">Current URL: ../public/post.php?slug=<?php echo htmlspecialchars($post['slug']); ?></div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="excerpt" class="form-label">
                                        <i class="fas fa-quote-left me-1"></i>Excerpt (Optional)
                                    </label>
                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                                    <div class="form-text">This appears on the homepage and in previews.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="content" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Content *
                                    </label>
                                    <textarea class="form-control" id="content" name="content" rows="15" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-cog me-2"></i>Post Settings
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">
                                                <i class="fas fa-eye me-1"></i>Status
                                            </label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="draft" <?php echo ($_POST['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>
                                                    Draft
                                                </option>
                                                <option value="published" <?php echo ($_POST['status'] ?? '') == 'published' ? 'selected' : ''; ?>>
                                                    Published
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="featured_image" class="form-label">
                                                <i class="fas fa-image me-1"></i>Featured Image
                                            </label>
                                            
                                            <?php if ($post['featured_image']): ?>
                                                <div class="mb-3">
                                                    <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                         alt="Current Featured Image" class="img-thumbnail mb-2" 
                                                         style="max-width: 100%; height: auto;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                                        <label class="form-check-label" for="remove_image">
                                                            Remove current image
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <input type="file" class="form-control" id="featured_image" name="featured_image" 
                                                   accept="image/*" onchange="previewImage(this)">
                                            <div class="form-text">Max 5MB. JPEG, PNG, or GIF</div>
                                            
                                            <div id="imagePreview" class="mt-3" style="display: none;">
                                                <img id="preview" src="" class="img-thumbnail" style="max-width: 100%; height: auto;">
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Created: <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                                                <?php if ($post['updated_at'] != $post['created_at']): ?>
                                                    <br><i class="fas fa-edit me-1"></i>
                                                    Updated: <?php echo date('M j, Y \a\t g:i A', strtotime($post['updated_at'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Post
                                            </button>
                                            <?php if ($post['status'] == 'published'): ?>
                                                <a href="../public/post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                                   class="btn btn-outline-info" target="_blank">
                                                    <i class="fas fa-eye me-2"></i>View Post
                                                </a>
                                            <?php endif; ?>
                                            <a href="posts.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Posts
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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

// Auto-resize content textarea
document.getElementById('content').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 600) + 'px';
});

// Initial resize
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('content');
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 600) + 'px';
});
</script>

<?php include '../includes/footer.php'; ?>
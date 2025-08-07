<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } else {
        // Generate unique slug
        $base_slug = createSlug($title);
        $slug = $base_slug;
        $counter = 1;
        
        // Check if slug exists and make it unique
        do {
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug = $base_slug . '-' . $counter;
                $counter++;
            } else {
                break;
            }
        } while (true);
        
        $featured_image = null;
        
        // Handle featured image upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['featured_image']['type'], $allowed_types)) {
                $error = 'Please upload a valid image file (JPEG, PNG, or GIF).';
            } elseif ($_FILES['featured_image']['size'] > $max_size) {
                $error = 'Image file is too large. Maximum size is 5MB.';
            } else {
                // Create uploads directory if it doesn't exist
                if (!file_exists('../uploads')) {
                    mkdir('../uploads', 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                $featured_image = 'post_' . uniqid() . '.' . $file_extension;
                $upload_path = '../uploads/' . $featured_image;
                
                if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                    $error = 'Failed to upload image. Please try again.';
                    $featured_image = null;
                }
            }
        }
        
        if (empty($error)) {
            // Auto-generate excerpt if not provided
            if (empty($excerpt)) {
                $excerpt = truncate(strip_tags($content), 200);
            }
            
            // Insert post into database
            $stmt = $pdo->prepare("
                INSERT INTO posts (title, slug, content, excerpt, featured_image, author_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $_SESSION['user_id'], $status])) {
                $_SESSION['success_message'] = 'Post ' . ($status == 'published' ? 'published' : 'saved as draft') . ' successfully!';
                header('Location: posts.php');
                exit();
            } else {
                $error = 'Failed to create post. Please try again.';
            }
        }
    }
}

$page_title = 'Add New Post';
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-plus me-2"></i>Create New Post
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
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                           placeholder="Enter an engaging title for your post" required>
                                    <div class="form-text">This will be used to generate the URL slug automatically.</div>
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

// Update button text based on status selection
document.getElementById('status').addEventListener('change', function() {
    const submitBtn = document.getElementById('submitText');
    if (this.value === 'published') {
        submitBtn.textContent = 'Publish Post';
    } else {
        submitBtn.textContent = 'Save Draft';
    }
});

// Character counter for excerpt
document.getElementById('excerpt').addEventListener('input', function() {
    const maxLength = 300;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;
    
    let helpText = this.nextElementSibling;
    if (currentLength > maxLength * 0.8) {
        helpText.textContent = `${remaining} characters remaining`;
        helpText.className = remaining < 0 ? 'form-text text-danger' : 'form-text text-warning';
    } else {
        helpText.textContent = 'This appears on the homepage and in previews.';
        helpText.className = 'form-text';
    }
});

// Auto-resize content textarea
document.getElementById('content').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 600) + 'px';
});
</script>

<?php include '../includes/footer.php'; ?>
                                
                                <div class="mb-4">
                                    <label for="excerpt" class="form-label">
                                        <i class="fas fa-quote-left me-1"></i>Excerpt (Optional)
                                    </label>
                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3" 
                                              placeholder="A brief summary of your post (will be auto-generated if left empty)"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                                    <div class="form-text">This appears on the homepage and in previews.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="content" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Content *
                                    </label>
                                    <textarea class="form-control" id="content" name="content" rows="15" 
                                              placeholder="Write your post content here..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                    <div class="form-text">Write your full post content. You can use line breaks for paragraphs.</div>
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
                                                <option value="draft" <?php echo ($_POST['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>
                                                    <i class="fas fa-file-alt"></i> Draft
                                                </option>
                                                <option value="published" <?php echo ($_POST['status'] ?? '') == 'published' ? 'selected' : ''; ?>>
                                                    <i class="fas fa-eye"></i> Published
                                                </option>
                                            </select>
                                            <div class="form-text">Drafts are only visible to you.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="featured_image" class="form-label">
                                                <i class="fas fa-image me-1"></i>Featured Image
                                            </label>
                                            <input type="file" class="form-control" id="featured_image" name="featured_image" 
                                                   accept="image/*" onchange="previewImage(this)">
                                            <div class="form-text">Max 5MB. JPEG, PNG, or GIF</div>
                                            
                                            <div id="imagePreview" class="mt-3" style="display: none;">
                                                <img id="preview" src="" class="img-thumbnail" style="max-width: 100%; height: auto;">
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>
                                                <span id="submitText">Save Draft</span>
                                            </button>
                                            <a href="posts.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
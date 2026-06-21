<?php
require_once __DIR__ . '/../config/db.php';
requireRole('seller');

$uid   = currentUser()['id'];
$cats  = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']        ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $price    = (float)($_POST['price']    ?? 0);
    $stock    = (int)($_POST['stock']      ?? 0);
    $category = trim($_POST['category']    ?? '');

    if (!$name || $price <= 0 || !$category) {
        $error = 'Name, price, and category are required.';
    } else {
        $imagePath = null;

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $file     = $_FILES['image'];
            $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
            $maxSize  = 5 * 1024 * 1024; // 5 MB

            if (!in_array($file['type'], $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WebP images allowed.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Image must be under 5 MB.';
            } else {
                $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename  = uniqid('prod_') . '.' . strtolower($ext);
                $destPath  = UPLOAD_PATH . $filename;

                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    $error = 'Failed to save image. Check uploads folder permissions.';
                } else {
                    $imagePath = $filename;
                }
            }
        }

        if (!$error) {
            $status = $stock > 0 ? 'available' : 'sold_out';
            $ins    = $pdo->prepare(
                'INSERT INTO products (seller_id, name, description, price, stock, category, image_path, status)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $ins->execute([$uid, $name, $desc, $price, $stock, $category, $imagePath, $status]);
            header('Location: ' . BASE_URL . '/seller/manage_products.php?added=1');
            exit;
        }
    }
}

$pageTitle = 'Add Product — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Add New Product</h1>
    </div>
  </div>

  <div class="page-body">
    <div class="container" style="max-width:680px;">
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-header"><h3>Product Details</h3></div>
        <div class="panel-body">
          <form method="POST" enctype="multipart/form-data" novalidate>

            <div class="form-group">
              <label>Product Name <span style="color:var(--rust)">*</span></label>
              <input type="text" name="name"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                     placeholder="e.g., Bear Amigurumi" required autofocus>
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea name="description" rows="4"
                        placeholder="Describe the item: materials, dimensions, care instructions…"><?=
                htmlspecialchars($_POST['description'] ?? '')
              ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Price (NPR) <span style="color:var(--rust)">*</span></label>
                <input type="number" name="price" min="1" step="0.01"
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                       placeholder="850" required>
              </div>
              <div class="form-group">
                <label>Stock Quantity <span style="color:var(--rust)">*</span></label>
                <input type="number" name="stock" min="0"
                       value="<?= htmlspecialchars($_POST['stock'] ?? '1') ?>"
                       placeholder="1" required>
                <div class="form-hint">Set to 0 to mark as Sold Out</div>
              </div>
            </div>

            <div class="form-group">
              <label>Category <span style="color:var(--rust)">*</span></label>
              <select name="category" required>
                <option value="">Select a category</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= htmlspecialchars($c['name']) ?>"
                    <?= ($_POST['category'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                    <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Image upload -->
            <div class="form-group">
              <label>Product Photo</label>
              <div class="img-upload-area">
                <input type="file" id="productImage" name="image"
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="upload-icon">📷</div>
                <p>Click to upload or drag an image here<br>
                   <small>JPG, PNG, GIF, WebP — max 5 MB</small></p>
                <img id="imagePreview" class="img-preview" src="" alt="" style="display:none;">
              </div>
            </div>

            <div style="margin-top:8px;display:flex;gap:12px;">
              <button type="submit" class="btn btn-dark">Save Product</button>
              <a href="<?= BASE_URL ?>/seller/manage_products.php" class="btn btn-outline">Cancel</a>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

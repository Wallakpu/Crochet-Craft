<?php
require_once __DIR__ . '/../config/db.php';
requireRole('seller');

$uid = currentUser()['id'];
$pid = (int)($_GET['id'] ?? 0);

// Fetch the product (must belong to this seller)
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND seller_id = ?');
$stmt->execute([$pid, $uid]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . BASE_URL . '/seller/manage_products.php');
    exit;
}

$cats  = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']        ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $price    = (float)($_POST['price']    ?? 0);
    $stock    = (int)($_POST['stock']      ?? 0);
    $category = trim($_POST['category']    ?? '');

    if (!$name || $price <= 0 || !$category) {
        $error = 'Name, price, and category are required.';
    } else {
        $imagePath = $product['image_path']; // keep existing by default

        // Handle new image upload
        if (!empty($_FILES['image']['name'])) {
            $file    = $_FILES['image'];
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!in_array($file['type'], $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WebP images allowed.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5 MB.';
            } else {
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('prod_') . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $filename)) {
                    // Delete old image
                    if ($imagePath && file_exists(UPLOAD_PATH . $imagePath)) {
                        unlink(UPLOAD_PATH . $imagePath);
                    }
                    $imagePath = $filename;
                } else {
                    $error = 'Failed to save image.';
                }
            }
        }

        if (!$error) {
            $status = $stock > 0 ? 'available' : 'sold_out';
            $upd = $pdo->prepare(
                'UPDATE products
                 SET name=?, description=?, price=?, stock=?, category=?, image_path=?, status=?
                 WHERE id=? AND seller_id=?'
            );
            $upd->execute([$name, $desc, $price, $stock, $category, $imagePath, $status, $pid, $uid]);
            header('Location: ' . BASE_URL . '/seller/manage_products.php?updated=1');
            exit;
        }
    }
}

$pageTitle = 'Edit Product — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Edit Product</h1>
    </div>
  </div>

  <div class="page-body">
    <div class="container" style="max-width:680px;">
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-header"><h3><?= htmlspecialchars($product['name']) ?></h3></div>
        <div class="panel-body">
          <form method="POST" enctype="multipart/form-data" novalidate>

            <div class="form-group">
              <label>Product Name <span style="color:var(--rust)">*</span></label>
              <input type="text" name="name"
                     value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>" required>
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea name="description" rows="4"><?=
                htmlspecialchars($_POST['description'] ?? $product['description'])
              ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Price (NPR) <span style="color:var(--rust)">*</span></label>
                <input type="number" name="price" min="1" step="0.01"
                       value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>" required>
              </div>
              <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock" min="0"
                       value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>">
                <div class="form-hint">0 = Sold Out</div>
              </div>
            </div>

            <div class="form-group">
              <label>Category <span style="color:var(--rust)">*</span></label>
              <select name="category" required>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= htmlspecialchars($c['name']) ?>"
                    <?= (($_POST['category'] ?? $product['category']) === $c['name']) ? 'selected' : '' ?>>
                    <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Current image / new upload -->
            <div class="form-group">
              <label>Product Photo</label>
              <?php if ($product['image_path']): ?>
                <div style="margin-bottom:12px;">
                  <img src="<?= UPLOAD_URL . htmlspecialchars($product['image_path']) ?>"
                       style="max-height:140px;border-radius:10px;">
                  <p style="font-size:12px;color:var(--brown-mid);margin-top:6px;">
                    Current image — upload below to replace it
                  </p>
                </div>
              <?php endif; ?>
              <div class="img-upload-area">
                <input type="file" id="productImage" name="image"
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="upload-icon">📷</div>
                <p>Click to upload a new image (optional)</p>
                <img id="imagePreview" class="img-preview" src="" alt="" style="display:none;">
              </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px;">
              <button type="submit" class="btn btn-dark">Update Product</button>
              <a href="<?= BASE_URL ?>/seller/manage_products.php" class="btn btn-outline">Cancel</a>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

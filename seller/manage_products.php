<?php
require_once __DIR__ . '/../config/db.php';
requireRole('seller');

$uid = currentUser()['id'];

// ── Delete action ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pid = (int)($_POST['product_id'] ?? 0);
    // Ensure this product belongs to this seller before deleting
    $chk = $pdo->prepare('SELECT image_path FROM products WHERE id = ? AND seller_id = ?');
    $chk->execute([$pid, $uid]);
    $row = $chk->fetch();

    if ($row) {
        // Remove image file
        if ($row['image_path'] && file_exists(UPLOAD_PATH . $row['image_path'])) {
            unlink(UPLOAD_PATH . $row['image_path']);
        }
        $pdo->prepare('DELETE FROM products WHERE id = ? AND seller_id = ?')->execute([$pid, $uid]);
    }
    header('Location: ' . BASE_URL . '/seller/manage_products.php?deleted=1');
    exit;
}

// ── Toggle stock status ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $pdo->prepare(
        "UPDATE products
         SET status = IF(status='available','sold_out','available')
         WHERE id = ? AND seller_id = ?"
    )->execute([$pid, $uid]);
    header('Location: ' . BASE_URL . '/seller/manage_products.php');
    exit;
}

// ── Fetch products ─────────────────────────────────────────
$products = $pdo->prepare('SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC');
$products->execute([$uid]);
$products = $products->fetchAll();

$pageTitle = 'My Products — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h1>My Products</h1>
        <p><?= count($products) ?> listing<?= count($products) !== 1 ? 's' : '' ?></p>
      </div>
      <a href="<?= BASE_URL ?>/seller/add_product.php" class="btn btn-dark">+ Add Product</a>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success" data-auto-dismiss>Product added successfully!</div>
      <?php endif; ?>
      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success" data-auto-dismiss>Product deleted.</div>
      <?php endif; ?>
      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success" data-auto-dismiss>Product updated!</div>
      <?php endif; ?>

      <?php if ($products): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr>
                  <!-- Thumbnail -->
                  <td>
                    <?php if ($p['image_path']): ?>
                      <img src="<?= UPLOAD_URL . htmlspecialchars($p['image_path']) ?>"
                           style="width:48px;height:48px;border-radius:8px;object-fit:cover;">
                    <?php else: ?>
                      <div style="width:48px;height:48px;border-radius:8px;background:var(--beige);
                                  display:flex;align-items:center;justify-content:center;font-size:20px;">🧶</div>
                    <?php endif; ?>
                  </td>

                  <td>
                    <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                    <span style="font-size:12px;color:var(--brown-mid);">
                      <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 50, '…')) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($p['category']) ?></td>
                  <td>NPR <?= number_format($p['price'], 0) ?></td>
                  <td><?= $p['stock'] ?></td>
                  <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>

                  <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                      <a href="<?= BASE_URL ?>/seller/edit_product.php?id=<?= $p['id'] ?>"
                         class="btn btn-outline btn-xs">Edit</a>

                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action"     value="toggle_status">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sage btn-xs" type="submit">
                          <?= $p['status'] === 'available' ? 'Mark Sold Out' : 'Mark Available' ?>
                        </button>
                      </form>

                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action"     value="delete">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button class="btn btn-danger btn-xs"
                                data-confirm="Delete '<?= htmlspecialchars($p['name']) ?>'? This cannot be undone.">
                          Delete
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">🧶</div>
          <h3>No products yet</h3>
          <p>Start selling by adding your first handmade item.</p>
          <a href="<?= BASE_URL ?>/seller/add_product.php" class="btn btn-dark">+ Add Product</a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

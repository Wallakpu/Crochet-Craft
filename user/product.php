<?php
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

// Fetch product with seller info
$stmt = $pdo->prepare(
    "SELECT p.*, u.name AS seller_name, u.id AS seller_id_val
     FROM products p
     JOIN users u ON u.id = p.seller_id
     WHERE p.id = ?"
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . BASE_URL . '/user/browse.php');
    exit;
}

// Handle add-to-cart from this page
$cartMsg = '';
$cartErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn() || currentUser()['role'] !== 'user') {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    if ($product['status'] !== 'available' || $product['stock'] < 1) {
        $cartErr = 'Sorry, this item is out of stock.';
    } else {
        $uid = currentUser()['id'];
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        // Insert or bump quantity
        $ins = $pdo->prepare(
            'INSERT INTO cart (user_id, product_id, quantity)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
        );
        $ins->execute([$uid, $id, $qty]);
        $cartMsg = 'Added to cart!';
    }
}

$pageTitle = htmlspecialchars($product['name']) . ' — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-body">
    <div class="container">

      <div class="breadcrumb" style="margin-bottom:28px;">
        <a href="<?= BASE_URL ?>/index.php">Home</a><span>/</span>
        <a href="<?= BASE_URL ?>/user/browse.php">Shop</a><span>/</span>
        <span><?= htmlspecialchars($product['name']) ?></span>
      </div>

      <div class="product-detail">

        <!-- Image -->
        <div class="product-detail-img">
          <?php if ($product['image_path']): ?>
            <img src="<?= UPLOAD_URL . htmlspecialchars($product['image_path']) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>">
          <?php else: ?>
            <div class="product-detail-img-ph">🧶</div>
          <?php endif; ?>
        </div>

        <!-- Info -->
        <div>
          <div class="product-card-cat" style="margin-bottom:8px;">
            <?= htmlspecialchars($product['category']) ?>
          </div>

          <h1><?= htmlspecialchars($product['name']) ?></h1>
          <div class="detail-price">NPR <?= number_format($product['price'], 0) ?></div>

          <p class="detail-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

          <div class="detail-meta">
            Seller: <span><?= htmlspecialchars($product['seller_name']) ?></span>
            &nbsp;·&nbsp;
            Stock:
            <span><?= $product['stock'] > 0 ? $product['stock'] . ' available' : 'Out of stock' ?></span>
          </div>

          <?php if ($cartMsg): ?>
            <div class="alert alert-success" data-auto-dismiss><?= htmlspecialchars($cartMsg) ?></div>
          <?php endif; ?>
          <?php if ($cartErr): ?>
            <div class="alert alert-error"><?= htmlspecialchars($cartErr) ?></div>
          <?php endif; ?>

          <?php if ($product['status'] === 'available' && $product['stock'] > 0): ?>
            <form method="POST" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
              <input type="hidden" name="add_to_cart" value="1">
              <input type="number" name="quantity" value="1" min="1"
                     max="<?= $product['stock'] ?>"
                     style="width:72px;padding:10px;border:1.5px solid var(--tan);
                            border-radius:8px;font-size:15px;text-align:center;">
              <button type="submit" class="btn btn-dark">Add to Cart</button>
              <a href="<?= BASE_URL ?>/user/custom_order.php?seller=<?= $product['seller_id_val'] ?>"
                 class="btn btn-outline">Request Custom</a>
            </form>
          <?php else: ?>
            <div class="alert alert-warn">This item is currently sold out.</div>
            <a href="<?= BASE_URL ?>/user/custom_order.php?seller=<?= $product['seller_id_val'] ?>"
               class="btn btn-outline">Request Custom Order</a>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/db.php';
requireRole('user');

$uid = currentUser()['id'];
$msg = '';
$err = '';

// ── Cart actions (POST) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $cartId    = (int)($_POST['cart_id']    ?? 0);
    $redirect  = $_POST['redirect']   ?? BASE_URL . '/user/cart.php';

    if ($action === 'add' && $productId) {
        // Verify product exists and is in stock
        $chk = $pdo->prepare("SELECT id, stock, status FROM products WHERE id = ?");
        $chk->execute([$productId]);
        $prod = $chk->fetch();

        if ($prod && $prod['status'] === 'available' && $prod['stock'] > 0) {
            $ins = $pdo->prepare(
                'INSERT INTO cart (user_id, product_id, quantity)
                 VALUES (?,?,1)
                 ON DUPLICATE KEY UPDATE quantity = quantity + 1'
            );
            $ins->execute([$uid, $productId]);
        }
        header("Location: $redirect");
        exit;

    } elseif ($action === 'remove' && $cartId) {
        $del = $pdo->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
        $del->execute([$cartId, $uid]);
        header('Location: ' . BASE_URL . '/user/cart.php');
        exit;

    } elseif ($action === 'update' && $cartId) {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $upd = $pdo->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?');
        $upd->execute([$qty, $cartId, $uid]);
        header('Location: ' . BASE_URL . '/user/cart.php');
        exit;

    } elseif ($action === 'clear') {
        $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$uid]);
        header('Location: ' . BASE_URL . '/user/cart.php');
        exit;
    }
}

// ── Fetch cart items ──────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT c.id AS cart_id, c.quantity,
            p.id AS product_id, p.name, p.price, p.image_path, p.status, p.stock
     FROM cart c
     JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?
     ORDER BY c.id DESC"
);
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

// Calculate totals
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$shipping = $subtotal > 0 ? 100 : 0;   // flat shipping NPR 100
$total    = $subtotal + $shipping;

$pageTitle = 'My Cart — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>My Cart</h1>
      <p><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></p>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <?php if ($items): ?>
        <div class="cart-layout">

          <!-- Cart items panel -->
          <div class="panel">
            <div class="panel-header">
              <h3>Items</h3>
              <form method="POST">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-danger btn-xs"
                        data-confirm="Remove all items from cart?">Clear cart</button>
              </form>
            </div>

            <?php foreach ($items as $item): ?>
              <div class="cart-item">
                <!-- Image -->
                <?php if ($item['image_path']): ?>
                  <img class="cart-item-img"
                       src="<?= UPLOAD_URL . htmlspecialchars($item['image_path']) ?>"
                       alt="<?= htmlspecialchars($item['name']) ?>">
                <?php else: ?>
                  <div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;font-size:28px;">🧶</div>
                <?php endif; ?>

                <div class="cart-item-info">
                  <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                  <div class="cart-item-price">
                    NPR <?= number_format($item['price'], 0) ?> each
                  </div>

                  <!-- Quantity control (form-based for reliability) -->
                  <div style="display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap;">
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="action"   value="update">
                      <input type="hidden" name="cart_id"  value="<?= $item['cart_id'] ?>">
                      <input type="hidden" name="quantity"
                             value="<?= max(1, $item['quantity'] - 1) ?>">
                      <button class="qty-btn" <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>−</button>
                    </form>

                    <span class="qty-num"><?= $item['quantity'] ?></span>

                    <form method="POST" style="display:inline">
                      <input type="hidden" name="action"   value="update">
                      <input type="hidden" name="cart_id"  value="<?= $item['cart_id'] ?>">
                      <input type="hidden" name="quantity"
                             value="<?= $item['quantity'] + 1 ?>">
                      <button class="qty-btn"
                              <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                    </form>

                    <span style="font-size:13px;color:var(--brown-mid);margin-left:4px;">
                      Subtotal: <strong>NPR <?= number_format($item['price'] * $item['quantity'], 0) ?></strong>
                    </span>
                  </div>
                </div>

                <!-- Remove -->
                <form method="POST">
                  <input type="hidden" name="action"  value="remove">
                  <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                  <button class="remove-btn" title="Remove"
                          data-confirm="Remove this item from cart?">✕</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Order summary -->
          <div class="order-summary">
            <h3>Order Summary</h3>
            <div class="sum-row"><span>Subtotal</span><span>NPR <?= number_format($subtotal, 0) ?></span></div>
            <div class="sum-row"><span>Shipping</span><span>NPR <?= number_format($shipping, 0) ?></span></div>
            <div class="sum-row total">
              <span>Total</span>
              <span id="cart-total">NPR <?= number_format($total, 0) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/user/checkout.php"
               class="btn btn-dark btn-full" style="margin-top:20px;">
              Proceed to Checkout
            </a>
            <a href="<?= BASE_URL ?>/user/browse.php"
               class="btn btn-outline btn-full" style="margin-top:10px;">
              Continue Shopping
            </a>
          </div>
        </div>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">🛒</div>
          <h3>Your cart is empty</h3>
          <p>Browse our collection and add something beautiful!</p>
          <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-dark">Shop Now</a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

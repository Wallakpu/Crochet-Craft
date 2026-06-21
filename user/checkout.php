<?php
require_once __DIR__ . '/../config/db.php';
requireRole('user');

$uid = currentUser()['id'];

// Fetch cart items
$stmt = $pdo->prepare(
    "SELECT c.quantity, p.id AS product_id, p.name, p.price, p.stock, p.status
     FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?"
);
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

if (!$items) {
    header('Location: ' . BASE_URL . '/user/cart.php');
    exit;
}

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$shipping = 100;
$total    = $subtotal + $shipping;
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['shipping_name']    ?? '');
    $address = trim($_POST['shipping_address'] ?? '');
    $phone   = trim($_POST['shipping_phone']   ?? '');

    if (!$name || !$address || !$phone) {
        $error = 'Please fill in all shipping details.';
    } else {
        try {
            $pdo->beginTransaction();

            // Create order record
            $ins = $pdo->prepare(
                'INSERT INTO orders (user_id, total_amount, shipping_name, shipping_address, shipping_phone)
                 VALUES (?,?,?,?,?)'
            );
            $ins->execute([$uid, $total, $name, $address, $phone]);
            $orderId = $pdo->lastInsertId();

            // Insert order items and decrement stock
            $insItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price)
                 VALUES (?,?,?,?,?)'
            );
            $decrStock = $pdo->prepare(
                'UPDATE products SET stock = stock - ?,
                 status = IF(stock - ? <= 0, "sold_out", "available")
                 WHERE id = ? AND stock >= ?'
            );

            foreach ($items as $item) {
                $insItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['quantity'],
                    $item['price'],
                ]);
                $decrStock->execute([
                    $item['quantity'],
                    $item['quantity'],
                    $item['product_id'],
                    $item['quantity'],
                ]);
            }

            // Clear cart
            $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$uid]);

            $pdo->commit();
            header('Location: ' . BASE_URL . '/user/orders.php?placed=' . $orderId);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Order failed. Please try again.';
        }
    }
}

$pageTitle = 'Checkout — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Checkout</h1>
    </div>
  </div>

  <div class="page-body">
    <div class="container">
      <div class="cart-layout">

        <!-- Shipping form -->
        <div class="panel">
          <div class="panel-header"><h3>Shipping Details</h3></div>
          <div class="panel-body">
            <?php if ($error): ?>
              <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="shipping_name"
                       value="<?= htmlspecialchars($_POST['shipping_name'] ?? currentUser()['name']) ?>"
                       placeholder="Recipient's name" required>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="shipping_phone"
                       value="<?= htmlspecialchars($_POST['shipping_phone'] ?? '') ?>"
                       placeholder="+977-98XXXXXXXX" required>
              </div>
              <div class="form-group">
                <label>Delivery Address</label>
                <textarea name="shipping_address" rows="3"
                          placeholder="Street, City, District…" required><?=
                    htmlspecialchars($_POST['shipping_address'] ?? '')
                ?></textarea>
              </div>

              <!-- Review items -->
              <h3 style="margin:24px 0 16px;">Order Items</h3>
              <?php foreach ($items as $item): ?>
                <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:10px;">
                  <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                  <span>NPR <?= number_format($item['price'] * $item['quantity'], 0) ?></span>
                </div>
              <?php endforeach; ?>

              <div style="margin-top:20px;">
                <button type="submit" class="btn btn-dark btn-full">
                  Place Order — NPR <?= number_format($total, 0) ?>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Summary sidebar -->
        <div class="order-summary">
          <h3>Summary</h3>
          <div class="sum-row"><span>Subtotal</span><span>NPR <?= number_format($subtotal, 0) ?></span></div>
          <div class="sum-row"><span>Shipping</span><span>NPR <?= number_format($shipping, 0) ?></span></div>
          <div class="sum-row total"><span>Total</span><span>NPR <?= number_format($total, 0) ?></span></div>
          <p style="font-size:12px;color:var(--brown-mid);margin-top:16px;line-height:1.6;">
            Payment is collected on delivery (COD). We'll contact you to confirm your order.
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

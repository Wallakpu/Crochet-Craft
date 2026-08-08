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

            $ins = $pdo->prepare(
                'INSERT INTO orders (user_id, total_amount, shipping_name, shipping_address, shipping_phone)
                 VALUES (?,?,?,?,?)'
            );
            $ins->execute([$uid, $total, $name, $address, $phone]);
            $orderId = $pdo->lastInsertId();

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

<style>
.payment-methods {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 4px;
}
.payment-option {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 2px solid var(--beige);
  border-radius: 10px;
  padding: 14px 16px;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  background: #fff;
}
.payment-option input[type="radio"] { display: none; }
.payment-option--active,
.payment-option:has(input:checked) {
  border-color: var(--rust);
  background: #fdf6f2;
}
.payment-option-icon { font-size: 24px; line-height: 1; }
.payment-option-title { font-weight: 600; font-size: 14px; color: var(--brown); }
.payment-option-sub   { font-size: 12px; color: var(--brown-mid); margin-top: 2px; }

@media (max-width: 480px) {
  .payment-methods { grid-template-columns: 1fr; }
}
</style>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Checkout</h1>
    </div>
  </div>

  <div class="page-body">
    <div class="container">
      <div class="cart-layout">

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

              <!-- Payment Method -->
              <h3 style="margin:24px 0 14px;">Payment Method</h3>
              <div class="payment-methods">
                <label class="payment-option payment-option--active" id="lbl-cod">
                  <input type="radio" name="payment_method" value="cod" checked
                         onchange="toggleCard(false)">
                  <span class="payment-option-icon"><i class="fa-solid fa-money-bill-wave"></i></span>
                  <div>
                    <div class="payment-option-title">Cash on Delivery</div>
                    <div class="payment-option-sub">Pay when it arrives</div>
                  </div>
                </label>
                <label class="payment-option" id="lbl-card">
                  <input type="radio" name="payment_method" value="card"
                         onchange="toggleCard(true)">
                  <span class="payment-option-icon"><i class="fa-solid fa-credit-card"></i></span>
                  <div>
                    <div class="payment-option-title">Card Payment</div>
                    <div class="payment-option-sub">Visa / Mastercard</div>
                  </div>
                </label>
              </div>

              <!-- Card details (non-functional UI) -->
              <div id="card-details" style="display:none;margin-top:16px;">
                <div class="form-group">
                  <label>Cardholder Name</label>
                  <input type="text" id="card_name" placeholder="Name as on card"
                         autocomplete="cc-name">
                </div>
                <div class="form-group">
                  <label>Card Number</label>
                  <input type="text" id="card_number" placeholder="0000 0000 0000 0000"
                         maxlength="19" autocomplete="cc-number"
                         oninput="formatCardNumber(this)">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                  <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="text" id="card_expiry" placeholder="MM/YY"
                           maxlength="5" autocomplete="cc-exp"
                           oninput="formatExpiry(this)">
                  </div>
                  <div class="form-group">
                    <label>CVV</label>
                    <input type="text" id="card_cvv" placeholder="•••"
                           maxlength="3" autocomplete="off"
                           oninput="this.value=this.value.replace(/\D/g,'')">
                  </div>
                </div>
                <p style="font-size:12px;color:var(--brown-mid);margin-top:2px;">
                  <i class="fa-solid fa-lock"></i> Your card details are encrypted and secure.
                </p>
              </div>

              <!-- Order items review -->
              <h3 style="margin:24px 0 14px;">Order Items</h3>
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
            We'll send you a confirmation once your order is received.
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
function toggleCard(show) {
  document.getElementById('card-details').style.display = show ? 'block' : 'none';
  document.getElementById('lbl-cod').classList.toggle('payment-option--active', !show);
  document.getElementById('lbl-card').classList.toggle('payment-option--active', show);
}

function formatCardNumber(input) {
  let v = input.value.replace(/\D/g, '').slice(0, 16);
  input.value = v.replace(/(.{4})/g, '$1 ').trim();
}

function formatExpiry(input) {
  let v = input.value.replace(/\D/g, '').slice(0, 4);
  if (v.length >= 3) v = v.slice(0, 2) + '/' + v.slice(2);
  input.value = v;
}
</script>

<?php require_once ROOT . '/includes/footer.php'; ?>

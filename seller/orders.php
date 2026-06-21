<?php
require_once __DIR__ . '/../config/db.php';
requireRole('seller');

$uid = currentUser()['id'];

// Fetch all orders that contain this seller's products
$orders = $pdo->prepare(
    "SELECT DISTINCT o.id, o.created_at, o.status, o.total_amount,
            o.shipping_name, o.shipping_address, o.shipping_phone,
            u.name AS customer_name, u.email AS customer_email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     WHERE o.id IN (
         SELECT oi.order_id FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE p.seller_id = ?
     )
     ORDER BY o.created_at DESC"
);
$orders->execute([$uid]);
$orders = $orders->fetchAll();

// Fetch order items only for this seller's products per order
function getSellerItemsForOrder(PDO $pdo, int $orderId, int $sellerId): array {
    $stmt = $pdo->prepare(
        "SELECT oi.product_name, oi.quantity, oi.unit_price
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ? AND p.seller_id = ?"
    );
    $stmt->execute([$orderId, $sellerId]);
    return $stmt->fetchAll();
}

$expandId = (int)($_GET['view'] ?? 0);

$pageTitle = 'My Orders — CrochetCraft Seller';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Orders</h1>
      <p>Orders that include your products</p>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <?php if ($orders): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td><strong>#<?= $o['id'] ?></strong></td>
                  <td>
                    <?= htmlspecialchars($o['customer_name']) ?><br>
                    <span style="font-size:12px;color:var(--brown-mid);"><?= htmlspecialchars($o['customer_email']) ?></span>
                  </td>
                  <td class="td-muted"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                  <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                  <td>NPR <?= number_format($o['total_amount'], 0) ?></td>
                  <td>
                    <a href="?view=<?= $o['id'] ?>" class="btn btn-outline btn-xs">
                      <?= $expandId === $o['id'] ? 'Hide' : 'View' ?>
                    </a>
                  </td>
                </tr>

                <?php if ($expandId === $o['id']): ?>
                  <?php $items = getSellerItemsForOrder($pdo, $o['id'], $uid); ?>
                  <tr>
                    <td colspan="6" style="background:var(--cream);padding:16px 20px;">
                      <div style="font-size:13px;margin-bottom:8px;"><strong>Your items in this order:</strong></div>
                      <?php foreach ($items as $item): ?>
                        <div style="font-size:13px;margin-bottom:4px;">
                          • <?= htmlspecialchars($item['product_name']) ?>
                            × <?= $item['quantity'] ?>
                            @ NPR <?= number_format($item['unit_price'], 0) ?>
                            = NPR <?= number_format($item['quantity'] * $item['unit_price'], 0) ?>
                        </div>
                      <?php endforeach; ?>
                      <div style="margin-top:10px;font-size:13px;color:var(--brown-mid);">
                        Ship to: <strong><?= htmlspecialchars($o['shipping_name']) ?></strong> —
                        <?= htmlspecialchars($o['shipping_address']) ?>
                        (<?= htmlspecialchars($o['shipping_phone']) ?>)
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>

              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📦</div>
          <h3>No orders yet</h3>
          <p>Orders will appear here once customers buy your products.</p>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

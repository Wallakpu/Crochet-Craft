<?php
require_once __DIR__ . '/../config/db.php';
requireRole('user');

$uid = currentUser()['id'];

// Flash message after placing an order
$placed = (int)($_GET['placed'] ?? 0);

// Fetch orders for this user, newest first
$stmt = $pdo->prepare(
    "SELECT o.*,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
     FROM orders o
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$uid]);
$orders = $stmt->fetchAll();

// Fetch order items for an expanded order (query param ?view=ID)
$viewId    = (int)($_GET['view'] ?? 0);
$viewItems = [];
if ($viewId) {
    $vi = $pdo->prepare(
        "SELECT oi.*, p.image_path
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ? AND (SELECT user_id FROM orders WHERE id = ?) = ?"
    );
    $vi->execute([$viewId, $viewId, $uid]);
    $viewItems = $vi->fetchAll();
}

// Custom orders for this user
$customStmt = $pdo->prepare(
    "SELECT co.*, u.name AS seller_name
     FROM custom_orders co
     LEFT JOIN users u ON u.id = co.seller_id
     WHERE co.user_id = ?
     ORDER BY co.created_at DESC"
);
$customStmt->execute([$uid]);
$customOrders = $customStmt->fetchAll();

$pageTitle = 'My Orders — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>My Orders</h1>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <?php if ($placed): ?>
        <div class="alert alert-success" data-auto-dismiss>
          Order #<?= $placed ?> placed successfully! We'll be in touch soon.
        </div>
      <?php endif; ?>

      <!-- Regular Orders -->
      <div class="panel" style="margin-bottom:32px;">
        <div class="panel-header"><h3>Purchase Orders</h3></div>

        <?php if ($orders): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td class="td-muted"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    <td><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                    <td><strong>NPR <?= number_format($o['total_amount'], 0) ?></strong></td>
                    <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td>
                      <a href="?view=<?= $o['id'] ?>"
                         class="btn btn-outline btn-xs">
                        <?= $viewId === $o['id'] ? 'Hide' : 'Details' ?>
                      </a>
                    </td>
                  </tr>

                  <?php if ($viewId === $o['id'] && $viewItems): ?>
                    <tr>
                      <td colspan="6" style="background:var(--cream);padding:16px 20px;">
                        <strong style="font-size:13px;">Items in order #<?= $o['id'] ?>:</strong>
                        <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
                          <?php foreach ($viewItems as $vi): ?>
                            <div style="display:flex;gap:12px;align-items:center;font-size:13px;">
                              <?php if ($vi['image_path']): ?>
                                <img src="<?= UPLOAD_URL . htmlspecialchars($vi['image_path']) ?>"
                                     style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                              <?php else: ?>
                                <div style="width:40px;height:40px;border-radius:6px;background:var(--beige);display:flex;align-items:center;justify-content:center;">🧶</div>
                              <?php endif; ?>
                              <div>
                                <div><strong><?= htmlspecialchars($vi['product_name']) ?></strong></div>
                                <div class="td-muted">
                                  <?= $vi['quantity'] ?> × NPR <?= number_format($vi['unit_price'], 0) ?>
                                  = NPR <?= number_format($vi['quantity'] * $vi['unit_price'], 0) ?>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                        <div style="margin-top:12px;font-size:13px;">
                          Shipping to: <strong><?= htmlspecialchars($o['shipping_name']) ?></strong>,
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
            <p>Time to treat yourself!</p>
            <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-dark">Shop Now</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Custom Orders -->
      <div class="panel">
        <div class="panel-header">
          <h3>Custom Order Requests</h3>
          <a href="<?= BASE_URL ?>/user/custom_order.php" class="btn btn-dark btn-sm">+ New Request</a>
        </div>

        <?php if ($customOrders): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Request #</th>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Seller</th>
                  <th>Deadline</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($customOrders as $co): ?>
                  <tr>
                    <td><strong>#<?= $co['id'] ?></strong></td>
                    <td class="td-muted"><?= date('M d, Y', strtotime($co['created_at'])) ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($co['description'], 0, 60, '…')) ?></td>
                    <td><?= htmlspecialchars($co['seller_name'] ?? 'Any seller') ?></td>
                    <td class="td-muted">
                      <?= $co['deadline'] ? date('M d, Y', strtotime($co['deadline'])) : '—' ?>
                    </td>
                    <td>
                      <span class="badge badge-<?= $co['status'] ?>"><?= ucfirst($co['status']) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">✏️</div>
            <h3>No custom orders</h3>
            <p>Request a custom piece made just for you!</p>
            <a href="<?= BASE_URL ?>/user/custom_order.php" class="btn btn-outline">Request Custom Order</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

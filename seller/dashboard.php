<?php
require_once __DIR__ . '/../config/db.php';
requireRole('seller');

$uid = currentUser()['id'];

// Stats
$prodCount  = $pdo->prepare('SELECT COUNT(*) FROM products WHERE seller_id = ?');
$prodCount->execute([$uid]); $totalProds = $prodCount->fetchColumn();

$orderCount = $pdo->prepare(
    'SELECT COUNT(DISTINCT o.id) FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN products p ON p.id = oi.product_id
     WHERE p.seller_id = ?'
);
$orderCount->execute([$uid]); $totalOrders = $orderCount->fetchColumn();

$customCount = $pdo->prepare("SELECT COUNT(*) FROM custom_orders WHERE seller_id = ? AND status = 'pending'");
$customCount->execute([$uid]); $pendingCustom = $customCount->fetchColumn();

$revenue = $pdo->prepare(
    'SELECT COALESCE(SUM(oi.quantity * oi.unit_price),0)
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE p.seller_id = ?'
);
$revenue->execute([$uid]); $totalRevenue = $revenue->fetchColumn();

// 5 most recent orders for this seller
$recentOrders = $pdo->prepare(
    'SELECT o.id, o.created_at, o.status, o.total_amount,
            u.name AS customer_name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     WHERE o.id IN (
         SELECT DISTINCT oi.order_id FROM order_items oi
         JOIN products p ON p.id = oi.product_id WHERE p.seller_id = ?
     )
     ORDER BY o.created_at DESC LIMIT 5'
);
$recentOrders->execute([$uid]);
$latestOrders = $recentOrders->fetchAll();

$pageTitle = 'Seller Dashboard — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Seller Hub</h1>
      <p>Welcome back, <?= htmlspecialchars(currentUser()['name']) ?>!</p>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-card-icon">🧶</div>
          <div class="stat-card-value"><?= $totalProds ?></div>
          <div class="stat-card-label">Products Listed</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">📦</div>
          <div class="stat-card-value"><?= $totalOrders ?></div>
          <div class="stat-card-label">Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">✏️</div>
          <div class="stat-card-value"><?= $pendingCustom ?></div>
          <div class="stat-card-label">Pending Requests</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">💰</div>
          <div class="stat-card-value">NPR <?= number_format($totalRevenue, 0) ?></div>
          <div class="stat-card-label">Total Revenue</div>
        </div>
      </div>

      <!-- Quick actions -->
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:32px;">
        <a href="<?= BASE_URL ?>/seller/add_product.php"      class="btn btn-dark">+ Add Product</a>
        <a href="<?= BASE_URL ?>/seller/manage_products.php"  class="btn btn-outline">My Products</a>
        <a href="<?= BASE_URL ?>/seller/orders.php"           class="btn btn-outline">Orders</a>
        <a href="<?= BASE_URL ?>/seller/custom_orders.php"    class="btn btn-outline">Custom Requests</a>
      </div>

      <!-- Recent orders table -->
      <div class="panel">
        <div class="panel-header">
          <h3>Recent Orders</h3>
          <a href="<?= BASE_URL ?>/seller/orders.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php if ($latestOrders): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Order #</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($latestOrders as $o): ?>
                  <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td class="td-muted"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    <td>NPR <?= number_format($o['total_amount'], 0) ?></td>
                    <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state" style="padding:40px 20px;">
            <div class="empty-icon">📦</div>
            <h3>No orders yet</h3>
            <p>List your first product to start getting orders!</p>
            <a href="<?= BASE_URL ?>/seller/add_product.php" class="btn btn-dark" style="margin-top:12px;">Add Product</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

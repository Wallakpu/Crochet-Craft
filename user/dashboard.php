<?php
require_once __DIR__ . '/../config/db.php';
requireRole('user');

$uid  = currentUser()['id'];
$user = currentUser();

// Stats
$orderCount  = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$orderCount->execute([$uid]);
$totalOrders = $orderCount->fetchColumn();

$customCount = $pdo->prepare('SELECT COUNT(*) FROM custom_orders WHERE user_id = ?');
$customCount->execute([$uid]);
$totalCustom = $customCount->fetchColumn();

$spent = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id = ?');
$spent->execute([$uid]);
$totalSpent = $spent->fetchColumn();

// Recent 5 orders
$recent = $pdo->prepare(
    'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'
);
$recent->execute([$uid]);
$recentOrders = $recent->fetchAll();

$pageTitle = 'My Dashboard — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Hello, <?= htmlspecialchars($user['name']) ?>!</h1>
      <p>Welcome to your CrochetCraft account</p>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-card-icon">📦</div>
          <div class="stat-card-value"><?= $totalOrders ?></div>
          <div class="stat-card-label">Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">✏️</div>
          <div class="stat-card-value"><?= $totalCustom ?></div>
          <div class="stat-card-label">Custom Requests</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">💰</div>
          <div class="stat-card-value">NPR <?= number_format($totalSpent, 0) ?></div>
          <div class="stat-card-label">Total Spent</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">🛒</div>
          <div class="stat-card-value"><?= cartCount($pdo) ?></div>
          <div class="stat-card-label">Items in Cart</div>
        </div>
      </div>

      <!-- Quick actions -->
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:32px;">
        <a href="<?= BASE_URL ?>/user/browse.php"       class="btn btn-dark">Browse Shop</a>
        <a href="<?= BASE_URL ?>/user/cart.php"         class="btn btn-outline">My Cart</a>
        <a href="<?= BASE_URL ?>/user/orders.php"       class="btn btn-outline">My Orders</a>
        <a href="<?= BASE_URL ?>/user/custom_order.php" class="btn btn-outline">Custom Order</a>
      </div>

      <!-- Recent orders -->
      <div class="panel">
        <div class="panel-header">
          <h3>Recent Orders</h3>
          <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-outline btn-sm">View All</a>
        </div>

        <?php if ($recentOrders): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Order #</th><th>Date</th><th>Total</th><th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $o): ?>
                  <tr>
                    <td><a href="<?= BASE_URL ?>/user/orders.php?view=<?= $o['id'] ?>"
                           style="color:var(--rust);font-weight:600;">#<?= $o['id'] ?></a></td>
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
            <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-dark" style="margin-top:12px;">Start Shopping</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

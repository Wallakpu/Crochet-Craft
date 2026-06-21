<?php
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// Site-wide stats
$stats = [
    'users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'sellers'  => $pdo->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetchColumn(),
    'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders'   => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue'  => $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn(),
    'custom'   => $pdo->query("SELECT COUNT(*) FROM custom_orders WHERE status='pending'")->fetchColumn(),
];

// Recent 5 orders
$recentOrders = $pdo->query(
    "SELECT o.id, o.created_at, o.status, o.total_amount, u.name AS customer
     FROM orders o JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 5"
)->fetchAll();

// Recent 5 registrations
$recentUsers = $pdo->query(
    "SELECT id, name, email, role, created_at FROM users
     WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Admin Dashboard — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Admin Dashboard</h1>
      <p>Platform overview</p>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-card-icon">👤</div>
          <div class="stat-card-value"><?= $stats['users'] ?></div>
          <div class="stat-card-label">Customers</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">🧶</div>
          <div class="stat-card-value"><?= $stats['sellers'] ?></div>
          <div class="stat-card-label">Sellers</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">🛍️</div>
          <div class="stat-card-value"><?= $stats['products'] ?></div>
          <div class="stat-card-label">Products</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">📦</div>
          <div class="stat-card-value"><?= $stats['orders'] ?></div>
          <div class="stat-card-label">Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">💰</div>
          <div class="stat-card-value">NPR <?= number_format($stats['revenue'], 0) ?></div>
          <div class="stat-card-label">Total Revenue</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon">✏️</div>
          <div class="stat-card-value"><?= $stats['custom'] ?></div>
          <div class="stat-card-label">Pending Custom</div>
        </div>
      </div>

      <!-- Quick links -->
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:32px;">
        <a href="<?= BASE_URL ?>/admin/manage_users.php"   class="btn btn-dark">Manage Customers</a>
        <a href="<?= BASE_URL ?>/admin/manage_sellers.php" class="btn btn-outline">Manage Sellers</a>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;">

        <!-- Recent orders -->
        <div class="panel">
          <div class="panel-header"><h3>Recent Orders</h3></div>
          <?php if ($recentOrders): ?>
            <div class="table-wrap" style="box-shadow:none;">
              <table>
                <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($recentOrders as $o): ?>
                    <tr>
                      <td>#<?= $o['id'] ?></td>
                      <td><?= htmlspecialchars($o['customer']) ?></td>
                      <td>NPR <?= number_format($o['total_amount'], 0) ?></td>
                      <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state" style="padding:30px 20px;">No orders yet.</div>
          <?php endif; ?>
        </div>

        <!-- Recent registrations -->
        <div class="panel">
          <div class="panel-header"><h3>Recent Registrations</h3></div>
          <?php if ($recentUsers): ?>
            <div class="table-wrap" style="box-shadow:none;">
              <table>
                <thead><tr><th>Name</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                  <?php foreach ($recentUsers as $u): ?>
                    <tr>
                      <td>
                        <?= htmlspecialchars($u['name']) ?><br>
                        <span style="font-size:11px;color:var(--brown-mid);"><?= htmlspecialchars($u['email']) ?></span>
                      </td>
                      <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                      <td class="td-muted"><?= date('M d', strtotime($u['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state" style="padding:30px 20px;">No users yet.</div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

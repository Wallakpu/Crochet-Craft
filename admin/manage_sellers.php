<?php
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// ── Actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($targetId && $targetId !== (int)currentUser()['id']) {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'seller'")->execute([$targetId]);
        } elseif ($action === 'suspend') {
            $pdo->prepare("UPDATE users SET status='suspended' WHERE id = ? AND role='seller'")->execute([$targetId]);
        } elseif ($action === 'activate') {
            $pdo->prepare("UPDATE users SET status='active' WHERE id = ? AND role='seller'")->execute([$targetId]);
        }
    }
    header('Location: ' . BASE_URL . '/admin/manage_sellers.php');
    exit;
}

// ── Search ─────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$params = ['seller'];
$where  = "WHERE u.role = ?";
if ($search) {
    $where   .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare(
    "SELECT u.*,
            COUNT(DISTINCT p.id)    AS product_count,
            COUNT(DISTINCT oi.id)   AS order_count
     FROM users u
     LEFT JOIN products p      ON p.seller_id = u.id
     LEFT JOIN order_items oi  ON oi.product_id = p.id
     $where
     GROUP BY u.id
     ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$sellers = $stmt->fetchAll();

$pageTitle = 'Manage Sellers — CrochetCraft Admin';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h1>Manage Sellers</h1>
        <p><?= count($sellers) ?> seller<?= count($sellers) !== 1 ? 's' : '' ?></p>
      </div>
      <a href="<?= BASE_URL ?>/admin/manage_users.php" class="btn btn-outline btn-sm">Switch to Customers</a>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <!-- Search -->
      <form method="GET" class="filter-bar">
        <div class="search-wrap">
          <span class="si">🔍</span>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                 placeholder="Search seller by name or email…">
        </div>
        <button type="submit" class="btn btn-dark btn-sm">Search</button>
        <?php if ($search): ?>
          <a href="<?= BASE_URL ?>/admin/manage_sellers.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>

      <?php if ($sellers): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Name</th><th>Email</th>
                <th>Products</th><th>Order Items</th>
                <th>Status</th><th>Joined</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sellers as $s): ?>
                <tr>
                  <td><?= $s['id'] ?></td>
                  <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                  <td class="td-muted"><?= htmlspecialchars($s['email']) ?></td>
                  <td><?= $s['product_count'] ?></td>
                  <td><?= $s['order_count'] ?></td>
                  <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                  <td class="td-muted"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                  <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                      <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?= $s['id'] ?>"
                         class="btn btn-outline btn-xs">Edit</a>

                      <?php if ($s['status'] === 'active'): ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                          <input type="hidden" name="action"  value="suspend">
                          <button class="btn btn-xs" style="background:#f59e0b;color:#fff;"
                                  data-confirm="Suspend this seller?">Suspend</button>
                        </form>
                      <?php else: ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                          <input type="hidden" name="action"  value="activate">
                          <button class="btn btn-sage btn-xs">Activate</button>
                        </form>
                      <?php endif; ?>

                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="action"  value="delete">
                        <button class="btn btn-danger btn-xs"
                                data-confirm="Delete seller '<?= htmlspecialchars($s['name']) ?>'? All their products will be deleted too.">
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
          <h3>No sellers found</h3>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

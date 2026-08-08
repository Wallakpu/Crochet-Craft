<?php
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// ── Actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    // Never allow action on the admin themselves
    if ($targetId && $targetId !== (int)currentUser()['id']) {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?')->execute([$targetId, 'user']);
        } elseif ($action === 'suspend') {
            $pdo->prepare("UPDATE users SET status='suspended' WHERE id = ? AND role='user'")->execute([$targetId]);
        } elseif ($action === 'activate') {
            $pdo->prepare("UPDATE users SET status='active' WHERE id = ? AND role='user'")->execute([$targetId]);
        }
    }
    header('Location: ' . BASE_URL . '/admin/manage_users.php');
    exit;
}

// ── Search ─────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$params = ['user'];
$where  = "WHERE role = ?";
if ($search) {
    $where   .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Customers — CrochetCraft Admin';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h1>Manage Customers</h1>
        <p><?= count($users) ?> customer<?= count($users) !== 1 ? 's' : '' ?></p>
      </div>
      <a href="<?= BASE_URL ?>/admin/manage_sellers.php" class="btn btn-outline btn-sm">Switch to Sellers</a>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <!-- Search -->
      <form method="GET" class="filter-bar">
        <div class="search-wrap">
          <span class="si">🔍</span>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                 placeholder="Search by name or email…">
        </div>
        <button type="submit" class="btn btn-dark btn-sm">Search</button>
        <?php if ($search): ?>
          <a href="<?= BASE_URL ?>/admin/manage_users.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>

      <?php if ($users): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Status</th>
                <th>Joined</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= $u['id'] ?></td>
                  <td>
                    <strong><?= htmlspecialchars($u['name']) ?></strong>
                  </td>
                  <td class="td-muted"><?= htmlspecialchars($u['email']) ?></td>
                  <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                  <td class="td-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                      <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?= $u['id'] ?>"
                         class="btn btn-outline btn-xs">Edit</a>

                      <?php if ($u['status'] === 'active'): ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <input type="hidden" name="action"  value="suspend">
                          <button class="btn btn-xs" style="background:#f59e0b;color:#fff;"
                                  data-confirm="Suspend this user?">Suspend</button>
                        </form>
                      <?php else: ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <input type="hidden" name="action"  value="activate">
                          <button class="btn btn-sage btn-xs">Activate</button>
                        </form>
                      <?php endif; ?>

                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="action"  value="delete">
                        <button class="btn btn-danger btn-xs"
                                data-confirm="Permanently delete user '<?= htmlspecialchars($u['name']) ?>'? All their orders and cart data will be deleted.">
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
          <div class="empty-icon">👤</div>
          <h3>No customers found</h3>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role != 'admin'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . BASE_URL . '/admin/manage_users.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $role   = in_array($_POST['role'] ?? '', ['user','seller']) ? $_POST['role'] : $user['role'];
    $status = in_array($_POST['status'] ?? '', ['active','suspended']) ? $_POST['status'] : $user['status'];

    if (!$name || !$email) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Check email uniqueness (excluding this user)
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $chk->execute([$email, $id]);
        if ($chk->fetch()) {
            $error = 'That email is already taken by another account.';
        } else {
            $newPassword = trim($_POST['new_password'] ?? '');
            if ($newPassword) {
                if (strlen($newPassword) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $pdo->prepare(
                        'UPDATE users SET name=?, email=?, role=?, status=?, password_hash=? WHERE id=?'
                    )->execute([$name, $email, $role, $status, $hash, $id]);
                }
            } else {
                $pdo->prepare(
                    'UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?'
                )->execute([$name, $email, $role, $status, $id]);
            }
            if (!$error) {
                $success = 'User updated successfully.';
                // Refresh user data
                $stmt->execute([$id]);
                $user = $pdo->prepare("SELECT * FROM users WHERE id = ?")->execute([$id]) && ($user = $stmt->fetch());
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            }
        }
    }
}

$backUrl = $user['role'] === 'seller'
    ? BASE_URL . '/admin/manage_sellers.php'
    : BASE_URL . '/admin/manage_users.php';

$pageTitle = 'Edit User — CrochetCraft Admin';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Edit User</h1>
    </div>
  </div>

  <div class="page-body">
    <div class="container" style="max-width:540px;">

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success" data-auto-dismiss><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-header">
          <h3><?= htmlspecialchars($user['name']) ?></h3>
          <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
        </div>
        <div class="panel-body">
          <form method="POST" novalidate>

            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="name"
                     value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>" required>
            </div>

            <div class="form-group">
              <label>Email Address</label>
              <input type="email" name="email"
                     value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Role</label>
                <select name="role">
                  <option value="user"   <?= ($user['role']==='user')   ? 'selected':'' ?>>Customer</option>
                  <option value="seller" <?= ($user['role']==='seller') ? 'selected':'' ?>>Seller</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status</label>
                <select name="status">
                  <option value="active"    <?= ($user['status']==='active')    ? 'selected':'' ?>>Active</option>
                  <option value="suspended" <?= ($user['status']==='suspended') ? 'selected':'' ?>>Suspended</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>New Password <span style="color:var(--brown-mid);font-weight:400;">(leave blank to keep current)</span></label>
              <input type="password" name="new_password" placeholder="Min 6 characters">
            </div>

            <div style="display:flex;gap:12px;margin-top:8px;">
              <button type="submit" class="btn btn-dark">Save Changes</button>
              <a href="<?= $backUrl ?>" class="btn btn-outline">Cancel</a>
            </div>

          </form>
        </div>
      </div>

      <div style="margin-top:12px;font-size:13px;color:var(--brown-mid);">
        Account created: <?= date('M d, Y H:i', strtotime($user['created_at'])) ?>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

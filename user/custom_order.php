<?php
require_once __DIR__ . '/../config/db.php';
requireRole('user');

$uid = currentUser()['id'];

// Fetch all sellers for the dropdown
$sellers = $pdo->query("SELECT id, name FROM users WHERE role = 'seller' AND status = 'active' ORDER BY name")->fetchAll();

// Pre-select seller from query param (e.g., coming from a product page)
$defaultSeller = (int)($_GET['seller'] ?? 0);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sellerId    = (int)($_POST['seller_id'] ?? 0) ?: null;
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color']       ?? '');
    $size        = trim($_POST['size']        ?? '');
    $deadline    = trim($_POST['deadline']    ?? '');
    $budget      = (float)($_POST['budget']   ?? 0);

    if (!$description) {
        $error = 'Please describe what you want.';
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO custom_orders (user_id, seller_id, description, color, size, deadline, budget)
             VALUES (?,?,?,?,?,?,?)'
        );
        $ins->execute([
            $uid,
            $sellerId,
            $description,
            $color ?: null,
            $size  ?: null,
            $deadline ?: null,
            $budget   ?: null,
        ]);
        $success = 'Custom order request submitted! A seller will respond soon.';
    }
}

$pageTitle = 'Custom Order — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Request a Custom Order</h1>
      <p>Tell us your dream piece — our crafters will make it happen</p>
    </div>
  </div>

  <div class="page-body">
    <div class="container" style="max-width:680px;">

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success" data-auto-dismiss><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-header"><h3>Order Details</h3></div>
        <div class="panel-body">
          <form method="POST" novalidate>

            <!-- Seller selection -->
            <div class="form-group">
              <label>Seller <span style="color:var(--brown-mid);font-weight:400;">(optional — leave blank to reach all sellers)</span></label>
              <select name="seller_id">
                <option value="">Any available seller</option>
                <?php foreach ($sellers as $s): ?>
                  <option value="<?= $s['id'] ?>"
                    <?= (int)($_POST['seller_id'] ?? $defaultSeller) === $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Description -->
            <div class="form-group">
              <label>Description <span style="color:var(--rust);">*</span></label>
              <textarea name="description" rows="5"
                        placeholder="Describe what you want: type of item, style, who it's for…" required><?=
                htmlspecialchars($_POST['description'] ?? '')
              ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Preferred Color(s)</label>
                <input type="text" name="color"
                       value="<?= htmlspecialchars($_POST['color'] ?? '') ?>"
                       placeholder="e.g., dusty pink, sage green">
              </div>
              <div class="form-group">
                <label>Size / Dimensions</label>
                <input type="text" name="size"
                       value="<?= htmlspecialchars($_POST['size'] ?? '') ?>"
                       placeholder="e.g., small, 30cm tall">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Deadline</label>
                <input type="date" name="deadline"
                       value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>"
                       min="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                <div class="form-hint">When do you need it by?</div>
              </div>
              <div class="form-group">
                <label>Your Budget (NPR)</label>
                <input type="number" name="budget"
                       value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>"
                       placeholder="e.g., 1500" min="0" step="50">
              </div>
            </div>

            <div style="margin-top:8px;">
              <button type="submit" class="btn btn-dark">Submit Request</button>
              <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-outline" style="margin-left:10px;">
                View My Requests
              </a>
            </div>

          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

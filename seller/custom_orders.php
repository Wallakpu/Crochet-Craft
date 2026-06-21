<?php
require_once __DIR__ . '/../config/db.php';
requireRole('seller');

$uid = currentUser()['id'];

// ── Accept / Decline action ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coId   = (int)($_POST['custom_order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($coId && in_array($action, ['accept','decline'])) {
        $status = $action === 'accept' ? 'accepted' : 'declined';
        $upd    = $pdo->prepare(
            'UPDATE custom_orders SET status = ? WHERE id = ? AND seller_id = ?'
        );
        $upd->execute([$status, $coId, $uid]);
    }
    header('Location: ' . BASE_URL . '/seller/custom_orders.php');
    exit;
}

// ── Fetch custom orders directed at this seller ───────────
$stmt = $pdo->prepare(
    "SELECT co.*, u.name AS customer_name, u.email AS customer_email
     FROM custom_orders co
     JOIN users u ON u.id = co.user_id
     WHERE co.seller_id = ?
     ORDER BY co.created_at DESC"
);
$stmt->execute([$uid]);
$orders = $stmt->fetchAll();

$pageTitle = 'Custom Order Requests — CrochetCraft Seller';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <h1>Custom Order Requests</h1>
      <p>Review and respond to incoming custom order requests</p>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <?php if ($orders): ?>
        <div style="display:flex;flex-direction:column;gap:20px;">
          <?php foreach ($orders as $co): ?>
            <div class="panel">
              <div class="panel-header">
                <div>
                  <strong>Request #<?= $co['id'] ?></strong>
                  from <span style="color:var(--rust);"><?= htmlspecialchars($co['customer_name']) ?></span>
                  <span style="color:var(--brown-mid);font-size:13px;margin-left:8px;">
                    (<?= htmlspecialchars($co['customer_email']) ?>)
                  </span>
                </div>
                <span class="badge badge-<?= $co['status'] ?>"><?= ucfirst($co['status']) ?></span>
              </div>
              <div class="panel-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:14px;margin-bottom:16px;">
                  <div>
                    <strong>Description</strong><br>
                    <span style="color:var(--brown-mid);"><?= nl2br(htmlspecialchars($co['description'])) ?></span>
                  </div>
                  <div>
                    <div style="margin-bottom:8px;">
                      <strong>Color:</strong>
                      <?= $co['color'] ? htmlspecialchars($co['color']) : '<em style="color:#999">Not specified</em>' ?>
                    </div>
                    <div style="margin-bottom:8px;">
                      <strong>Size:</strong>
                      <?= $co['size'] ? htmlspecialchars($co['size']) : '<em style="color:#999">Not specified</em>' ?>
                    </div>
                    <div style="margin-bottom:8px;">
                      <strong>Deadline:</strong>
                      <?= $co['deadline'] ? date('M d, Y', strtotime($co['deadline'])) : '<em style="color:#999">No deadline</em>' ?>
                    </div>
                    <div>
                      <strong>Budget:</strong>
                      <?= $co['budget'] ? 'NPR ' . number_format($co['budget'], 0) : '<em style="color:#999">Not specified</em>' ?>
                    </div>
                  </div>
                </div>

                <div style="font-size:12px;color:var(--brown-mid);margin-bottom:12px;">
                  Submitted: <?= date('M d, Y H:i', strtotime($co['created_at'])) ?>
                </div>

                <?php if ($co['status'] === 'pending'): ?>
                  <div style="display:flex;gap:10px;">
                    <form method="POST">
                      <input type="hidden" name="custom_order_id" value="<?= $co['id'] ?>">
                      <input type="hidden" name="action" value="accept">
                      <button class="btn btn-sage btn-sm">Accept</button>
                    </form>
                    <form method="POST">
                      <input type="hidden" name="custom_order_id" value="<?= $co['id'] ?>">
                      <input type="hidden" name="action" value="decline">
                      <button class="btn btn-danger btn-sm"
                              data-confirm="Decline this request?">Decline</button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">✉️</div>
          <h3>No custom requests yet</h3>
          <p>When customers send you custom order requests, they'll appear here.</p>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

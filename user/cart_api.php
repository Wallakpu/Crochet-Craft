<?php
// AJAX endpoint for live cart quantity updates (used by JS qty buttons)
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isLoggedIn() || currentUser()['role'] !== 'user') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$cartId  = (int)($input['cart_id']  ?? 0);
$qty     = (int)($input['quantity'] ?? 1);
$uid     = currentUser()['id'];

if (!$cartId || $qty < 1) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Verify ownership and get product price
$stmt = $pdo->prepare(
    'SELECT c.id, c.quantity, p.price, p.stock
     FROM cart c JOIN products p ON p.id = c.product_id
     WHERE c.id = ? AND c.user_id = ?'
);
$stmt->execute([$cartId, $uid]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['error' => 'Cart item not found']);
    exit;
}

// Clamp qty to available stock
$qty = min($qty, $row['stock']);
if ($qty < 1) $qty = 1;

$pdo->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?')
    ->execute([$qty, $cartId, $uid]);

// Recalculate cart total
$totStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(c.quantity * p.price),0) AS total,
            COALESCE(SUM(c.quantity),0) AS count
     FROM cart c JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?'
);
$totStmt->execute([$uid]);
$totRow = $totStmt->fetch();

echo json_encode([
    'subtotal'   => round($row['price'] * $qty),
    'total'      => round($totRow['total']),
    'cart_count' => (int)$totRow['count'],
]);

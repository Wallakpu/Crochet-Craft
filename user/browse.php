<?php
require_once __DIR__ . '/../config/db.php';

// ── Filters from GET ──────────────────────────────────────
$search   = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = $_GET['sort'] ?? 'newest';
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);

// ── Pagination ────────────────────────────────────────────
$perPage  = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

// ── Build query with prepared statement ───────────────────
$conditions = ["p.status = 'available'"];
$params     = [];

if ($search) {
    $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[]     = "%$search%";
    $params[]     = "%$search%";
}
if ($category) {
    $conditions[] = "p.category = ?";
    $params[]     = $category;
}
if ($minPrice > 0) {
    $conditions[] = "p.price >= ?";
    $params[]     = $minPrice;
}
if ($maxPrice > 0) {
    $conditions[] = "p.price <= ?";
    $params[]     = $maxPrice;
}

$where = 'WHERE ' . implode(' AND ', $conditions);

$orderMap = [
    'newest'    => 'p.created_at DESC',
    'oldest'    => 'p.created_at ASC',
    'price_asc' => 'p.price ASC',
    'price_desc'=> 'p.price DESC',
    'name_asc'  => 'p.name ASC',
];
$order = $orderMap[$sort] ?? 'p.created_at DESC';

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$countStmt->execute($params);
$total     = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Products for this page
$dataParams = array_merge($params, [$perPage, $offset]);
$dataStmt   = $pdo->prepare(
    "SELECT p.*, u.name AS seller_name
     FROM products p
     JOIN users u ON u.id = p.seller_id
     $where
     ORDER BY $order
     LIMIT ? OFFSET ?"
);
$dataStmt->execute($dataParams);
$products   = $dataStmt->fetchAll();

// Categories for filter dropdown
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'Shop — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="page-top">
  <div class="page-header">
    <div class="container">
      <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/index.php">Home</a>
        <span>/</span>
        <span>Shop</span>
        <?php if ($category): ?>
          <span>/</span><span><?= htmlspecialchars($category) ?></span>
        <?php endif; ?>
      </div>
      <h1><?= $category ? ucfirst(htmlspecialchars($category)) : 'All Products' ?></h1>
      <p><?= $total ?> item<?= $total !== 1 ? 's' : '' ?> found</p>
    </div>
  </div>

  <div class="page-body">
    <div class="container">

      <!-- Filter bar -->
      <form method="GET" class="filter-bar">
        <div class="search-wrap">
          <span class="si">🔍</span>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                 placeholder="Search products…">
        </div>

        <select name="category" class="filter-select">
          <option value="">All categories</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= htmlspecialchars($c['slug']) ?>"
              <?= $category === $c['slug'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="sort" class="filter-select">
          <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest</option>
          <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low → High</option>
          <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High → Low</option>
          <option value="name_asc"   <?= $sort==='name_asc'   ? 'selected':'' ?>>Name A–Z</option>
        </select>

        <input type="number" name="min_price" class="filter-select"
               placeholder="Min NPR" value="<?= $minPrice ?: '' ?>" min="0" style="width:110px;">
        <input type="number" name="max_price" class="filter-select"
               placeholder="Max NPR" value="<?= $maxPrice ?: '' ?>" min="0" style="width:110px;">

        <button type="submit" class="btn btn-dark btn-sm">Filter</button>
        <?php if ($search || $category || $minPrice || $maxPrice): ?>
          <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>

      <!-- Product grid -->
      <?php if ($products): ?>
        <div class="products-grid">
          <?php foreach ($products as $p): ?>
            <div class="product-card">
              <a href="<?= BASE_URL ?>/user/product.php?id=<?= $p['id'] ?>">
                <?php if ($p['image_path']): ?>
                  <img class="product-card-img"
                       src="<?= UPLOAD_URL . htmlspecialchars($p['image_path']) ?>"
                       alt="<?= htmlspecialchars($p['name']) ?>">
                <?php else: ?>
                  <div class="product-card-placeholder">🧶</div>
                <?php endif; ?>
              </a>
              <div class="product-card-body">
                <div class="product-card-cat"><?= htmlspecialchars($p['category']) ?></div>
                <a href="<?= BASE_URL ?>/user/product.php?id=<?= $p['id'] ?>">
                  <div class="product-card-name"><?= htmlspecialchars($p['name']) ?></div>
                </a>
                <div class="product-card-foot">
                  <span class="product-price">NPR <?= number_format($p['price'], 0) ?></span>
                  <?php if ($p['status'] === 'available' && $p['stock'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/user/cart.php">
                      <input type="hidden" name="action"     value="add">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="redirect"   value="<?= BASE_URL ?>/user/browse.php">
                      <button class="btn btn-dark btn-sm" type="submit">Add</button>
                    </form>
                  <?php else: ?>
                    <span class="sold-out-tag">Sold Out</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php
            // Build query string without page for links
            $qp = $_GET;
            for ($i = 1; $i <= $totalPages; $i++):
                $qp['page'] = $i;
                $link = '?' . http_build_query($qp);
            ?>
              <a href="<?= $link ?>"
                 class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">🔍</div>
          <h3>No products found</h3>
          <p>Try adjusting your filters or search term.</p>
          <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-outline">Clear filters</a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = 'CrochetCraft — Handmade with Love';

// Fetch 8 latest available products for the homepage grid
$featStmt = $pdo->query(
    "SELECT p.*, u.name AS seller_name
     FROM products p
     JOIN users u ON u.id = p.seller_id
     WHERE p.status = 'available'
     ORDER BY p.created_at DESC
     LIMIT 8"
);
$featured = $featStmt->fetchAll();

// Fetch all categories
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

require_once ROOT . '/includes/header.php';
?>

<!-- ── Hero ──────────────────────────────────────────────── -->
<section class="hero">

  <div class="hero-left">
    <div class="hero-badge">✦ Handmade in Nepal</div>

    <h1 class="hero-title">
      Crafted with
      <em>thread &amp; love</em>
    </h1>

    <p class="hero-desc">
      Discover one-of-a-kind crochet dolls, hats, keyrings, plushies,
      and cozy knitted pieces — each made by hand with care and creativity.
    </p>

    <div class="hero-buttons">
      <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-dark">Shop Now</a>
      <a href="#about" class="btn btn-outline">Our Story</a>
    </div>

    <div class="hero-divider"></div>

    <div class="hero-stats">
      <div>
        <div class="stat-num">120+</div>
        <div class="stat-lbl">Handmade items</div>
      </div>
      <div>
        <div class="stat-num">8</div>
        <div class="stat-lbl">Categories</div>
      </div>
      <div>
        <div class="stat-num">100%</div>
        <div class="stat-lbl">Handcrafted</div>
      </div>
    </div>
  </div>

  <!-- Decorative right panel -->
  <div class="hero-right">
    <div class="new-arrivals-badge">🌱 New arrivals</div>

    <div class="hero-card">
      <div class="hero-card-img" style="padding:0;">
        <img src="<?= BASE_URL ?>/assets/images/hero-card.jpg"
             alt="Bear Amigurumi"
             style="width:100%;height:200px;object-fit:cover;">
      </div>
      <div class="hero-card-body">
        <div class="hero-card-cat">🐻 Amigurumi</div>
        <div class="hero-card-name">Cute Teddy Bear Amigurumi</div>
        <div class="hero-card-foot">
          <span class="hero-card-price">NPR 950</span>
          <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-dark btn-sm">Add</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Featured Products ──────────────────────────────────── -->
<section class="section" id="shop">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Our Collection</div>
      <h2>Featured Products</h2>
      <p>Each piece is handcrafted with premium yarn and endless love.</p>
    </div>

    <?php if ($featured): ?>
      <div class="products-grid">
        <?php foreach ($featured as $p): ?>
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
                <?php if ($p['status'] === 'available'): ?>
                  <form method="POST" action="<?= BASE_URL ?>/user/cart.php">
                    <input type="hidden" name="action"     value="add">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="redirect"   value="<?= BASE_URL ?>/index.php">
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

      <div style="text-align:center;margin-top:36px;">
        <a href="<?= BASE_URL ?>/user/browse.php" class="btn btn-outline">View All Products</a>
      </div>

    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">🧶</div>
        <h3>No products yet</h3>
        <p>Our sellers are getting ready. Check back soon!</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ── Categories ────────────────────────────────────────── -->
<section class="section section-alt" id="categories">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Browse By</div>
      <h2>Shop Categories</h2>
    </div>
    <div class="categories-grid">
      <?php foreach ($cats as $cat): ?>
        <a href="<?= BASE_URL ?>/user/browse.php?category=<?= urlencode($cat['slug']) ?>"
           class="cat-card">
          <div class="cat-icon"><?= $cat['icon'] ?></div>
          <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Custom Order CTA ───────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="cta-banner">
      <div>
        <h2>Can't find what you want?</h2>
        <p>Request a custom order — describe your dream piece and our crafters will make it just for you.</p>
      </div>
      <a href="<?= BASE_URL ?>/user/custom_order.php" class="btn btn-rust" style="flex-shrink:0;">
        Request Custom Order
      </a>
    </div>
  </div>
</section>

<!-- ── About ─────────────────────────────────────────────── -->
<section class="section section-alt" id="about">
  <div class="container" style="max-width:720px;text-align:center;">
    <div class="section-tag">Our Story</div>
    <h2>Made with Hands, Not Machines</h2>
    <p style="color:var(--brown-mid);font-size:17px;line-height:1.8;margin-top:16px;">
      CrochetCraft was born from a passion for handmade crafts. Every item in our marketplace
      is created by independent artisans in Nepal — no factories, no shortcuts. When you buy
      from us, you support a real maker and get a truly one-of-a-kind piece.
    </p>
  </div>
</section>

<!-- ── Contact ───────────────────────────────────────────── -->
<section class="section" id="contact">
  <div class="container" style="max-width:520px;">
    <div class="section-header">
      <div class="section-tag">Get In Touch</div>
      <h2>Contact Us</h2>
    </div>
    <div class="panel">
      <div class="panel-body">
        <p style="color:var(--brown-mid);font-size:14px;margin-bottom:20px;">
          Have questions? Reach out and we'll get back to you within 24 hours.
        </p>
        <div style="display:flex;flex-direction:column;gap:12px;font-size:14px;color:var(--brown-mid);">
          <div>📧 &nbsp;<strong>hello@crochetcraft.np</strong></div>
          <div>📞 &nbsp;<strong>+977-9800000000</strong></div>
          <div>📍 &nbsp;Kathmandu, Nepal</div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once ROOT . '/includes/footer.php'; ?>

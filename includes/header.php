<?php
// header.php is included AFTER config/db.php is already required on each page.
// $pdo and all constants are available here.

$cartCount = cartCount($pdo);
$user      = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'CrochetCraft — Handmade with Love') ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>

<nav>
  <div class="nav-inner">

    <!-- Logo -->
    <a href="<?= BASE_URL ?>/index.php" class="logo">Crochet<span>Craft</span></a>

    <!-- Main links -->
    <ul class="nav-links" id="navLinks">
      <li><a href="<?= BASE_URL ?>/user/browse.php">Shop</a></li>
      <li><a href="<?= BASE_URL ?>/user/browse.php?view=categories">Categories</a></li>
      <li><a href="<?= BASE_URL ?>/index.php#about">About</a></li>
      <li><a href="<?= BASE_URL ?>/index.php#contact">Contact</a></li>
    </ul>

    <!-- Right-side actions -->
    <div class="nav-actions">

      <!-- Search -->
      <button class="nav-icon-btn" id="searchToggle" title="Search">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>

      <?php if ($user && $user['role'] === 'user'): ?>
        <!-- Cart (customers only) -->
        <a href="<?= BASE_URL ?>/user/cart.php" class="nav-icon-btn" title="Cart">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 01-8 0"/>
          </svg>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge" id="cart-count"><?= $cartCount ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>

      <?php if (!$user): ?>
        <!-- Guest: Login + Sign Up -->
        <a href="<?= BASE_URL ?>/auth/login.php"    class="btn btn-outline btn-sm">Login</a>
        <a href="<?= BASE_URL ?>/auth/register.php"  class="btn btn-dark btn-sm">Sign Up</a>

      <?php elseif ($user['role'] === 'user'): ?>
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-outline btn-sm">My Account</a>
        <a href="<?= BASE_URL ?>/auth/logout.php"    class="btn btn-dark btn-sm">Logout</a>

      <?php elseif ($user['role'] === 'seller'): ?>
        <a href="<?= BASE_URL ?>/seller/dashboard.php" class="btn btn-outline btn-sm">Seller Hub</a>
        <a href="<?= BASE_URL ?>/auth/logout.php"      class="btn btn-dark btn-sm">Logout</a>

      <?php elseif ($user['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm">Admin</a>
        <a href="<?= BASE_URL ?>/auth/logout.php"     class="btn btn-dark btn-sm">Logout</a>
      <?php endif; ?>

      <!-- Hamburger (mobile) -->
      <button class="hamburger" id="hamburger" aria-label="Menu">&#9776;</button>
    </div>
  </div>
</nav>

<!-- Search overlay -->
<div class="search-overlay" id="searchOverlay">
  <div class="search-box">
    <form action="<?= BASE_URL ?>/user/browse.php" method="GET">
      <input type="text" name="q" placeholder="Search for crochet items…" autocomplete="off">
    </form>
    <p style="font-size:13px;color:#999;margin-top:10px;text-align:center;">
      Press <kbd>Esc</kbd> or click outside to close
      &nbsp;<button id="searchClose" style="background:none;border:none;color:#999;cursor:pointer;font-size:18px;">✕</button>
    </p>
  </div>
</div>

<script>
// Esc closes search overlay
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.getElementById('searchOverlay')?.classList.remove('active');
});
</script>

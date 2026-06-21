<!-- ── Footer ───────────────────────────────────────────────── -->
<footer>
  <div class="container">
    <div class="footer-grid">

      <!-- Brand -->
      <div class="footer-brand">
        <a href="<?= BASE_URL ?>/index.php" class="logo">Crochet<span>Craft</span></a>
        <p>Handmade crochet goods crafted with thread, love, and a little bit of magic. Each piece is unique — just like you.</p>
      </div>

      <!-- Shop -->
      <div class="footer-col">
        <h4>Shop</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>/user/browse.php">All Products</a></li>
          <li><a href="<?= BASE_URL ?>/user/browse.php?category=amigurumi">Amigurumi</a></li>
          <li><a href="<?= BASE_URL ?>/user/browse.php?category=hats">Hats &amp; Scarves</a></li>
          <li><a href="<?= BASE_URL ?>/user/browse.php?category=plushies">Plushies</a></li>
          <li><a href="<?= BASE_URL ?>/user/custom_order.php">Custom Orders</a></li>
        </ul>
      </div>

      <!-- Account -->
      <div class="footer-col">
        <h4>Account</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
          <li><a href="<?= BASE_URL ?>/auth/register.php">Register</a></li>
          <li><a href="<?= BASE_URL ?>/user/orders.php">My Orders</a></li>
          <li><a href="<?= BASE_URL ?>/seller/dashboard.php">Sell on CrochetCraft</a></li>
        </ul>
      </div>

      <!-- Info -->
      <div class="footer-col">
        <h4>Info</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>/index.php#about">About Us</a></li>
          <li><a href="<?= BASE_URL ?>/index.php#contact">Contact</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Shipping Info</a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      &copy; <?= date('Y') ?> CrochetCraft. All rights reserved. Made with 🧶 in Nepal.
    </div>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

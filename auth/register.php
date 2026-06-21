<?php
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $role     = in_array($_POST['role'] ?? '', ['user','seller']) ? $_POST['role'] : 'user';

    // Basic validation
    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check for duplicate email
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);

        if ($chk->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)'
            );
            $ins->execute([$name, $email, $hash, $role]);

            header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
            exit;
        }
    }
}

$pageTitle = 'Register — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="form-page">
  <div class="form-card" style="max-width:520px;">
    <h1>Create account</h1>
    <p class="subtitle">Join our handmade community today</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>

      <!-- Role picker -->
      <div class="role-picker">
        <label class="role-opt <?= ($_POST['role'] ?? 'user') === 'user' ? 'active' : '' ?>">
          <input type="radio" name="role" value="user"
                 <?= ($_POST['role'] ?? 'user') === 'user' ? 'checked' : '' ?>>
          <div class="role-opt-icon">🛍️</div>
          <div class="role-opt-label">Customer</div>
          <div class="role-opt-sub">Browse &amp; buy</div>
        </label>
        <label class="role-opt <?= ($_POST['role'] ?? '') === 'seller' ? 'active' : '' ?>">
          <input type="radio" name="role" value="seller"
                 <?= ($_POST['role'] ?? '') === 'seller' ? 'checked' : '' ?>>
          <div class="role-opt-icon">🧶</div>
          <div class="role-opt-label">Seller</div>
          <div class="role-opt-sub">List &amp; sell</div>
        </label>
      </div>

      <div class="form-group">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               placeholder="Your name" required autofocus>
      </div>

      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Min 6 characters" required>
        </div>
        <div class="form-group">
          <label for="confirm">Confirm password</label>
          <input type="password" id="confirm" name="confirm"
                 placeholder="Repeat password" required>
        </div>
      </div>

      <button type="submit" class="btn btn-dark btn-full" style="margin-top:4px;">
        Create Account
      </button>
    </form>

    <p class="form-footer">
      Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
    </p>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/db.php';

// Already logged in → redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = currentUser()['role'];
    header('Location: ' . BASE_URL . "/$role/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        // Fetch user by email (prepared statement — no injection risk)
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended. Contact support.';
            } else {
                // Store minimal user info in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                ];

                // Role-based redirect
                $dest = match($user['role']) {
                    'admin'  => BASE_URL . '/admin/dashboard.php',
                    'seller' => BASE_URL . '/seller/dashboard.php',
                    default  => BASE_URL . '/user/dashboard.php',
                };
                header("Location: $dest");
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login — CrochetCraft';
require_once ROOT . '/includes/header.php';
?>

<div class="form-page">
  <div class="form-card">
    <h1>Welcome back</h1>
    <p class="subtitle">Login to your CrochetCraft account</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success" data-auto-dismiss>
        Account created! Please log in.
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-dark btn-full" style="margin-top:8px;">
        Login
      </button>
    </form>

    <p class="form-footer">
      Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Sign up free</a>
    </p>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>

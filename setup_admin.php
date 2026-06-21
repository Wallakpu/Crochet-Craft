<?php
/**
 * One-time admin account setup.
 * Run this ONCE via browser: http://localhost/crochet_craft/setup_admin.php
 * Then DELETE this file immediately for security.
 */
require_once __DIR__ . '/config/db.php';

// Prevent running if admin already exists
$existing = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
if ($existing) {
    echo '<p style="font-family:sans-serif;color:green;">✓ Admin account already exists. You can delete this file.</p>';
    echo '<p><a href="' . BASE_URL . '/auth/login.php">Go to Login</a></p>';
    exit;
}

$name     = 'Admin';
$email    = 'admin@crochetcraft.com';
$password = 'Admin@1234';
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,'admin')"
);
$stmt->execute([$name, $email, $hash]);

echo '<div style="font-family:sans-serif;max-width:400px;margin:60px auto;padding:30px;
      border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);">';
echo '<h2 style="color:#2C1A0F;">✓ Admin account created!</h2>';
echo '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>';
echo '<p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>';
echo '<p style="color:red;font-weight:bold;">⚠️ Delete this file immediately after setup!</p>';
echo '<p><a href="' . BASE_URL . '/auth/login.php" style="color:#C4704F;">Go to Login →</a></p>';
echo '</div>';

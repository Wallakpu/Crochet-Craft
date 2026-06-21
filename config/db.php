<?php
// ── Database & app configuration ──────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'crochet_craft');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL (no trailing slash) — adjust if your folder name differs
define('BASE_URL', '/crochet_craft');

// Absolute server path to the project root
define('ROOT', dirname(__DIR__));

// Where uploaded product images are stored / served from
define('UPLOAD_PATH', ROOT . '/uploads/products/');
define('UPLOAD_URL',  BASE_URL . '/uploads/products/');

// ── PDO connection ─────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Show a friendly error instead of exposing credentials
    http_response_code(503);
    die('<h2 style="font-family:sans-serif;color:#c00">Database unavailable. Please try again later.</h2>');
}

// ── Session helpers ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $back = $redirect ?: BASE_URL . '/auth/login.php';
        header("Location: $back");
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    $user = currentUser();
    if (!$user || $user['role'] !== $role) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Cart count for the nav badge (returns 0 when not a customer)
function cartCount(PDO $pdo): int {
    $user = currentUser();
    if (!$user || $user['role'] !== 'user') return 0;
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    return (int) $stmt->fetchColumn();
}

<?php
require_once __DIR__ . '/../config/db.php';
// Destroy session and redirect to login
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;

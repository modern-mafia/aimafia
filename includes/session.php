<?php
// session.php

// --- Show errors while developing ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Make sure session is started ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Connect to DB (adjust path if needed) ---
require_once __DIR__ . "/db.php";  // assumes db.php is in same /includes/ folder

// --- If logged in, update last_active ---
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_active = ? WHERE id = ?");
        $stmt->execute([time(), $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // If it fails (like missing column), show a readable error instead of 500
        echo "<pre style='color:red;font-weight:bold'>DB Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
        exit;
    }
}

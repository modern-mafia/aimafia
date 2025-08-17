<?php
// includes/db.php

// --- Show DB errors during dev ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database credentials (from InfinityFree panel) ---
$db_host = "sql206.infinityfree.com"; // Hostname
$db_name = "if0_39717176_database";        // Database name (⚠️ replace with your real db name!)
$db_user = "if0_39717176";            // Username
$db_pass = "kAXKTWD1pN";              // Password
$db_port = 3306;

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;port=$db_port;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // ✅ alias so both $pdo and $db can be used
    $db = $pdo;

} catch (PDOException $e) {
    die("<pre style='color:red;font-weight:bold'>DB Connection failed: " . htmlspecialchars($e->getMessage()) . "</pre>");
}

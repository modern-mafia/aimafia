<?php
session_start();
require_once "includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) exit;

// Save spin timestamp
$stmt = $pdo->prepare("UPDATE users SET last_spin = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

// Log to activity feed
$stmt = $pdo->prepare("INSERT INTO activity (user_id, message, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$user_id, "spun the Weekly Wheel of Fortune!"]);

echo "ok";

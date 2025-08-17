<?php
session_start();
require __DIR__ . "/includes/db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$territory_id = isset($_POST['territory_id']) ? (int)$_POST['territory_id'] : 0;
$points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
if ($points <= 0) {
    echo json_encode(['success' => false, 'message' => 'No points scored']);
    exit;
}

// Get user gang
$stmt = $db->prepare("SELECT gang_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || !$user['gang_id']) {
    echo json_encode(['success' => false, 'message' => 'Not in a gang']);
    exit;
}

$gain = max(0, $points); // 1 point = 1% influence

$stmt = $db->prepare("
    INSERT INTO territory_influence (territory_id, gang_id, influence)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE influence = LEAST(100, influence + ?)
");
$stmt->execute([$territory_id, $user['gang_id'], $gain, $gain]);

// Get updated influence
$stmt = $db->prepare("SELECT influence FROM territory_influence WHERE territory_id = ? AND gang_id = ?");
$stmt->execute([$territory_id, $user['gang_id']]);
$influence = (int)$stmt->fetchColumn();

echo json_encode(['success' => true, 'influence' => $influence]);

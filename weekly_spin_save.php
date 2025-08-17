<?php
require_once "includes/db.php";
session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

// Fetch last spin
$stmt = $pdo->prepare("SELECT last_spin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$now = new DateTime();
$can_spin = false;

if ($user && $user['last_spin']) {
    $lastSpin = new DateTime($user['last_spin']);
    $nextSpin = $lastSpin->modify('+7 days');
    $can_spin = ($now >= $nextSpin);
} else {
    $can_spin = true;
}

if (!$can_spin) {
    echo json_encode(["success" => false, "message" => "No spin available yet"]);
    exit;
}

// Example reward logic (can expand later)
$reward = rand(100, 1000);

// Update spin timestamp + add reward
$stmt = $pdo->prepare("UPDATE users SET last_spin = NOW(), cash = cash + ? WHERE id = ?");
$stmt->execute([$reward, $user_id]);

echo json_encode(["success" => true, "message" => "You won $$reward!"]);

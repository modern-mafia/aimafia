<?php
require_once "includes/session.php";
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  exit("Not logged in");
}

$reward = $_POST['reward'] ?? null;
if (!$reward) { exit("No reward"); }

// Save last_spin
$stmt = $pdo->prepare("UPDATE users SET last_spin = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

// TODO: insert into inventory table when that's ready
echo "Saved reward: $reward";

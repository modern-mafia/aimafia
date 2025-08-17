<?php
require_once "includes/session.php";

// Get online users
$stmt = $pdo->query("SELECT username, avatar, last_active FROM users ORDER BY username ASC");
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

function userStatus($lastActive) {
    $now = time();
    $diff = $now - (int)$lastActive;
    if ($diff <= 6600) return "online";
    elseif ($diff <= 9100) return "idle";
    else return "offline";
}
foreach ($allUsers as &$f) {
    $f['status'] = userStatus($f['last_active']);
}

// Get activity feed
$stmt = $pdo->query("
    SELECT a.message, a.created_at, u.username, u.avatar 
    FROM activity a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output JSON
header("Content-Type: application/json");
echo json_encode([
    "users" => $allUsers,
    "activities" => $activities
]);

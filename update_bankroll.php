<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) exit;

$bankroll = (int)$_POST['bankroll'];
$stmt = $db->prepare("UPDATE users SET cash = ? WHERE id = ?");
$stmt->execute([$bankroll, $_SESSION['user_id']]);

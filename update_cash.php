<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

if (!isset($_POST['cash'])) {
    die("No amount given");
}

$newCash = intval($_POST['cash']);

$stmt = $db->prepare("UPDATE users SET cash = ? WHERE id = ?");
$stmt->execute([$newCash, $_SESSION['user_id']]);

echo "OK";

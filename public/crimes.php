<?php
session_start();
require "../includes/db.php";

if (!isset($_SESSION['user_id'])) { die("Please log in."); }

$user_id = $_SESSION['user_id'];

// Fetch user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch a crime
$crime_id = $_GET['id'] ?? 1;
$stmt = $db->prepare("SELECT * FROM crimes WHERE id = ?");
$stmt->execute([$crime_id]);
$crime = $stmt->fetch(PDO::FETCH_ASSOC);

$last_crime_time = strtotime($user['last_crime']);
if (time() - $last_crime_time < $crime['cooldown_seconds']) {
    die("You must wait before committing another crime.");
}

// RNG success
if (rand(1, 100) <= $crime['success_rate']) {
    $earnings = rand($crime['min_cash'], $crime['max_cash']);
    $stmt = $db->prepare("UPDATE users SET cash = cash + ?, notoriety = notoriety + ?, last_crime = NOW() WHERE id = ?");
    $stmt->execute([$earnings, $crime['notoriety_gain'], $user_id]);
    echo "Success! You earned $$earnings.";
} else {
    echo "The crime failed.";
}

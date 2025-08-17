<?php
include 'includes/session.php';
include 'includes/db.php';

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, rank, cash, avatar, last_active FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Online users
$online = $pdo->query("SELECT username, avatar, last_active FROM users ORDER BY last_active DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AIMafia Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(270deg, #1a1a1a, #333, #111);
      background-size: 600% 600%;
      animation: gradient 20s ease infinite;
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
      overflow: hidden;
    }
    @keyframes gradient {
      0% {background-position: 0% 50%;}
      50% {background-position: 100% 50%;}
      100% {background-position: 0% 50%;}
    }
    .top-nav {
      display: flex;
      justify-content: center;
      gap: 2rem;
      padding: 1rem;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(10px);
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
    }
    .top-nav a {
      color: #fff;
      font-size: 1.2rem;
      text-decoration: none;
      transition: color 0.3s;
    }
    .top-nav a:hover {
      color: #0d6efd;
    }
    .sidebar {
      position: fixed;
      top: 70px;
      bottom: 0;
      width: 250px;
      padding: 1rem;
      background: rgba(0,0,0,0.4);
      overflow-y: auto;
    }
    .left { left: 0; }
    .right { right: 0; }
    .center {
      margin: 90px auto;
      max-width: 700px;
      text-align: center;
    }
    .player-card {
      background: rgba(255,255,255,0.05);
      padding: 2rem;
      border-radius: 1rem;
      backdrop-filter: blur(6px);
    }
    .avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 4px solid #0d6efd;
      object-fit: cover;
    }
    .xp-bar {
      height: 10px;
      background: #444;
      border-radius: 5px;
      overflow: hidden;
      margin-top: 1rem;
    }
    .xp-bar-fill {
      width: 40%; /* TODO: calc from db */
      height: 100%;
      background: linear-gradient(to right, #0d6efd, #6610f2);
    }
    .online-list img {
      width: 30px; height: 30px;
      border-radius: 50%;
      margin-right: 0.5rem;
    }
    .activity-feed {
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <!-- Top Nav -->
  <div class="top-nav">
    <a href="inventory.php"><i class="bi bi-box"></i></a>
    <a href="loadout.php"><i class="bi bi-hammer"></i></a>
    <a href="play.php"><i class="bi bi-play-circle"></i></a>
    <a href="store.php"><i class="bi bi-shop"></i></a>
  </div>

  <!-- Left Sidebar -->
  <div class="sidebar left">
    <h5>Missions</h5>
    <ul class="list-unstyled">
      <li><i class="bi bi-flag"></i> Daily Job</li>
      <li><i class="bi bi-trophy"></i> Weekly Goal</li>
    </ul>
    <h5 class="mt-4">Events</h5>
    <p><i class="bi bi-lightning"></i> Gang Wars starting soon!</p>
  </div>

  <!-- Right Sidebar -->
  <div class="sidebar right">
    <h5>Online Users</h5>
    <div class="online-list">
      <?php foreach ($online as $o): ?>
        <div class="d-flex align-items-center mb-2">
          <img src="assets/avatars/<?= htmlspecialchars($o['avatar']) ?>" alt="">
          <span><?= htmlspecialchars($o['username']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <h5 class="mt-4">Activity</h5>
    <div class="activity-feed">
      <p><i class="bi bi-cash"></i> PlayerX won $500 in Blackjack</p>
      <p><i class="bi bi-gem"></i> PlayerY found Epic Loot</p>
    </div>
  </div>

  <!-- Center -->
  <div class="center">
    <div class="player-card">
      <img src="assets/avatars/<?= htmlspecialchars($user['avatar']) ?>" class="avatar" alt="">
      <h3 class="mt-3"><?= htmlspecialchars($user['username']) ?></h3>
      <p>Rank: <span class="text-info"><?= htmlspecialchars($user['rank']) ?></span></p>
      <p>Cash: $<?= number_format($user['cash']) ?></p>
      <div class="xp-bar"><div class="xp-bar-fill"></div></div>
      <button class="btn btn-primary btn-lg mt-3">Start Mission</button>
    </div>
  </div>
</body>
</html>

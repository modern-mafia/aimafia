<?php
session_start();
require_once "includes/db.php";
require_once "includes/session.php";

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, avatar, last_spin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Weekly spin check
$can_spin = false;
if ($user && $user['last_spin']) {
    $lastSpin = new DateTime($user['last_spin']);
    $nextSpin = $lastSpin->modify('+7 days');
    $can_spin = (new DateTime() >= $nextSpin);
} else {
    $can_spin = true; // never spun before
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AIMafia Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body {
      margin: 0;
      background: url('street_bg.png') no-repeat center center fixed;
      background-size: cover;
      position: relative;
      font-family: 'Segoe UI', sans-serif;
    }
    body::after {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(6px);
      z-index: -1;
    }
    .topbar {
      height: 60px;
      background: #111;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
    }
    .topbar .nav-icons i {
      font-size: 1.3rem;
      margin: 0 10px;
      cursor: pointer;
    }
    .sidebar {
      position: fixed;
      top: 60px;
      bottom: 0;
      width: 200px;
      background: #1a1a1a;
      color: #fff;
      padding: 15px;
      overflow-y: auto;
    }
    .sidebar.left { left: 0; }
    .sidebar.right { right: 0; }
    .content {
      margin-top: 80px;
      margin-left: 220px;
      margin-right: 220px;
      color: #fff;
    }
    .avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
    }
    .spin-card {
      background: #222;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 0 12px rgba(0,0,0,0.6);
    }
    .btn-spin {
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="nav-icons">
      <i class="bi bi-house-door"></i>
      <i class="bi bi-coin"></i>
      <i class="bi bi-controller"></i>
      <i class="bi bi-graph-up"></i>
    </div>
    <div class="user-info">
      <img src="<?php echo $user['avatar'] ?: 'default_avatar.png'; ?>" alt="Avatar" class="avatar">
      <span class="ms-2"><?php echo htmlspecialchars($user['username']); ?></span>
    </div>
  </div>

  <div class="sidebar left">
    <h5>Navigation</h5>
    <ul class="nav flex-column">
      <li class="nav-item"><a href="dashboard.php" class="nav-link text-white">Dashboard</a></li>
      <li class="nav-item"><a href="crimes.php" class="nav-link text-white">Crimes</a></li>
      <li class="nav-item"><a href="casino.php" class="nav-link text-white">Casino</a></li>
    </ul>
  </div>

  <div class="sidebar right">
    <h5>Stats</h5>
    <p>Cash: $1000</p>
    <p>Bank: $5000</p>
  </div>

  <div class="content container">
    <div class="row">
      <div class="col-md-6 offset-md-3">
        <div class="spin-card">
          <h4>Weekly Spin</h4>
          <?php if ($can_spin): ?>
            <button id="spin-btn" class="btn btn-success btn-spin">Spin Now 🎰</button>
          <?php else: ?>
            <button class="btn btn-secondary btn-spin" disabled>Come back next week</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $("#spin-btn").on("click", function() {
      $.post("weekly_spin_save.php", { user_id: <?php echo (int)$user_id; ?> }, function(response) {
        alert(response.message);
        location.reload();
      }, "json");
    });
  </script>
</body>
</html>

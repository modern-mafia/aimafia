<?php
require_once "includes/session.php";
require_once "includes/db.php";

// Fetch user
$stmt = $pdo->prepare("SELECT id, username, rank, cash, avatar, last_spin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Spin availability
$can_spin = true;
if ($user['last_spin']) {
    $last = new DateTime($user['last_spin']);
    $now = new DateTime();
    $diff = $last->diff($now)->days;
    if ($diff < 7) $can_spin = false;
}

// Fetch online users
$online_stmt = $pdo->query("SELECT username, avatar, last_active FROM users ORDER BY last_active DESC LIMIT 20");
$online_users = $online_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AIMafia Dashboard</title>
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<!-- Top Nav -->
<div class="top-nav">
  <div class="nav-item">INVENTORY</div>
  <div class="nav-item">LOADOUT</div>
  <div class="nav-item">PLAY</div>
  <div class="nav-item">STORE</div>
</div>

<!-- Left Sidebar -->
<div class="sidebar left">
  <h3>Missions</h3>
  <div class="mission">Next mission in 3 days</div>
  <div class="mission">Special Event soon</div>
</div>

<!-- Right Sidebar -->
<div class="sidebar right">
  <h3>Players Online</h3>
  <div id="online-users">
    <?php foreach ($online_users as $ou): 
      // Status calc
      $last_active = strtotime($ou['last_active']);
      $now = time();
      $diff = $now - $last_active;
      if ($diff < 60) $status = "green";
      elseif ($diff < 300) $status = "amber";
      else $status = "red";
    ?>
      <div class="online-user">
        <img src="assets/avatars/<?php echo $ou['avatar'] ?: 'default_avatar.png'; ?>" alt="user">
        <span class="status <?php echo $status; ?>"></span>
        <span class="name"><?php echo htmlspecialchars($ou['username']); ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <h3>Activity Feed</h3>
  <div id="activity-feed">
    <div class="feed-item">PlayerX joined a gang</div>
    <div class="feed-item">PlayerY won $500 in Blackjack</div>
  </div>
</div>

<!-- Center Panel -->
<div class="center-panel">
  <img class="avatar" src="assets/avatars/<?php echo $user['avatar'] ?: 'default_avatar.png'; ?>" alt="avatar">
  <h2><?php echo htmlspecialchars($user['username']); ?></h2>
  <p>Rank: <?php echo htmlspecialchars($user['rank']); ?> | Cash: $<?php echo $user['cash']; ?></p>
  
  <!-- XP bar -->
  <div class="xp-bar"><div class="fill" style="width:60%"></div></div>

  <!-- Weekly Spin -->
  <div class="weekly-spin">
    <h3>Weekly Spin</h3>
    <?php if ($can_spin): ?>
      <div id="spin-container">
        <div id="spin-strip"></div>
        <div class="marker"></div>
      </div>
      <button id="spin-btn">Spin Now</button>
    <?php else: ?>
      <p>Spin available in a few days</p>
    <?php endif; ?>
  </div>
</div>

<script>
// Populate spin strip
const rewards = [
  {name:"Cash $100", rarity:"common"},
  {name:"Cash $500", rarity:"rare"},
  {name:"Cash $1000", rarity:"epic"},
  {name:"Exclusive Skin", rarity:"legendary"},
  {name:"Gang Influence +10", rarity:"rare"},
  {name:"XP Boost", rarity:"common"}
];
const strip = $("#spin-strip");
for (let i=0;i<30;i++) {
  let r = rewards[Math.floor(Math.random()*rewards.length)];
  strip.append(`<div class="box ${r.rarity}">${r.name}</div>`);
}

$("#spin-btn").click(function(){
  $(this).prop("disabled", true);
  const roll = Math.floor(Math.random()*rewards.length);
  const stopIndex = 20 + roll;
  const offset = -stopIndex*120 + 240; // center prize
  strip.css("transition","transform 4s cubic-bezier(.17,.67,.83,.67)");
  strip.css("transform",`translateX(${offset}px)`);
  setTimeout(()=>{
    const reward = rewards[roll];
    alert("You won: " + reward.name);
    $.post("weekly_spin_save.php",{reward:reward.name},function(res){
      console.log(res);
    });
  },4000);
});
</script>

</body>
</html>

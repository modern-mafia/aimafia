<?php
session_start();
require_once "includes/db.php";

// Simulate logged in user (replace with real session check later)
$user_id = $_SESSION['user_id'] ?? 1;

// Fetch user
$stmt = $pdo->prepare("SELECT id, username, cash, rank, avatar, last_active FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check weekly spin availability
$canSpin = false;
$lastSpin = $_SESSION['last_spin'] ?? null;
if (!$lastSpin || strtotime($lastSpin) < strtotime("-7 days")) {
    $canSpin = true;
}

// Update last_active heartbeat
$stmt = $pdo->prepare("UPDATE users SET last_active = UNIX_TIMESTAMP() WHERE id = ?");
$stmt->execute([$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<style>
/* === General === */
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
    color: white;
}

/* === Nav === */
nav {
    display: flex;
    justify-content: space-around;
    padding: 15px;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    font-weight: bold;
}
nav a {
    color: white;
    text-decoration: none;
    padding: 8px 15px;
    transition: background 0.3s;
}
nav a:hover { background: rgba(255,255,255,0.2); border-radius: 6px; }

/* === Layout === */
#container {
    display: flex;
    padding: 20px;
}
.panel {
    flex: 1;
    padding: 15px;
    background: rgba(255,255,255,0.05);
    margin: 10px;
    border-radius: 12px;
    overflow-y: auto;
    max-height: 80vh;
}
#center-panel {
    flex: 2;
    margin: 10px;
    text-align: center;
}

/* === Player === */
#player-model {
    width: 150px;
    height: 200px;
    margin: 20px auto;
    background: url('assets/avatars/default.png') center/cover no-repeat;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(255,255,255,0.2);
}
#player-info {
    background: rgba(255,255,255,0.08);
    padding: 15px;
    border-radius: 12px;
    margin-top: 15px;
}

/* XP bar */
.xp-bar {
    width: 100%;
    background: rgba(255,255,255,0.1);
    height: 20px;
    border-radius: 10px;
    margin-top: 10px;
}
.xp-fill {
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, gold, orange);
    border-radius: 10px;
}

/* Button */
#cta {
    margin-top: 20px;
    padding: 12px 25px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(90deg, #ff416c, #ff4b2b);
    color: white;
    cursor: pointer;
    transition: transform 0.2s;
}
#cta:hover { transform: scale(1.05); }

/* === Online List === */
.friend {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}
.friend img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-right: 10px;
}
.status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-left: auto;
}
.online { background: limegreen; }
.idle { background: orange; }
.offline { background: red; }

/* Right panel split */
#right-panel { display: flex; flex-direction: column; }
#online-section, #activity-section {
    flex: 1;
    overflow-y: auto;
    margin: 5px 0;
}

/* === Weekly Spin === */
#weekly-spin {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(8px);
    padding: 20px;
    border-radius: 15px;
    margin: 15px auto;
    width: 90%;
    max-width: 700px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.2);
    box-shadow: 0 0 20px rgba(0,0,0,0.4);
}
#spin-wrapper { position: relative; margin: 0 auto 15px auto; }
#spin-container {
    overflow: hidden;
    border: 2px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    background: rgba(20,20,20,0.8);
}
#spin-strip {
    display: flex;
    transition: transform 4s cubic-bezier(0.33,1,0.68,1);
}
.spin-item {
    min-width: 100px;
    padding: 15px;
    margin: 5px;
    text-align: center;
    border-radius: 8px;
    font-weight: bold;
    color: white;
}
.common { background: grey; }
.rare { background: #1e90ff; }
.epic { background: purple; }
.legendary { background: gold; color: black; }
#spin-marker {
    position: absolute; top:0; bottom:0;
    left:50%; width:4px;
    background: gold; box-shadow:0 0 10px gold;
    transform: translateX(-50%);
    z-index:2;
}
@keyframes pulseGlow {
    0%{box-shadow:0 0 5px white}
    50%{box-shadow:0 0 25px gold}
    100%{box-shadow:0 0 5px white}
}
.winner { animation:pulseGlow 1s infinite; transform:scale(1.1); }
#spin-result {
    display:none;
    margin-top:15px;
    padding:15px;
    border-radius:10px;
    background:rgba(0,0,0,0.7);
    border:2px solid gold;
}
#spin-result h4 { margin:0 0 10px 0; }
</style>
</head>
<body>
<nav>
    <a href="#">Inventory</a>
    <a href="#">Loadout</a>
    <a href="#">Play</a>
    <a href="#">Store</a>
</nav>

<div id="container">
    <!-- Left -->
    <div class="panel">
        <h3>Missions</h3>
        <p>Rob a bank within 24h!</p>
        <h3>Events</h3>
        <p>Gang Wars coming soon...</p>
    </div>

    <!-- Center -->
    <div id="center-panel">
        <?php if ($canSpin): ?>
        <div id="weekly-spin">
            <h3>🎁 Weekly Spin</h3>
            <div id="spin-wrapper">
                <div id="spin-marker"></div>
                <div id="spin-container">
                    <div id="spin-strip">
                        <div class="spin-item common">Common</div>
                        <div class="spin-item rare">Rare</div>
                        <div class="spin-item epic">Epic</div>
                        <div class="spin-item common">Common</div>
                        <div class="spin-item rare">Rare</div>
                        <div class="spin-item legendary">Legendary</div>
                        <div class="spin-item common">Common</div>
                        <div class="spin-item epic">Epic</div>
                    </div>
                </div>
            </div>
            <button id="spin-btn">SPIN</button>
            <div id="spin-result">
                <h4 id="result-text"></h4>
                <button onclick="alert('Opening inventory...')">View in Inventory</button>
            </div>
        </div>
        <?php endif; ?>

        <div id="player-model"></div>
        <div id="player-info">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <p>Rank: <?= htmlspecialchars($user['rank']) ?></p>
            <p>Cash: $<?= number_format($user['cash']) ?></p>
            <div class="xp-bar"><div class="xp-fill"></div></div>
        </div>
        <button id="cta">ENTER CASINO</button>
    </div>

    <!-- Right -->
    <div class="panel" id="right-panel">
        <div id="online-section">
            <h3>Online</h3>
            <div id="online-list"></div>
        </div>
        <div id="activity-section">
            <h3>Activity Feed</h3>
            <div id="activity-list"></div>
        </div>
    </div>
</div>

<?php if ($user['username'] === 'admin'): ?>
<form method="post">
    <button name="reset_spin" style="margin-top:10px;">Reset Spin</button>
</form>
<?php
if (isset($_POST['reset_spin'])) {
    $stmt = $pdo->prepare("UPDATE users SET last_spin = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);
    echo "<p style='color:lime;'>✅ Spin reset! Refresh to try again.</p>";
}
?>
<?php endif; ?>

<script>
// === Weekly Spin ===
document.addEventListener("DOMContentLoaded", () => {
    const spinBtn = document.getElementById("spin-btn");
    const spinStrip = document.getElementById("spin-strip");
    const resultBox = document.getElementById("spin-result");
    const resultText = document.getElementById("result-text");

    if (spinBtn) {
        spinBtn.addEventListener("click", () => {
            const items = document.querySelectorAll(".spin-item");
            const stopIndex = Math.floor(Math.random() * items.length);
            const itemWidth = items[0].offsetWidth + 10;
            const container = document.getElementById("spin-container");

            const containerCenter = container.offsetWidth / 2;
            const itemOffset = stopIndex * itemWidth + itemWidth / 2;
            const offset = containerCenter - itemOffset;

            spinStrip.style.transition = "transform 4s cubic-bezier(0.33,1,0.68,1)";
            spinStrip.style.transform = `translateX(${offset}px)`;

            setTimeout(() => {
                items[stopIndex].classList.add("winner");
                setTimeout(() => {
                    spinBtn.style.display = "none";
                    resultText.innerText = "🎉 You won: " + items[stopIndex].innerText;
                    resultBox.style.display = "block";
                    fetch("weekly_spin_save.php");
                }, 2000);
            }, 4200);
        });
    }
});

// === Load Online + Activity via AJAX ===
function timeAgo(dateString) {
    const now = new Date();
    const then = new Date(dateString * 1000);
    const diff = Math.floor((now - then) / 1000);

    if (diff < 60) return "just now";
    if (diff < 3600) return Math.floor(diff/60) + " minutes ago";
    if (diff < 86400) return Math.floor(diff/3600) + " hours ago";
    if (diff < 172800) return "yesterday";
    return Math.floor(diff/86400) + " days ago";
}

async function loadRightPanel() {
    const response = await fetch("dashboard_data.php");
    const data = await response.json();

    let onlineHTML = ``;
    data.users.forEach(f => {
        let avatar = f.avatar || 'https://randomuser.me/api/portraits/lego/2.jpg';
        onlineHTML += `
        <div class="friend">
            <img src="${avatar}">
            <span>${f.username}</span>
            <div class="status ${f.status}"></div>
        </div>`;
    });

    let activityHTML = ``;
    data.activities.forEach(act => {
        let avatar = act.avatar || 'https://randomuser.me/api/portraits/lego/3.jpg';
        activityHTML += `
        <div class="friend" style="align-items:flex-start;">
            <img src="${avatar}">
            <div>
                <b>${act.username}</b><br>
                <small>${act.message}</small><br>
                <small style="color:grey;">${timeAgo(act.created_at)}</small>
            </div>
        </div>`;
    });

    document.getElementById("online-list").innerHTML = onlineHTML;
    document.getElementById("activity-list").innerHTML = activityHTML;
}

loadRightPanel();
setInterval(loadRightPanel, 30000);
</script>
</body>
</html>

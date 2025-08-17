<?php
session_start();
require __DIR__ . "/includes/db.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch user & gang
$stmt = $db->prepare("SELECT u.*, g.id AS gang_id, g.name AS gang_name 
                      FROM users u 
                      LEFT JOIN gangs g ON u.gang_id = g.id
                      WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user['gang_id']) {
    die("You must be in a gang to influence territory!");
}

$territory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM territories WHERE id = ?");
$stmt->execute([$territory_id]);
$territory = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$territory) die("Territory not found.");

// Fetch influence
$stmt = $db->prepare("SELECT ti.*, g.name AS gang_name 
                      FROM territory_influence ti
                      JOIN gangs g ON ti.gang_id = g.id
                      WHERE ti.territory_id = ?");
$stmt->execute([$territory_id]);
$influences = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($territory['name']) ?> - Turf War</title>
<style>
body { background: #111; color: #eee; font-family: Arial; text-align: center; }
.bar { background: #333; width: 300px; height: 20px; margin: 5px auto; position: relative; border-radius: 3px; }
.fill { background: #0f0; height: 100%; border-radius: 3px; transition: width 0.3s; }
button { padding: 10px 20px; background: #0f0; border: none; cursor: pointer; margin-top: 10px; }
button:hover { background: #0c0; }
canvas { display:block; margin:20px auto; border:1px solid #333; background:#222; }
</style>
</head>
<body>
<h1><?= htmlspecialchars($territory['name']) ?></h1>
<p><?= htmlspecialchars($territory['description']) ?></p>

<h2>Influence</h2>
<?php if ($influences): ?>
    <?php foreach ($influences as $inf): ?>
        <p id="gang-<?= $inf['gang_id'] ?>-text"><?= htmlspecialchars($inf['gang_name']) ?>: <?= $inf['influence'] ?>%</p>
        <div class="bar">
            <div class="fill" id="gang-<?= $inf['gang_id'] ?>-bar" style="width:<?= $inf['influence'] ?>%;"></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No influence yet. Be the first to claim this turf!</p>
    <div class="bar"><div class="fill" id="gang-<?= $user['gang_id'] ?>-bar" style="width:0%;"></div></div>
    <p id="gang-<?= $user['gang_id'] ?>-text"><?= htmlspecialchars($user['gang_name']) ?>: 0%</p>
<?php endif; ?>

<canvas id="turfCanvas" width="500" height="300"></canvas>
<button id="startFight">Start Turf Fight</button>

<script>
const canvas = document.getElementById('turfCanvas');
const ctx = canvas.getContext('2d');

let enemies = [];
let score = 0;
let roundActive = false;
let gameTimer;

// Load images (replace with your own PNGs)
const bgImage = new Image();
bgImage.src = 'images/street_bg.png'; // Background street image

const enemyImage = new Image();
enemyImage.src = 'images/enemy_sprite.png'; // Enemy character sprite

// Spawn animated enemy
function spawnEnemy() {
    if (!roundActive) return;
    const size = 40;
    const y = Math.random() * (canvas.height - size - 10);
    const direction = Math.random() < 0.5 ? 1 : -1;
    const x = direction === 1 ? -size : canvas.width + size;
    const speed = 1 + Math.random() * 2;
    enemies.push({x, y, size, direction, speed, hiding: false, flash: 0});
    setTimeout(spawnEnemy, 800 + Math.random()*1000);
}

// Draw everything
function draw() {
    ctx.clearRect(0,0,canvas.width,canvas.height);

    // Background
    ctx.drawImage(bgImage, 0, 0, canvas.width, canvas.height);

    enemies.forEach(e => {
        // Movement
        if (!e.hiding) {
            e.x += e.speed * e.direction;
        }

        // Random hiding
        if (!e.hiding && Math.random() < 0.002) {
            e.hiding = true;
            setTimeout(() => e.hiding = false, 800);
        }

        // Draw enemy or cover
        if (!e.hiding) {
            ctx.drawImage(enemyImage, e.x, e.y, e.size, e.size);
        } else {
            ctx.fillStyle = '#444';
            ctx.fillRect(e.x, e.y + e.size / 2, e.size, e.size / 2);
        }

        // Flash effect
        if (e.flash > 0) {
            ctx.fillStyle = 'rgba(255,255,0,0.5)';
            ctx.beginPath();
            ctx.arc(e.x + e.size / 2, e.y + e.size / 2, e.size, 0, Math.PI * 2);
            ctx.fill();
            e.flash--;
        }
    });

    // Remove off-screen enemies
    enemies = enemies.filter(e => e.x > -50 && e.x < canvas.width + 50);

    requestAnimationFrame(draw);
}

// Shooting logic
canvas.addEventListener('click', e => {
    const rect = canvas.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;

    enemies.forEach(enemy => {
        if (!enemy.hiding &&
            mouseX >= enemy.x && mouseX <= enemy.x + enemy.size &&
            mouseY >= enemy.y && mouseY <= enemy.y + enemy.size) {
            
            const headshot = mouseY <= enemy.y + enemy.size / 3;
            score += headshot ? 2 : 1;
            enemy.flash = 5;
        }
    });

    enemies = enemies.filter(enemy => enemy.flash === 0);
});

// Start fight
document.getElementById('startFight').addEventListener('click', () => {
    enemies = [];
    score = 0;
    roundActive = true;
    spawnEnemy();

    clearTimeout(gameTimer);
    gameTimer = setTimeout(() => {
        roundActive = false;
        // Send score to PHP
        fetch('territory_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'territory_id=<?= $territory_id ?>&points=' + score
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let textEl = document.getElementById('gang-<?= $user['gang_id'] ?>-text');
                let barEl = document.getElementById('gang-<?= $user['gang_id'] ?>-bar');
                textEl.textContent = "<?= htmlspecialchars($user['gang_name']) ?>: " + data.influence + "%";
                barEl.style.width = data.influence + "%";
                alert("Round over! You scored " + score + " points and gained influence!");
            } else {
                alert(data.message);
            }
        });
    }, 15000);
});

draw();
</script>
</body>
</html>

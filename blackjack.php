<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("<h2 style='color:white;text-align:center;'>❌ You must be logged in to play Blackjack.</h2>");
}

try {
    $stmt = $db->prepare("SELECT cash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $bankroll = $stmt->fetchColumn();
    if ($bankroll === false) {
        $bankroll = 1000;
    }
} catch (PDOException $e) {
    die("DB Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Blackjack 2.0</title>
<style>
body {
    background: radial-gradient(circle at center, #023, #000);
    color: white;
    font-family: Arial, sans-serif;
    text-align: center;
    margin: 0;
}
#table {
    margin: 30px auto;
    width: 900px;
    height: 550px;
    border-radius: 50% / 30%;
    position: relative;
    box-shadow: 0 0 60px #0f0;
    background: #145c2d url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==') repeat;
}
.hand {
    display: flex;
    justify-content: center;
    min-height: 140px;
    margin: 10px;
}
.card {
    height: 120px;
    margin: 0 5px;
    opacity: 0;
    transform: translateY(-50px) rotate(var(--tilt, 0deg));
    transition: all 0.5s ease;
    border-radius: 6px;
    box-shadow: 2px 4px 6px rgba(0,0,0,0.5);
}
.card.show { opacity: 1; transform: translateY(0) rotate(var(--tilt, 0deg)); }
.card.face-down {
    background: url('https://deckofcardsapi.com/static/img/back.png');
    background-size: cover;
}
.card.flip { animation: flipCard 0.6s forwards; }
@keyframes flipCard {
    0% { transform: scaleX(1); }
    50% { transform: scaleX(0); }
    100% { transform: scaleX(1); }
}
#bet-circle {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 140px;
    height: 140px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.6);
    box-shadow: 0 0 20px rgba(0,255,0,0.5);
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    transition: box-shadow 0.3s ease;
}
#bet-circle.flash { box-shadow: 0 0 40px rgba(0,255,0,1); }
#chips { margin-top: 20px; }
.chip {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid #fff;
    margin: 0 5px;
    font-weight: bold;
    font-size: 16px;
    background: radial-gradient(circle at 30% 30%, gold, orange);
    color: black;
    cursor: pointer;
    transition: transform 0.2s ease;
}
.chip:hover { transform: scale(1.1); }
@keyframes toss {
    0% { transform: translate(var(--xStart), var(--yStart)) rotate(0deg) scale(1); opacity: 1; }
    50% { transform: translate(calc(var(--xMid)), calc(var(--yMid) - 50px)) rotate(180deg) scale(1.1); }
    100% { transform: translate(0, 0) rotate(360deg) scale(1); opacity: 1; }
}
#controls button {
    padding: 10px 20px;
    margin: 5px;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
#message {
    font-size: 24px;
    margin-top: 20px;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.5s ease;
}
#message.show { opacity: 1; transform: scale(1); }
.win-glow { animation: winPulse 1s ease infinite alternate; }
@keyframes winPulse {
    0% { box-shadow: 0 0 20px gold; }
    100% { box-shadow: 0 0 50px gold; }
}
#lose-img {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 400px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.5s ease;
    z-index: 9999;
}
#lose-img.show {
    opacity: 1;
}
#dealer-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid gold;
    margin-bottom: 5px;
}
</style>
</head>
<body>

<h1 style="color: gold;">♠ Blackjack 2.0 ♣</h1>
<p>Bankroll: $<span id="bankroll"><?= htmlspecialchars($bankroll) ?></span> | Bet: $<span id="bet">0</span></p>

<div id="table">
    <div id="dealer-area" style="position:absolute;top:30px;left:50%;transform:translateX(-50%);">
        <img id="dealer-avatar" src="badgarms.png" alt="Dealer" />
        <h2>Dealer</h2>
        <div id="dealer-hand" class="hand"></div>
    </div>
    <div id="bet-circle"></div>
    <div id="player-area" style="position:absolute;bottom:30px;left:50%;transform:translateX(-50%);">
        <h2>You</h2>
        <div id="player-hand" class="hand"></div>
    </div>
</div>

<!-- Lose image -->
<img id="lose-img" src="jodinoto.png" alt="Lose">

<div id="chips">
    <button class="chip" data-amount="5">$5</button>
    <button class="chip" data-amount="10">$10</button>
    <button class="chip" data-amount="50">$50</button>
    <button class="chip" data-amount="100">$100</button>
</div>

<div id="controls">
    <button id="deal">Deal</button>
    <button id="hit" disabled>Hit</button>
    <button id="stand" disabled>Stand</button>
    <button id="play-again" style="display:none;">Play Again</button>
</div>

<div id="message"></div>

<!-- Sounds -->
<audio id="sound-card" src="https://www.soundjay.com/misc/sounds/card-flip-1.mp3"></audio>
<audio id="sound-chip" src="https://www.soundjay.com/misc/sounds/coin-drop-4.mp3"></audio>
<audio id="sound-chip-remove" src="https://www.soundjay.com/misc/sounds/coin-drop-1.mp3"></audio>
<audio id="sound-win" src="https://www.soundjay.com/misc/sounds/small-bell-ring-01.mp3"></audio>
<audio id="sound-lose" src="https://www.soundjay.com/misc/sounds/fail-trombone-01.mp3"></audio>
<audio id="sound-shuffle" src="https://www.soundjay.com/misc/sounds/card-shuffling-2.mp3"></audio>

<script>
// same JS logic from before with lose image integration
// (You can paste in the blackjack JS from the previous version here)
</script>

</body>
</html>

<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get table
$table_id = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;
$stmt = $db->prepare("SELECT * FROM blackjack_tables WHERE id = ?");
$stmt->execute([$table_id]);
$table = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$table) die("Table not found.");

function hand_value($hand) {
    $value = 0; $aces = 0;
    foreach ($hand as $card) {
        if (in_array($card, ['J','Q','K'])) $value += 10;
        elseif ($card == 'A') { $value += 11; $aces++; }
        else $value += $card;
    }
    while ($value > 21 && $aces > 0) { $value -= 10; $aces--; }
    return $value;
}

function create_shoe($decks = 2) {
    $deck = array_merge(range(2,10), ['J','Q','K','A']);
    $shoe = [];
    for ($i = 0; $i < $decks * 4; $i++) {
        $shoe = array_merge($shoe, $deck);
    }
    shuffle($shoe);
    return $shoe;
}

$shoe = $table['shoe'] ? json_decode($table['shoe'], true) : [];
if (!$shoe) {
    $shoe = create_shoe(2);
    $db->prepare("UPDATE blackjack_tables SET shoe=? WHERE id=?")->execute([json_encode($shoe), $table_id]);
}

$stmt = $db->prepare("SELECT * FROM blackjack_players WHERE table_id = ? ORDER BY id ASC");
$stmt->execute([$table_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Play again
if (isset($_POST['play_again']) && $table['state'] == 'finished') {
    if (count($shoe) < 25) $shoe = create_shoe(2);

    foreach ($players as $p) {
        $db->prepare("UPDATE blackjack_players SET hand=?, finished=0 WHERE id=?")->execute([json_encode([]), $p['id']]);
        $db->prepare("UPDATE users SET cash = cash - ? WHERE id=?")->execute([$table['bet'], $p['user_id']]);
        $db->prepare("UPDATE casinos SET balance = balance + ? WHERE id=1")->execute([$table['bet']]);
    }

    $dealer_hand = [array_pop($shoe), array_pop($shoe)];
    foreach ($players as $p) {
        $hand = [array_pop($shoe), array_pop($shoe)];
        $db->prepare("UPDATE blackjack_players SET hand=? WHERE id=?")->execute([json_encode($hand), $p['id']]);
    }

    $db->prepare("UPDATE blackjack_tables SET dealer_hand=?, shoe=?, state='playing', turn_index=0, delete_after=NULL WHERE id=?")
        ->execute([json_encode($dealer_hand), json_encode($shoe), $table_id]);
}

if ($table['state'] == 'waiting' && count($players) >= 2) {
    $dealer_hand = [array_pop($shoe), array_pop($shoe)];
    foreach ($players as $p) {
        $hand = [array_pop($shoe), array_pop($shoe)];
        $db->prepare("UPDATE blackjack_players SET hand=? WHERE id=?")->execute([json_encode($hand), $p['id']]);
        $db->prepare("UPDATE users SET cash = cash - ? WHERE id=?")->execute([$table['bet'], $p['user_id']]);
        $db->prepare("UPDATE casinos SET balance = balance + ? WHERE id=1")->execute([$table['bet']]);
    }
    $db->prepare("UPDATE blackjack_tables SET state='playing', dealer_hand=?, shoe=?, turn_index=0 WHERE id=?")
        ->execute([json_encode($dealer_hand), json_encode($shoe), $table_id]);
}

$stmt = $db->prepare("SELECT * FROM blackjack_tables WHERE id = ?");
$stmt->execute([$table_id]);
$table = $stmt->fetch(PDO::FETCH_ASSOC);
$shoe = json_decode($table['shoe'], true);
$dealer_hand = json_decode($table['dealer_hand'], true);

$stmt = $db->prepare("SELECT * FROM blackjack_players WHERE table_id = ? ORDER BY id ASC");
$stmt->execute([$table_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_index = $table['turn_index'];
$current_player = isset($players[$current_index]) ? $players[$current_index] : null;

if ($table['state'] == 'playing' && $current_player && $current_player['user_id'] == $user_id) {
    if (isset($_POST['hit'])) {
        $hand = json_decode($current_player['hand'], true);
        $hand[] = array_pop($shoe);
        $db->prepare("UPDATE blackjack_players SET hand=? WHERE id=?")->execute([json_encode($hand), $current_player['id']]);
        $db->prepare("UPDATE blackjack_tables SET shoe=? WHERE id=?")->execute([json_encode($shoe), $table_id]);
        if (hand_value($hand) > 21) {
            $db->prepare("UPDATE blackjack_players SET finished=1 WHERE id=?")->execute([$current_player['id']]);
            $db->prepare("UPDATE blackjack_tables SET turn_index=turn_index+1 WHERE id=?")->execute([$table_id]);
        }
    }
    if (isset($_POST['stand'])) {
        $db->prepare("UPDATE blackjack_players SET finished=1 WHERE id=?")->execute([$current_player['id']]);
        $db->prepare("UPDATE blackjack_tables SET turn_index=turn_index+1 WHERE id=?")->execute([$table_id]);
    }
}

$all_done = true;
foreach ($players as $p) {
    if (!$p['finished'] && hand_value(json_decode($p['hand'], true)) <= 21) {
        $all_done = false;
        break;
    }
}

if ($table['state'] == 'playing' && $all_done) {
    while (hand_value($dealer_hand) < 17) {
        $dealer_hand[] = array_pop($shoe);
    }
    $db->prepare("UPDATE blackjack_tables SET dealer_hand=?, shoe=?, state='finished' WHERE id=?")
       ->execute([json_encode($dealer_hand), json_encode($shoe), $table_id]);

    foreach ($players as $p) {
        $player_total = hand_value(json_decode($p['hand'], true));
        $dealer_total = hand_value($dealer_hand);
        $bet = $table['bet'];
        $result = 'lose'; $win_amount = 0;

        if ($player_total <= 21 && ($dealer_total > 21 || $player_total > $dealer_total)) {
            $win_amount = $bet * 2;
            $db->prepare("UPDATE users SET cash = cash + ? WHERE id=?")->execute([$win_amount, $p['user_id']]);
            $db->prepare("UPDATE casinos SET balance = balance - ? WHERE id=1")->execute([$bet]);
            $result = 'win';
        } elseif ($player_total == $dealer_total) {
            $db->prepare("UPDATE users SET cash = cash + ? WHERE id=?")->execute([$bet, $p['user_id']]);
            $result = 'push';
        }

        $db->prepare("INSERT INTO casino_logs (user_id, game_type, bet, result, amount_won) VALUES (?, 'blackjack', ?, ?, ?)")
           ->execute([$p['user_id'], $bet, $result, $win_amount]);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Blackjack Table #<?= $table_id ?></title>
<meta http-equiv="refresh" content="3">
<style>
body { background: #111; color: #eee; font-family: Arial; text-align: center; }
button { padding: 8px; background: #444; color: #fff; border: none; cursor: pointer; margin: 5px; }
button:hover { background: #666; }
</style>
</head>
<body>
<h1>Blackjack Table #<?= $table_id ?></h1>
<p><a href="blackjack_lobby.php">⬅ Back to Lobby</a></p>

<!-- Hidden YouTube player -->
<div id="ytplayer" style="width:0;height:0;overflow:hidden;"></div>
<script src="https://www.youtube.com/iframe_api"></script>
<script>
var player;
function onYouTubeIframeAPIReady() {
    player = new YT.Player('ytplayer', {
        height: '0',
        width: '0',
        videoId: 'cgQF164vg4o',
        playerVars: { 'autoplay': 0, 'controls': 0 }
    });
}
function playYouTubeSound() {
    if (player) {
        player.seekTo(0);
        player.playVideo();
        setTimeout(function() { player.stopVideo(); }, 10000);
    }
}
function checkAndPlaySound(total) {
    if (total === 21) {
        playYouTubeSound();
    }
}
</script>

<?php if ($table['state'] == 'waiting'): ?>
    <p>Waiting for more players... (Need at least 2)</p>

<?php elseif ($table['state'] == 'playing'): ?>
    <p>Dealer's Hand: <?= $dealer_hand[0] ?> <?php if (count($dealer_hand) > 1): ?> [hidden] <?php endif; ?></p>
    <?php foreach ($players as $p): ?>
        <p><?= ($p['user_id'] == $user_id ? "<strong>Your</strong>" : "Player {$p['user_id']}'s") ?>
           Hand: <?= implode(" ", json_decode($p['hand'], true)) ?>
           (<?= hand_value(json_decode($p['hand'], true)) ?>)
           <?= $p['finished'] ? "(Finished)" : "" ?>
        </p>
    <?php endforeach; ?>
    <?php if ($current_player && $current_player['user_id'] == $user_id && !$current_player['finished']): ?>
        <?php $my_total = hand_value(json_decode($current_player['hand'], true)); ?>
        <form method="post" onsubmit="if(event.submitter.name==='stand'){checkAndPlaySound(<?= $my_total ?>);}">
            <button type="submit" name="hit">Hit</button>
            <button type="submit" name="stand">Stand</button>
        </form>
    <?php else: ?>
        <p>Waiting for <?= $current_player ? "Player {$current_player['user_id']}" : "dealer" ?>...</p>
    <?php endif; ?>

<?php elseif ($table['state'] == 'finished'): ?>
    <h2>Results</h2>
    <p>Dealer's Hand: <?= implode(" ", $dealer_hand) ?> (<?= hand_value($dealer_hand) ?>)</p>
    <?php foreach ($players as $p): ?>
        <p><?= ($p['user_id'] == $user_id ? "<strong>Your</strong>" : "Player {$p['user_id']}'s") ?>
           Hand: <?= implode(" ", json_decode($p['hand'], true)) ?>
           (<?= hand_value(json_decode($p['hand'], true)) ?>)
        </p>
    <?php endforeach; ?>
    <form method="post">
        <button type="submit" name="play_again">Play Again</button>
    </form>
<?php endif; ?>
</body>
</html>

<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Create table
if (isset($_POST['create_table'])) {
    $bet = abs((int)$_POST['bet']);
    if ($bet >= 1) {
        $stmt = $db->prepare("INSERT INTO blackjack_tables (bet) VALUES (?)");
        $stmt->execute([$bet]);
        $table_id = $db->lastInsertId();

        // Add creator as first player
        $stmt = $db->prepare("INSERT INTO blackjack_players (table_id, user_id, hand) VALUES (?, ?, ?)");
        $stmt->execute([$table_id, $user_id, json_encode([])]);

        header("Location: blackjack_play.php?table_id=" . $table_id);
        exit;
    }
}

// Join table
if (isset($_POST['join_table'])) {
    $table_id = (int)$_POST['table_id'];
    $stmt = $db->prepare("SELECT * FROM blackjack_players WHERE table_id = ? AND user_id = ?");
    $stmt->execute([$table_id, $user_id]);
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO blackjack_players (table_id, user_id, hand) VALUES (?, ?, ?)");
        $stmt->execute([$table_id, $user_id, json_encode([])]);
    }
    header("Location: blackjack_play.php?table_id=" . $table_id);
    exit;
}

// Get tables
$stmt = $db->query("SELECT t.*, COUNT(p.id) AS player_count 
                    FROM blackjack_tables t
                    LEFT JOIN blackjack_players p ON t.id = p.table_id
                    WHERE t.state != 'finished'
                    GROUP BY t.id");
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Blackjack Lobby</title>
<meta http-equiv="refresh" content="5">
<style>
body { background: #111; color: #eee; font-family: Arial; text-align: center; }
table { margin: auto; background: #222; border-collapse: collapse; }
th, td { padding: 10px; border: 1px solid #333; }
button { padding: 8px; background: #444; color: #fff; border: none; cursor: pointer; }
button:hover { background: #666; }
</style>
</head>
<body>
<h1>Blackjack Lobby</h1>
<p><a href="dashboard.php">⬅ Back to Dashboard</a></p>

<h2>Create Table</h2>
<form method="post">
    <input type="number" name="bet" placeholder="Bet Amount" min="1" required>
    <button type="submit" name="create_table">Create</button>
</form>

<h2>Available Tables</h2>
<table>
<tr>
    <th>Table ID</th>
    <th>Bet</th>
    <th>Players</th>
    <th>Status</th>
    <th>Action</th>
</tr>
<?php foreach ($tables as $t): ?>
<tr>
    <td><?= $t['id'] ?></td>
    <td>$<?= number_format($t['bet']) ?></td>
    <td><?= $t['player_count'] ?></td>
    <td><?= ucfirst($t['state']) ?></td>
    <td>
        <?php if ($t['state'] == 'waiting'): ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="table_id" value="<?= $t['id'] ?>">
                <button type="submit" name="join_table">Join</button>
            </form>
        <?php else: ?>
            In Progress
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>

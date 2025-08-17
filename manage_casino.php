<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get casino info
$stmt = $db->prepare("SELECT * FROM casinos WHERE id = 1 LIMIT 1");
$stmt->execute();
$casino = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$casino) {
    die("Casino not found.");
}

// Only owner can manage
if ($casino['owner_id'] != $user_id) {
    die("❌ You are not the owner of this casino.");
}

$message = "";

// Withdraw profits
if (isset($_POST['withdraw'])) {
    $amount = abs((int)$_POST['amount']);
    if ($amount > 0 && $amount <= $casino['balance']) {
        $stmt = $db->prepare("UPDATE users SET cash = cash + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);

        $stmt = $db->prepare("UPDATE casinos SET balance = balance - ? WHERE id = 1");
        $stmt->execute([$amount]);

        $message = "✅ Withdrawn $" . number_format($amount) . " from casino.";
    } else {
        $message = "❌ Invalid amount.";
    }
}

// Change house cut
if (isset($_POST['house_cut'])) {
    $cut = (int)$_POST['cut'];
    if ($cut >= 0 && $cut <= 100) {
        $stmt = $db->prepare("UPDATE casinos SET house_cut = ? WHERE id = 1");
        $stmt->execute([$cut]);
        $message = "✅ House cut set to {$cut}%.";
    } else {
        $message = "❌ Invalid percentage.";
    }
}

// Transfer ownership
if (isset($_POST['transfer'])) {
    $new_owner = (int)$_POST['new_owner_id'];
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$new_owner]);
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE casinos SET owner_id = ? WHERE id = 1");
        $stmt->execute([$new_owner]);
        $message = "✅ Casino ownership transferred to Player ID {$new_owner}.";
    } else {
        $message = "❌ Player not found.";
    }
}

// Refresh casino data
$stmt = $db->prepare("SELECT * FROM casinos WHERE id = 1 LIMIT 1");
$stmt->execute();
$casino = $stmt->fetch(PDO::FETCH_ASSOC);

// Get last 10 spins
$stmt = $db->prepare("
    SELECT casino_logs.*, users.username
    FROM casino_logs
    JOIN users ON users.id = casino_logs.user_id
    ORDER BY casino_logs.created_at DESC
    LIMIT 10
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Casino</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; text-align: center; }
        table { margin: auto; background: #222; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #333; }
        form { margin: 15px auto; background: #222; padding: 15px; width: 300px; border-radius: 8px; }
        input { padding: 5px; margin: 5px; }
        button { padding: 8px; background: #444; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #666; }
    </style>
</head>
<body>
    <h1>Manage Casino</h1>
    <p><a href="casino.php">⬅ Back to Casino</a></p>

    <?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

    <p><strong>Balance:</strong> $<?php echo number_format($casino['balance']); ?></p>
    <p><strong>House Cut:</strong> <?php echo $casino['house_cut']; ?>%</p>

    <form method="post">
        <h3>Withdraw Profits</h3>
        <input type="number" name="amount" placeholder="Amount" min="1" required>
        <button type="submit" name="withdraw">Withdraw</button>
    </form>

    <form method="post">
        <h3>Change House Cut (%)</h3>
        <input type="number" name="cut" min="0" max="100" value="<?php echo $casino['house_cut']; ?>" required>
        <button type="submit" name="house_cut">Update</button>
    </form>

    <form method="post">
        <h3>Transfer Ownership</h3>
        <input type="number" name="new_owner_id" placeholder="Player ID" required>
        <button type="submit" name="transfer">Transfer</button>
    </form>

    <h2>Recent Spins</h2>
    <table>
        <tr>
            <th>Player</th>
            <th>Game</th>
            <th>Bet</th>
            <th>Result</th>
            <th>Amount Won</th>
            <th>Time</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?php echo htmlspecialchars($log['username']); ?></td>
            <td><?php echo ucfirst($log['game_type']); ?></td>
            <td>$<?php echo number_format($log['bet']); ?></td>
            <td><?php echo ucfirst($log['result']); ?></td>
            <td>$<?php echo number_format($log['amount_won']); ?></td>
            <td><?php echo $log['created_at']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

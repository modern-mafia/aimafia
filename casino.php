<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Init session tracker
if (!isset($_SESSION['casino_net'])) {
    $_SESSION['casino_net'] = 0;
}

// Get player data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get casino data
$stmt = $db->prepare("SELECT * FROM casinos WHERE id = 1 LIMIT 1");
$stmt->execute();
$casino = $stmt->fetch(PDO::FETCH_ASSOC);

$message = "";
$result_symbols = [];

if (isset($_POST['bet'])) {
    $bet = abs((int)$_POST['bet']);

    if ($bet < 1) {
        $message = "❌ Bet must be at least $1.";
    } elseif ($bet > $user['cash']) {
        $message = "❌ You don't have enough cash.";
    } else {
        // Spin
        $symbols = ["🍒", "🍋", "🍇", "⭐", "💎"];
        $result_symbols = [
            $symbols[array_rand($symbols)],
            $symbols[array_rand($symbols)],
            $symbols[array_rand($symbols)]
        ];

        $win = 0;
        $result = 'lose';

        // Jackpot
        if ($result_symbols[0] === $result_symbols[1] && $result_symbols[1] === $result_symbols[2]) {
            $win = $bet * 3;
            $result = 'win';
            $message = "🎉 Jackpot! You won $" . number_format($win) . "!";
        }
        // Two match
        elseif ($result_symbols[0] === $result_symbols[1] || $result_symbols[1] === $result_symbols[2] || $result_symbols[0] === $result_symbols[2]) {
            $win = floor($bet * 1.5);
            $result = 'win';
            $message = "✨ Two match! You won $" . number_format($win) . "!";
        } else {
            $message = "💀 No match, you lost your bet.";
        }

        if ($result === 'win') {
            // Give player winnings
            $stmt = $db->prepare("UPDATE users SET cash = cash + ? WHERE id = ?");
            $stmt->execute([$win, $user_id]);

            // Casino loses money
            $stmt = $db->prepare("UPDATE casinos SET balance = balance - ? WHERE id = 1");
            $stmt->execute([$win]);

            // Track net profit
            $_SESSION['casino_net'] += $win;
        } else {
            // Player loses bet
            $stmt = $db->prepare("UPDATE users SET cash = cash - ? WHERE id = ?");
            $stmt->execute([$bet, $user_id]);

            // Casino gains bet
            $stmt = $db->prepare("UPDATE casinos SET balance = balance + ? WHERE id = 1");
            $stmt->execute([$bet]);

            // Pay owner cut
            if (!empty($casino['owner_id']) && $casino['house_cut'] > 0) {
                $owner_cut = floor($bet * ($casino['house_cut'] / 100));
                if ($owner_cut > 0) {
                    $stmt = $db->prepare("UPDATE users SET cash = cash + ? WHERE id = ?");
                    $stmt->execute([$owner_cut, $casino['owner_id']]);
                }
            }

            // Track net loss
            $_SESSION['casino_net'] -= $bet;
        }

        // Log spin
        $stmt = $db->prepare("INSERT INTO casino_logs (user_id, game_type, bet, result, amount_won) VALUES (?, 'slots', ?, ?, ?)");
        $stmt->execute([$user_id, $bet, $result, $win]);

        // Refresh casino data
        $stmt = $db->prepare("SELECT * FROM casinos WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $casino = $stmt->fetch(PDO::FETCH_ASSOC);

        // Takeover mechanic - casino bankrupt
        if ($casino['balance'] <= 0 && $result === 'win') {
            $stmt = $db->prepare("UPDATE casinos SET owner_id = ?, balance = 100000 WHERE id = 1");
            $stmt->execute([$user_id]);
            $message .= " 🏆 The casino is bankrupt! You are the new owner!";
            
            // Refresh again
            $stmt = $db->prepare("SELECT * FROM casinos WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $casino = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Refresh player data
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Casino - Slots</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; text-align: center; }
        .slot { font-size: 50px; margin: 20px; }
        form { background: #222; padding: 20px; margin: auto; width: 300px; border-radius: 8px; }
        input { padding: 8px; width: 80%; margin: 5px 0; }
        button { padding: 10px; background: #444; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #666; }
        .profit { color: #4CAF50; }
        .loss { color: #E53935; }
    </style>
</head>
<body>
    <h1>Casino - Slot Machine</h1>
    <p><a href="dashboard.php">⬅ Back to Dashboard</a></p>

    <p><strong>Your Cash:</strong> $<?php echo number_format($user['cash']); ?></p>
    <p><strong>Casino Balance:</strong> $<?php echo number_format($casino['balance']); ?></p>
    <?php if (!empty($casino['owner_id'])): ?>
        <p><strong>Owner:</strong> Player ID <?php echo $casino['owner_id']; ?> (House Cut: <?php echo $casino['house_cut']; ?>%)</p>
        <?php if ($user_id == $casino['owner_id']): ?>
            <p><a href="manage_casino.php">💼 Manage Casino</a></p>
        <?php endif; ?>
    <?php endif; ?>

    <p>
        <strong>Session Net:</strong>
        <span class="<?php echo $_SESSION['casino_net'] >= 0 ? 'profit' : 'loss'; ?>">
            <?php echo ($_SESSION['casino_net'] >= 0 ? '+' : '') . number_format($_SESSION['casino_net']); ?>
        </span>
    </p>

    <?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

    <?php if (!empty($result_symbols)): ?>
        <div class="slot">
            <?php echo $result_symbols[0] . " " . $result_symbols[1] . " " . $result_symbols[2]; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="number" name="bet" min="1" max="<?php echo $user['cash']; ?>" placeholder="Enter bet" required><br>
        <button type="submit">Spin 🎰</button>
    </form>
</body>
</html>

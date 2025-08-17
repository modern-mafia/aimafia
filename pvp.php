<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$attack_cooldown = 60; // seconds between attacks

// Fetch logged-in user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get targets (all other players)
$stmt = $db->prepare("SELECT id, username, cash, notoriety FROM users WHERE id != ? ORDER BY notoriety DESC");
$stmt->execute([$user_id]);
$targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";

// Handle attack
if (isset($_GET['attack'])) {
    $target_id = (int)$_GET['attack'];

    // Always check cooldown fresh from DB
    $stmt = $db->prepare("SELECT last_attack FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $last_attack_time = (int)$stmt->fetchColumn();

    $time_since_last = time() - $last_attack_time;
    if ($time_since_last < $attack_cooldown) {
        $remaining = $attack_cooldown - $time_since_last;
        $message = "⏳ You must wait {$remaining} seconds before attacking again.";
    } else {
        // Set cooldown immediately to prevent spam
        $stmt = $db->prepare("UPDATE users SET last_attack = ? WHERE id = ?");
        $stmt->execute([time(), $user_id]);

        // Fetch target
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            $message = "❌ Player not found.";
        } elseif ($target['cash'] <= 0) {
            $message = "💰 This player has no cash to steal.";
        } else {
            // Win chance calculation
            $chance = rand(1, 100);
            $success_chance = 50 + ($user['strength'] - $target['strength']) * 5;
            $success_chance = max(10, min(90, $success_chance)); // clamp 10%–90%

            if ($chance <= $success_chance) {
                // Win - steal 25% of target's cash
                $stolen = floor($target['cash'] * 0.25);
                if ($stolen < 1) $stolen = 1;

                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE users SET cash = cash + ? WHERE id = ?");
                $stmt->execute([$stolen, $user_id]);
                $stmt = $db->prepare("UPDATE users SET cash = cash - ? WHERE id = ?");
                $stmt->execute([$stolen, $target_id]);
                $stmt = $db->prepare("INSERT INTO pvp_logs (attacker_id, defender_id, result, amount) VALUES (?, ?, 'win', ?)");
                $stmt->execute([$user_id, $target_id, $stolen]);
                $db->commit();

                $message = "✅ You attacked {$target['username']} and stole $" . number_format($stolen) . "!";
            } else {
                // Lose - no money stolen
                $stmt = $db->prepare("INSERT INTO pvp_logs (attacker_id, defender_id, result, amount) VALUES (?, ?, 'lose', 0)");
                $stmt->execute([$user_id, $target_id]);
                $message = "❌ You failed to defeat {$target['username']}.";
            }
        }
    }

    // Refresh target list after attack
    $stmt = $db->prepare("SELECT id, username, cash, notoriety FROM users WHERE id != ? ORDER BY notoriety DESC");
    $stmt->execute([$user_id]);
    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch latest 10 PvP logs
$stmt = $db->prepare("
    SELECT pvp_logs.*, ua.username AS attacker_name, ud.username AS defender_name
    FROM pvp_logs
    JOIN users ua ON ua.id = pvp_logs.attacker_id
    JOIN users ud ON ud.id = pvp_logs.defender_id
    ORDER BY pvp_logs.created_at DESC
    LIMIT 10
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mafia Game - PvP</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; text-align: center; }
        table { margin: auto; background: #222; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #333; }
        a { color: red; text-decoration: none; }
        .message { margin: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Player vs Player</h1>
    <p><a href="dashboard.php">⬅ Back to Dashboard</a></p>

    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

    <h2>Targets</h2>
    <table>
        <tr>
            <th>Username</th>
            <th>Cash</th>
            <th>Notoriety</th>
            <th>Action</th>
        </tr>
        <?php foreach ($targets as $t): ?>
        <tr>
            <td><?php echo htmlspecialchars($t['username']); ?></td>
            <td>$<?php echo number_format($t['cash']); ?></td>
            <td><?php echo $t['notoriety']; ?></td>
            <td><a href="?attack=<?php echo $t['id']; ?>">Attack</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Recent Battles</h2>
    <table>
        <tr>
            <th>Attacker</th>
            <th>Defender</th>
            <th>Result</th>
            <th>Amount Stolen</th>
            <th>Time</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?php echo htmlspecialchars($log['attacker_name']); ?></td>
            <td><?php echo htmlspecialchars($log['defender_name']); ?></td>
            <td><?php echo ucfirst($log['result']); ?></td>
            <td>$<?php echo number_format($log['amount']); ?></td>
            <td><?php echo $log['created_at']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

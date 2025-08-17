<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get logged-in user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all available crimes
$crimes = $db->query("SELECT * FROM crimes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle committing a crime
if (isset($_GET['do'])) {
    $crime_id = (int)$_GET['do'];

    // Get crime details
    $stmt = $db->prepare("SELECT * FROM crimes WHERE id = ?");
    $stmt->execute([$crime_id]);
    $crime = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$crime) {
        $message = "Invalid crime.";
    } else {
        // Cooldown check
        $lastCrimeTime = strtotime($user['last_crime']);
        if (time() - $lastCrimeTime < $crime['cooldown_seconds']) {
            $remaining = $crime['cooldown_seconds'] - (time() - $lastCrimeTime);
            $message = "You must wait {$remaining} seconds before committing another crime.";
        } else {
            // Success or fail
            if (rand(1, 100) <= $crime['success_rate']) {
                $earnings = rand($crime['min_cash'], $crime['max_cash']);
                $stmt = $db->prepare("UPDATE users SET cash = cash + ?, notoriety = notoriety + ?, last_crime = NOW() WHERE id = ?");
                $stmt->execute([$earnings, $crime['notoriety_gain'], $_SESSION['user_id']]);
                $message = "✅ Success! You earned $" . number_format($earnings) . " and gained {$crime['notoriety_gain']} notoriety.";
            } else {
                $stmt = $db->prepare("UPDATE users SET last_crime = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $message = "❌ The crime failed. Better luck next time.";
            }
        }
    }

    // Refresh user data after update
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mafia Game - Crimes</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; text-align: center; }
        table { margin: auto; background: #222; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #333; }
        a { color: lime; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Commit a Crime</h1>
    <p><a href="dashboard.php">⬅ Back to Dashboard</a></p>

    <?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

    <table>
        <tr>
            <th>Crime</th>
            <th>Payout Range</th>
            <th>Success Rate</th>
            <th>Cooldown</th>
            <th>Action</th>
        </tr>
        <?php foreach ($crimes as $crime): ?>
        <tr>
            <td><?php echo htmlspecialchars($crime['name']); ?></td>
            <td>$<?php echo number_format($crime['min_cash']); ?> - $<?php echo number_format($crime['max_cash']); ?></td>
            <td><?php echo $crime['success_rate']; ?>%</td>
            <td><?php echo $crime['cooldown_seconds']; ?> sec</td>
            <td><a href="?do=<?php echo $crime['id']; ?>">Commit</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p><strong>Your Cash:</strong> $<?php echo number_format($user['cash']); ?> | 
       <strong>Notoriety:</strong> <?php echo $user['notoriety']; ?></p>
</body>
</html>

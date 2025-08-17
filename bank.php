<?php
session_start();
require __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle deposit
if (isset($_POST['deposit'])) {
    $amount = abs((int)$_POST['amount']);
    if ($amount > 0 && $amount <= $user['cash']) {
        $stmt = $db->prepare("UPDATE users SET cash = cash - ?, bank = bank + ? WHERE id = ?");
        $stmt->execute([$amount, $amount, $_SESSION['user_id']]);
        $message = "✅ Deposited $" . number_format($amount) . " into your bank.";
    } else {
        $message = "❌ Invalid deposit amount.";
    }
}

// Handle withdraw
if (isset($_POST['withdraw'])) {
    $amount = abs((int)$_POST['amount']);
    if ($amount > 0 && $amount <= $user['bank']) {
        $stmt = $db->prepare("UPDATE users SET cash = cash + ?, bank = bank - ? WHERE id = ?");
        $stmt->execute([$amount, $amount, $_SESSION['user_id']]);
        $message = "✅ Withdrew $" . number_format($amount) . " from your bank.";
    } else {
        $message = "❌ Invalid withdrawal amount.";
    }
}

// Refresh user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mafia Game - Bank</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; text-align: center; }
        form { background: #222; padding: 20px; margin: 20px auto; width: 300px; border-radius: 8px; }
        input { padding: 8px; width: 80%; margin: 5px 0; }
        button { padding: 10px; background: #444; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #666; }
        .message { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Your Bank</h1>
    <p><a href="dashboard.php">⬅ Back to Dashboard</a></p>

    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

    <p><strong>Cash on hand:</strong> $<?php echo number_format($user['cash']); ?></p>
    <p><strong>Bank balance:</strong> $<?php echo number_format($user['bank']); ?></p>

    <form method="post">
        <h3>Deposit Money</h3>
        <input type="number" name="amount" min="1" required><br>
        <button type="submit" name="deposit">Deposit</button>
    </form>

    <form method="post">
        <h3>Withdraw Money</h3>
        <input type="number" name="amount" min="1" required><br>
        <button type="submit" name="withdraw">Withdraw</button>
    </form>
</body>
</html>

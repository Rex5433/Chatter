<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_GET['user_id']; // friend

// Handle message send
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $msg);
        $stmt->execute();
    }
}

// Fetch conversation
$stmt = $conn->prepare("
    SELECT messages.*, users.username FROM messages
    JOIN users ON messages.sender_id = users.id
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY sent_at ASC
");
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Chat</h2>
    <div>
        <?php while ($msg = $messages->fetch_assoc()): ?>
            <p><strong><?php echo htmlspecialchars($msg['username']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?></p>
        <?php endwhile; ?>
    </div>

    <form method="POST">
        <input type="text" name="message" required>
        <input type="submit" value="Send">
    </form>
</body>
</html>

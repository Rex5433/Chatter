<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "
    SELECT users.username 
    FROM users
    WHERE users.id IN (
        SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
        UNION
        SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
    )
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$friends = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Friends</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Your Friends</h2>
    <?php while ($friend = $friends->fetch_assoc()): ?>
        <p><?php echo htmlspecialchars($friend['username']); ?></p>
    <?php endwhile; ?>
</body>
</html>

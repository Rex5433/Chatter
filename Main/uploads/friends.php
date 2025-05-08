<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$user_id = getUserId($username, $conn);

function getUserId($username, $conn) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_username'])) {
    $friend_username = trim($_POST['add_username']);
    if ($friend_username !== $username) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $friend_username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user_row = $result->fetch_assoc()) {
            $friend_id = $user_row['id'];
            $stmt = $conn->prepare("SELECT * FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
            $stmt->execute();
            $existing = $stmt->get_result();
            if ($existing->num_rows === 0) {
                $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
                $stmt->bind_param("ii", $user_id, $friend_id);
                $stmt->execute();
            }
        }
    }
    header("Location: friends.php");
    exit();
}

if (isset($_GET['accept'])) {
    $request_id = (int)$_GET['accept'];
    $stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND friend_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    header("Location: friends.php");
    exit();
}

if (isset($_GET['remove'])) {
    $friend_id = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    $stmt->execute();
    header("Location: friends.php");
    exit();
}

$stmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN friends f ON (u.id = f.user_id OR u.id = f.friend_id) WHERE f.status = 'accepted' AND (f.user_id = ? OR f.friend_id = ?) AND u.id != ?");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$friends = $stmt->get_result();

$stmt = $conn->prepare("SELECT f.id, u.username FROM friends f JOIN users u ON f.user_id = u.id WHERE f.friend_id = ? AND f.status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Friends - Ch@tter</title>
    <link rel="stylesheet" href="feed.css">
    <link rel="stylesheet" href="friends.css">
</head>
<body>
<div class="navbar">
    <div class="logo">Ch@tter</div>
    <div class="nav-links">
        <a href="feed.php">Feed</a>
        <a href="chat.php">Chats</a>
        <a href="friends.php">Friends</a>
    </div>
    <div class="user-info-wrapper">
        <span class="user-info">Signed in as: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="gear-icon" id="gearToggle">⚙️</div>
        <div class="dropdown" id="userDropdown">
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
</div>

<div class="friends-container">
    <h2>Your Friends</h2>
    <?php while ($friend = $friends->fetch_assoc()): ?>
        <div class="friend-entry">
            <strong>@<?php echo htmlspecialchars($friend['username']); ?></strong>
            <div class="friend-actions">
                <a href="?remove=<?php echo $friend['id']; ?>" class="friend-btn">Remove</a>
            </div>
        </div>
    <?php endwhile; ?>

    <h2>Friend Requests</h2>
    <?php while ($request = $requests->fetch_assoc()): ?>
        <div class="friend-entry">
            <strong>@<?php echo htmlspecialchars($request['username']); ?></strong>
            <div class="friend-actions">
                <a href="?accept=<?php echo $request['id']; ?>" class="friend-btn">Accept</a>
            </div>
        </div>
    <?php endwhile; ?>

    <h2>Add a Friend</h2>
    <form method="POST" class="message-form">
        <input type="text" name="add_username" placeholder="Enter friend's username" required>
        <button type="submit">Send Request</button>
    </form>
</div>
<script>
    document.getElementById("gearToggle").addEventListener("click", function () {
        const dropdown = document.getElementById("userDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });

    document.addEventListener("click", function (event) {
        const toggle = document.getElementById("gearToggle");
        const dropdown = document.getElementById("userDropdown");
        if (!toggle.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = "none";
        }
    });
</script>
</body>
</html>

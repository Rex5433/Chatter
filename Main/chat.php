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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = htmlspecialchars_decode($_POST['message']);
    $file_path = null;

    if (!empty($_FILES['file']['name'])) {
        $upload_dir = 'uploads/messages/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES['file']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            $file_path = $target_path;
        }
    }

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $receiver_id, $message, $file_path);
    $stmt->execute();
    header("Location: chat.php?user_id=$receiver_id");
    exit();
}

$stmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN friends f ON (u.id = f.user_id OR u.id = f.friend_id) WHERE f.status = 'accepted' AND (f.user_id = ? OR f.friend_id = ?) AND u.id != ?");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$friends = $stmt->get_result();

$selected_friend_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$messages = [];
if ($selected_friend_id) {
    $stmt = $conn->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id = u.id WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    $stmt->bind_param("iiii", $user_id, $selected_friend_id, $selected_friend_id, $user_id);
    $stmt->execute();
    $messages = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ch@tter Chats</title>
    <link rel="stylesheet" href="feed.css">
    <link rel="stylesheet" href="chat.css">
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

<div class="chat-container">
    <div class="chat-sidebar">
        <h3>Friends</h3>
        <ul>
            <?php while ($friend = $friends->fetch_assoc()): ?>
                <li><a href="chat.php?user_id=<?php echo $friend['id']; ?>">@<?php echo htmlspecialchars($friend['username']); ?></a></li>
            <?php endwhile; ?>
        </ul>
    </div>

    <div class="chat-window">
        <?php if ($selected_friend_id): ?>
            <div class="chat-messages" id="chatMessages">
                <?php while ($msg = $messages->fetch_assoc()): ?>
                    <div class="message <?php echo $msg['sender_id'] === $user_id ? 'sent' : 'received'; ?>">
                        <h4>@<?php echo htmlspecialchars($msg['username']); ?></h4>
                        <p><?php echo nl2br(htmlspecialchars_decode($msg['content'])); ?></p>
                        <?php if ($msg['file_path']): ?>
                            <?php 
                                $ext = pathinfo($msg['file_path'], PATHINFO_EXTENSION);
                                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                                    echo '<img src="' . htmlspecialchars($msg['file_path']) . '" alt="Image" class="chat-media">';
                                } elseif (in_array(strtolower($ext), ['mp4', 'webm', 'ogg'])) {
                                    echo '<video controls class="chat-media"><source src="' . htmlspecialchars($msg['file_path']) . '"></video>';
                                }
                            ?>
                        <?php endif; ?>
                        <div class="date">Sent on <?php echo (new DateTime($msg['created_at']))->format('F j, Y \a\t g:i A'); ?></div>
                    </div>
                <?php endwhile; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" class="chat-form">
                <input type="hidden" name="receiver_id" value="<?php echo $selected_friend_id; ?>">
                <textarea name="message" placeholder="Type your message..." required></textarea>
                <input type="file" name="file" accept="image/*,video/*">
                <button type="submit">Send</button>
            </form>
        <?php else: ?>
            <p class="no-chat">Select a friend to start chatting.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    window.addEventListener('load', () => {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });

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

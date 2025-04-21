<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$post_id = $_GET['post_id'];

$stmt = $conn->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $_SESSION['user_id'], $comment);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Post</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Post by <?php echo htmlspecialchars($post['username']); ?></h2>
    <p><?php echo htmlspecialchars($post['content']); ?></p>
    <small><?php echo $post['created_at']; ?></small>

    <hr>
    <h3>Comments</h3>
    <?php
    $stmt = $conn->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at ASC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $comments = $stmt->get_result();

    while ($comment = $comments->fetch_assoc()) {
        echo "<div><strong>" . htmlspecialchars($comment['username']) . ":</strong> " .
             htmlspecialchars($comment['content']) . "<br><small>" .
             $comment['created_at'] . "</small></div><hr>";
    }
    ?>

    <form method="POST">
        <textarea name="comment" rows="2" cols="50" required></textarea><br>
        <input type="submit" value="Add Comment">
    </form>
</body>
</html>

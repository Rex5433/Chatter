<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $content);
        $stmt->execute();
        header("Location: feed.php");
        exit();
    } else {
        $error = "Post content cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Post</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Create a New Post</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <textarea name="content" rows="4" cols="50" placeholder="What's on your mind?" required></textarea><br>
        <input type="submit" value="Post">
    </form>
</body>
</html>

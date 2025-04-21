<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Updated for your merged login
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
    <title>Chatter | Feed</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .navbar {
            background-color: #2f3136;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #202225;
            margin-bottom: 20px;
        }

        .navbar a {
            color: #7289da;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .post-form {
            background-color: #2f3136;
            padding: 20px;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        textarea {
            background-color: #202225;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
    </style>
</head>
<body>

    <!-- ✅ NAVIGATION BAR -->
    <div class="navbar">
        <a href="feed.php">Feed</a>
        <a href="friends_list.php">Friends</a>
        <a href="chat.php?user_id=2">Chat (Test)</a>
        <a href="logout.php">Logout</a>
    </div>

    <h2>Create a New Post</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <div class="post-form">
        <form method="POST">
            <textarea name="content" rows="4" placeholder="What's on your mind?" required></textarea><br><br>
            <input type="submit" value="Post">
        </form>
    </div>

</body>
</html>

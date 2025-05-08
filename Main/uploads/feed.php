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

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_content'])) {
    $post_content = htmlspecialchars($_POST['post_content']);
    $media_path = null;

    if (isset($_FILES['post_media']) && $_FILES['post_media']['error'] == 0) {
        $target_dir = "uploads/";
        $media_name = basename($_FILES['post_media']['name']);
        $target_file = $target_dir . time() . "_" . $media_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'ogg'];
        if (in_array($file_type, $allowed_types)) {
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            if (move_uploaded_file($_FILES['post_media']['tmp_name'], $target_file)) {
                $media_path = $target_file;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, created_at, media_path) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("iss", $user_id, $post_content, $media_path);
    $stmt->execute();
    header("Location: feed.php");
    exit();
}

// Handle post edits
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_post_id'], $_POST['edit_content'])) {
    $edit_id = $_POST['edit_post_id'];
    $edit_content = htmlspecialchars($_POST['edit_content']);
    $stmt = $conn->prepare("UPDATE posts SET content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $edit_content, $edit_id, $user_id);
    $stmt->execute();
    header("Location: feed.php");
    exit();
}

// Handle post deletion
if (isset($_GET['delete_post_id'])) {
    $post_id = $_GET['delete_post_id'];
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    header("Location: feed.php");
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content'], $_POST['comment_post_id'])) {
    $comment_content = htmlspecialchars($_POST['comment_content']);
    $comment_post_id = (int)$_POST['comment_post_id'];

    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $comment_post_id, $user_id, $comment_content);
    $stmt->execute();
    header("Location: feed.php");
    exit();
}

// Fetch posts
$stmt = $conn->prepare("SELECT posts.id, posts.content, posts.created_at, posts.updated_at, posts.media_path, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ch@tter Feed</title>
    <link rel="stylesheet" href="feed.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo">Ch@tter</div>
    <div class="nav-links">
        <a href="feed.php">Feed</a>
        <a href="chat.php">Chats</a>
        <a href="friends.php">Friends</a>
    </div>
    <div class="user-info-wrapper">
        <span class="user-info">Signed in as: <?php echo $username; ?></span>
        <div class="gear-icon" id="gearToggle">⚙️</div>
        <div class="dropdown" id="userDropdown">
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
</div>

<!-- SIDEBAR TOGGLE -->
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

<!-- MAIN FEED + SIDEBAR -->
<div class="main-container">
    <div class="feed">
        <button class="top-post-btn" id="floatingPostBtn">Create Post</button>

        <?php 
        if ($result && $result->num_rows > 0) {
            while ($post = $result->fetch_assoc()) {
                $postDate = new DateTime($post['created_at']);
                $formattedDate = $postDate->format('F j, Y \a\t g:i A');

                echo '<div class="post" data-post-id="' . $post['id'] . '">';
                echo '<h3>@' . htmlspecialchars($post['username']) . '</h3>';
                echo '<p class="post-content" id="content-' . $post['id'] . '">' . nl2br(htmlspecialchars($post['content'])) . '</p>';

                if (!empty($post['media_path'])) {
                    $ext = pathinfo($post['media_path'], PATHINFO_EXTENSION);
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        echo '<img src="' . htmlspecialchars($post['media_path']) . '" class="post-media" alt="Uploaded media">';
                    } elseif (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                        echo '<video controls class="post-media"><source src="' . htmlspecialchars($post['media_path']) . '" type="video/' . $ext . '"></video>';
                    }
                }

                echo '<form class="edit-form" style="display:none" method="POST">';
                echo '<input type="hidden" name="edit_post_id" value="' . $post['id'] . '">';
                echo '<textarea name="edit_content" class="edit-textarea">' . htmlspecialchars($post['content']) . '</textarea>';
                echo '<div class="edit-controls">';
                echo '<button type="submit" class="save-btn">Save</button>';
                echo '<button type="button" class="cancel-edit">Cancel</button>';
                echo '</div>';
                echo '</form>';

                if (!empty($post['updated_at'])) {
                    echo '<div class="date">Posted on ' . $formattedDate . ' <span class="edited-tag">(edited)</span></div>';
                } else {
                    echo '<div class="date">Posted on ' . $formattedDate . '</div>';
                }

                if ($post['username'] === $username) {
                    echo '<div class="post-options">';
                    echo '<button class="options-button">⋯</button>';
                    echo '<div class="options-dropdown">';
                    echo '<button class="trigger-edit" data-id="' . $post['id'] . '">Edit</button>';
                    echo '<button onclick="location.href=\'feed.php?delete_post_id=' . $post['id'] . '\'">Delete</button>';
                    echo '</div>';
                    echo '</div>';
                }

                // Comments
                $comment_stmt = $conn->prepare("SELECT comments.content, comments.created_at, users.username 
                                                FROM comments 
                                                JOIN users ON comments.user_id = users.id 
                                                WHERE comments.post_id = ? 
                                                ORDER BY comments.created_at ASC");
                $comment_stmt->bind_param("i", $post['id']);
                $comment_stmt->execute();
                $comment_result = $comment_stmt->get_result();

                echo '<div class="comments"><h4>Comments</h4>';
                while ($comment = $comment_result->fetch_assoc()) {
                    $comment_date = new DateTime($comment['created_at']);
                    echo '<div class="comment">';
                    echo '<strong>@' . htmlspecialchars($comment['username']) . '</strong>: ';
                    echo nl2br(htmlspecialchars($comment['content']));
                    echo '<div class="comment-date">' . $comment_date->format('M j, g:i A') . '</div>';
                    echo '</div>';
                }
                echo '</div>';

                // Comment form
                echo '<form method="POST" class="comment-form">';
                echo '<input type="hidden" name="comment_post_id" value="' . $post['id'] . '">';
                echo '<input type="text" name="comment_content" placeholder="Add a comment..." required>';
                echo '<button type="submit">Comment</button>';
                echo '</form>';

                echo '</div>';
            }
        } else {
            echo '<p>No posts yet. Be the first to share!</p>';
        }

        $stmt->close();
        ?>
    </div>

    <div class="chat-preview-sidebar">
        <h3>Direct Messages</h3>
        <?php
        $chat_query = "
            SELECT 
                IF(m.sender_id = ?, m.receiver_id, m.sender_id) AS other_user_id,
                MAX(m.created_at) AS latest_message_time,
                (SELECT content FROM messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id) 
                    OR (sender_id = other_user_id AND receiver_id = ?) 
                 ORDER BY created_at DESC 
                 LIMIT 1) AS latest_message
            FROM messages m
            WHERE m.sender_id = ? OR m.receiver_id = ?
            GROUP BY other_user_id
            ORDER BY latest_message_time DESC
        ";

        $chat_stmt = $conn->prepare($chat_query);
        $chat_stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
        $chat_stmt->execute();
        $chat_result = $chat_stmt->get_result();

        while ($row = $chat_result->fetch_assoc()) {
            $other_user_id = $row['other_user_id'];

            $username_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $username_stmt->bind_param("i", $other_user_id);
            $username_stmt->execute();
            $username_result = $username_stmt->get_result();
            $username_data = $username_result->fetch_assoc();
            $other_user_username = htmlspecialchars($username_data['username']);

            $latest_message = htmlspecialchars($row['latest_message']);
            $latest_message_time = (new DateTime($row['latest_message_time']))->format('F j, Y \a\t g:i A');
            
            echo '<div class="chat-preview">';
            echo '<a href="chat.php?user=' . $other_user_username . '"><strong>@' . $other_user_username . '</strong><br>';
            echo '<span>' . $latest_message . '</span><br>';
            echo '<span class="timestamp">Last message: ' . $latest_message_time . '</span>';
            echo '</a>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- POST MODAL -->
<div class="overlay" id="overlay"></div>
<div class="post-modal" id="postModal">
    <form id="postForm" enctype="multipart/form-data" method="POST">
        <input type="text" name="post_content" id="postContent" placeholder="What's on your mind?" />
        <input type="file" name="post_media" accept="image/*,video/*" />
        <button type="submit" id="postButton">Post</button>
    </form>
</div>

<script>
    $('#floatingPostBtn').click(function () {
        $('#postModal').fadeIn();
        $('#overlay').fadeIn();
    });

    $('#overlay').click(function () {
        $('#postModal').fadeOut();
        $('#overlay').fadeOut();
    });

    $(document).on('click', '.options-button', function (e) {
        e.stopPropagation();
        $('.options-dropdown').not($(this).next()).hide();
        $(this).next('.options-dropdown').toggle();
    });

    $(document).on('click', '.trigger-edit', function (e) {
        e.stopPropagation();
        $('.options-dropdown').hide();
        const postId = $(this).data('id');
        const post = $('[data-post-id="' + postId + '"]');
        post.find('.post-content').hide();
        post.find('.edit-form').show();
    });

    $(document).on('click', '.cancel-edit', function () {
        const post = $(this).closest('.post');
        post.find('.edit-form').hide();
        post.find('.post-content').show();
    });
</script>

</body>
</html>

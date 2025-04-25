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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_content'])) {
    $post_content = htmlspecialchars($_POST['post_content']);
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $post_content);
    $stmt->execute();
    header("Location: feed.php");
    exit();
}

if (isset($_GET['delete_post_id'])) {
    $post_id = $_GET['delete_post_id'];
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    header("Location: feed.php");
    exit();
}

$stmt = $conn->prepare("SELECT posts.id, posts.content, posts.created_at, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ch@tter Feed</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #0e1836;
            color: #FAFDFF;
        }

        .navbar {
            background-color: #0e1836;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #7BBBFE;
            position: relative;
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #7BBBFE;
        }

        .navbar .user-info-wrapper {
            position: relative;
            cursor: pointer;
        }

        .navbar .user-info {
            font-size: 1rem;
            color: #B6CFFF;
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: #1a2a4f;
            border: 1px solid #7BBBFE;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            z-index: 100;
        }

        .dropdown a {
            color: #B6CFFF;
            text-decoration: none;
            display: block;
        }

        .dropdown a:hover {
            color: #FAFDFF;
        }

        .main-container {
            display: flex;
            height: calc(100vh - 70px);
        }

        .feed {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .post {
            background-color: #1a2a4f;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            border-left: 4px solid #B8AAFF;
            position: relative;
        }

        .post h3 {
            margin-top: 0;
            color: #7BBBFE;
        }

        .post .date {
            font-size: 0.9rem;
            color: #B6CFFF;
            margin-top: 0.5rem;
        }

        .post .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ff4d4d;
            color: #fff;
            padding: 0.3rem 0.6rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .post .delete-btn:hover {
            background-color: #ff1a1a;
        }

        .chat-toggle {
            position: absolute;
            right: 0;
            top: 80px;
            background-color: #7BBBFE;
            color: #0e1836;
            padding: 0.5rem 1rem;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            cursor: pointer;
            z-index: 10;
        }

        .chat-sidebar {
            width: 300px;
            background-color: #1a2a4f;
            padding: 1rem;
            border-left: 2px solid #B6CFFF;
            position: fixed;
            top: 70px;
            right: -300px;
            height: calc(100vh - 70px);
            transition: right 0.3s ease;
        }

        .chat-sidebar.open {
            right: 0;
        }

        .friend-btn {
            background-color: #B8AAFF;
            color: #0e1836;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            margin-left: 1rem;
        }

        .friend-btn:hover {
            background-color: #B6CFFF;
        }

        .floating-post-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: #7BBBFE;
            color: #0e1836;
            padding: 0.6rem 1.2rem;
            border-radius: 50%;
            font-size: 1.5rem;
            border: none;
            cursor: pointer;
            z-index: 10;
        }

        .floating-post-btn:hover {
            background-color: #5a9bd5;
        }

        .post-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #1a2a4f;
            padding: 20px;
            border-radius: 12px;
            width: 80%;
            max-width: 500px;
            z-index: 20;
        }

        .post-modal input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 1px solid #7BBBFE;
            background-color: #0e1836;
            color: #B6CFFF;
        }

        .post-modal button {
            background-color: #7BBBFE;
            color: #0e1836;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            width: 100%;
        }

        .post-modal button:hover {
            background-color: #5a9bd5;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">Ch@tter</div>
    <div class="user-info-wrapper" id="userDropdownToggle">
        <span class="user-info">Signed in as: <?php echo $username; ?></span>
        <div class="dropdown" id="userDropdown">
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
</div>

<div class="chat-toggle" id="chatToggle">Chats</div>

<div class="main-container">
    <div class="feed">
        <?php 
        if ($result->num_rows > 0) {
            while ($post = $result->fetch_assoc()) {
                $postDate = new DateTime($post['created_at']);
                $formattedDate = $postDate->format('F j, Y \a\t g:i A');
                
                echo '<div class="post">';
                echo '<h3>@' . htmlspecialchars($post['username']) . '</h3>';
                echo '<p>' . nl2br(htmlspecialchars($post['content'])) . '</p>';
                echo '<div class="date">Posted on ' . $formattedDate . '</div>';

                if ($post['username'] === $username) {
                    echo '<a href="feed.php?delete_post_id=' . $post['id'] . '" class="delete-btn">Delete</a>';
                }

                echo '</div>';
            }
        } else {
            echo '<p>No posts yet. Be the first to share!</p>';
        }

        $stmt->close();
        ?>
    </div>

    <div class="chat-sidebar" id="chatSidebar">
        <h3>Direct Messages</h3>
        <p><strong>@friend1:</strong> yo let's catch up soon</p>
        <p><strong>@you:</strong> for sure, hit me up</p>
    </div>
</div>

<!-- Floating post button -->
<button class="floating-post-btn" id="floatingPostBtn">+</button>

<!-- Modal for creating a post -->
<div class="overlay" id="overlay"></div>
<div class="post-modal" id="postModal">
    <input type="text" id="postContent" placeholder="What's on your mind?" />
    <button id="postButton">Post</button>
</div>

<script>
    $('#chatToggle').click(function () {
        $('#chatSidebar').toggleClass('open');
    });

    $('#floatingPostBtn').click(function () {
        $('#postModal').fadeIn();
        $('#overlay').fadeIn();
    });

    $('#overlay').click(function () {
        $('#postModal').fadeOut();
        $('#overlay').fadeOut();
    });

    $('#postButton').click(function () {
        const content = $('#postContent').val();
        if (content.trim() !== '') {
            $.post('feed.php', { post_content: content }, function () {
                $('#postModal').fadeOut();
                $('#overlay').fadeOut();
                location.reload();
            });
        }
    });

    $('#userDropdownToggle').click(function () {
        $('#userDropdown').toggle();
    });

    $(document).mouseup(function (e) {
        const dropdown = $('#userDropdown');
        if (!dropdown.is(e.target) && dropdown.has(e.target).length === 0) {
            dropdown.hide();
        }
    });
</script>

</body>
</html>

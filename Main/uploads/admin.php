<?php
require_once 'db_connect.php'; // use your mysqli connection

// Use PDO for simplicity with this admin interface
$pdo = new PDO("mysql:host=localhost;dbname=chatter;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Get user ID -> username mapping
$users = $pdo->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_KEY_PAIR);

// Tables to show
$tables = ['users', 'posts', 'comments', 'messages', 'friends'];

// Escape output
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'], $_POST['table'], $_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM `{$_POST['table']}` WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: admin.php");
    exit;
}

// Handle UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'], $_POST['table'], $_POST['id'])) {
    $table = $_POST['table'];
    $id = $_POST['id'];
    $fields = array_filter($_POST, fn($k) => !in_array($k, ['update', 'table', 'id']), ARRAY_FILTER_USE_KEY);

    $setClause = implode(", ", array_map(fn($key) => "`$key` = ?", array_keys($fields)));
    $values = array_values($fields);
    $values[] = $id;

    $stmt = $pdo->prepare("UPDATE `$table` SET $setClause WHERE id = ?");
    $stmt->execute($values);
    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CH@TTER Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            border-collapse: collapse;
            margin-bottom: 40px;
            width: 100%;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
        }
        th {
            background-color: #f4f4f4;
        }
        input[type="text"], textarea {
            width: 100%;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            overflow: hidden;
            min-height: 40px;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        /* Sign out button in top-right */
        .signout-button-container {
            text-align: right;
            margin-bottom: 20px;
            width: 100%;
        }

        .signout-button-container button {
            background-color: #FF6F6F;
            padding: 6px 12px;
            border: none;
            color: #FAFDFF;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .signout-button-container button:hover {
            background-color: #FF4444;
        }
    </style>
</head>
<body>
    <div class="signout-button-container">
        <a href="logout.php">
            <button>Sign Out</button>
        </a>
    </div>

    <h1>CH@TTER Admin Panel</h1>

    <?php foreach ($tables as $table): ?>
        <h2><?= ucfirst($table) ?></h2>
        <div class="table-wrapper">
        <table>
            <tr>
                <?php
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    foreach (array_keys($rows[0]) as $col) {
                        echo "<th>" . escape($col) . "</th>";
                    }
                    echo "<th>Actions</th>";
                }
                ?>
            </tr>
            <?php foreach ($rows as $row): ?>
                <form method="POST">
                <tr>
                    <?php foreach ($row as $key => $value): ?>
                        <td>
                            <?php
                            // Replace IDs with usernames where relevant
                            if (in_array($key, ['user_id', 'sender_id', 'receiver_id', 'friend_id'])) {
                                echo isset($users[$value]) ? escape($users[$value]) : "Unknown";
                            }
                            // Use <textarea> for long text fields
                            elseif (in_array($key, ['content', 'message', 'text', 'body'])) {
                                echo "<textarea name='$key' oninput='autoResize(this)'>" . escape($value) . "</textarea>";
                            }
                            // Default to input
                            else {
                                echo "<input type='text' name='$key' value='" . escape($value) . "'>";
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <input type="hidden" name="table" value="<?= escape($table) ?>">
                        <input type="hidden" name="id" value="<?= escape($row['id']) ?>">
                        <button type="submit" name="update">Update</button>
                        <button type="submit" name="delete" onclick="return confirm('Delete this record?')">Delete</button>
                    </td>
                </tr>
                </form>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endforeach; ?>

    <script>
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("textarea").forEach(autoResize);
        });
    </script>
</body>
</html>

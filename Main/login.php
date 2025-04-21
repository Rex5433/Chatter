<?php
session_start();
require 'db_connect.php';

$loginError = '';
$logoutMessage = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password, email, created_at FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $input, $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["created_at"] = $user["created_at"];
            header("Location: feed.php");
            exit();
        }
    }

    $loginError = "Invalid username or password. Please try again.";
}

if (isset($_GET['loggedout'])) {
    $logoutMessage = "You have been logged out successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chatter | Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Login to Chatter</h2>

    <?php if ($loginError): ?>
        <p style="color: red;"><?php echo $loginError; ?></p>
    <?php endif; ?>

    <?php if ($logoutMessage): ?>
        <p style="color: lightgreen;"><?php echo $logoutMessage; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username or Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="submit" value="Login">
    </form>

    <p>Don't have an account? <a href="register.html">Register here</a></p>
</body>
</html>

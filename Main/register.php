<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    if (!$username || !$email || !$password || !$confirm) {
        die("All fields are required.");
    }

    if ($password !== $confirm) {
        die("Passwords do not match.");
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

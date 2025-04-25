<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (!empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Username is already taken.";
            } else {
                // Hash the password and insert the new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
                $is_admin = 0; // Default to regular user
                $stmt->bind_param("ssi", $username, $hashed_password, $is_admin);
                if ($stmt->execute()) {
                    header("Location: login.php"); // Redirect to login page after successful registration
                    exit();
                } else {
                    $error = "There was an error, please try again.";
                }
            }
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chatter | Register</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #0e1836;
            color: #FAFDFF;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column;
        }

        .register-container {
            background-color: #1c2a4a;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 300px;
            width: 100%;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #7BBBFE;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 6px;
            background-color: #2a3553;
            color: #FAFDFF;
            font-size: 14px;
        }

        input[type="submit"] {
            background-color: #7BBBFE;
            border: none;
            color: #0e1836;
            padding: 12px;
            margin-top: 10px;
            font-weight: bold;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        input[type="submit"]:hover {
            background-color: #B6CFFF;
        }

        .login-link {
            margin-top: 15px;
            font-size: 14px;
        }

        .login-link a {
            color: #B8AAFF;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #7BBBFE;
        }

        .error {
            color: #FF6F6F;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Join CH@TTER</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" id="registerForm">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <input type="submit" value="Register">
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
    <div class="footer">
        Created by Alex and Taylor
    </div>
</body>
</html>

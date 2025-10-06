<?php
session_start();
include 'db_connect.php';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Find the user and check if they are an admin, INCLUDING ban status
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role, banned_until FROM Users WHERE (username = :username OR email = :username) AND role = 'admin'");
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Password is correct, now check if they are banned
        if (!is_null($admin['banned_until']) && strtotime($admin['banned_until']) > time()) {
            
            // Check if the ban is permanent
            if (date('Y', strtotime($admin['banned_until'])) == '9999') {
                $error_message = "This admin account has been permanently banned. Please contact the site owner.";
            } else {
                // It's a temporary ban
                $ban_expiry = date('F j, Y, g:i a', strtotime($admin['banned_until']));
                $error_message = "This admin account is temporarily banned until: " . $ban_expiry;
            }

        } else {
            // Not banned, log them in
            $_SESSION['user_id'] = $admin['user_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = 'admin';
            header("Location: admin_panel.php");
            exit();
        }
    } else {
        $error_message = "Invalid credentials or not an admin account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - NextRig</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="form-container" style="margin-top: 80px;">
        <h2>NextRig Admin Panel</h2>
        <p>Please log in to continue.</p>
        <?php if ($error_message): ?>
            <div class="error-banner"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="admin_login.php" method="POST">
            <div class="form-group">
                <label for="username">Admin Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
    </div>
</body>
</html>
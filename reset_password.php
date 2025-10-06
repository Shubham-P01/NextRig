<?php
$page_title = "Reset Password";
include 'header.php'; // This also includes db_connect.php

$token = $_GET['token'] ?? null;
$error_message = '';
$token_is_valid = false;

if (!$token) {
    $error_message = "No reset token provided. The link may be incomplete.";
} else {
    // Validate the token
    $token_hash = hash('sha256', $token);
    $current_time = date('Y-m-d H:i:s'); // Get current time from PHP

    // MODIFIED: Compare expiry time against PHP's current time instead of MySQL's NOW()
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > ?");
    $stmt->execute([$token_hash, $current_time]);
    $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reset_request) {
        $token_is_valid = true;
        $user_id = $reset_request['user_id'];

        // Handle the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($password) || empty($confirm_password)) {
                $error_message = "Please fill out both password fields.";
            } elseif (strlen($password) < 6) {
                $error_message = "Password must be at least 6 characters long.";
            } elseif ($password !== $confirm_password) {
                $error_message = "Passwords do not match.";
            } else {
                // Passwords match, update the user's password
                $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
                $update_stmt->execute([$new_password_hash, $user_id]);

                // Delete the token so it cannot be used again
                $delete_stmt = $pdo->prepare("DELETE FROM password_resets WHERE token_hash = ?");
                $delete_stmt->execute([$token_hash]);
                
                // Redirect to login page with success message
                header("Location: login.php?status=reset_success");
                exit();
            }
        }
    } else {
        $error_message = "This password reset link is invalid or has expired. Please request a new one.";
    }
}
?>

<div class="form-container auth-form">
    <div class="auth-header">
        <h2>Set a New Password</h2>
    </div>

    <?php if ($error_message): ?>
        <div class="error-banner"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($token_is_valid): ?>
        <p>Please enter your new password below.</p>
        <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Enter a new password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Reset Password</button>
        </form>
    <?php else: ?>
        <p style="text-align: center;"><a href="forgot_password.php" style="color: var(--accent-purple);">Request a new reset link</a></p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
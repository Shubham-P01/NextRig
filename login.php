<?php
$page_title = "Login to NextRig";
// A session must be started at the very top to track login attempts
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
include 'db_connect.php'; 
$error_message = '';
$username_value = ''; // Variable to hold the username on failed attempts

// Initialize login attempt counter if it doesn't exist in the session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $username_value = $username; // Store the submitted username to display it again

    $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role, banned_until FROM Users WHERE username = :username OR email = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct, now check if they are banned
        if (!is_null($user['banned_until']) && strtotime($user['banned_until']) > time()) {
            $_SESSION['login_attempts']++; // A failed login due to a ban also counts
            if (date('Y', strtotime($user['banned_until'])) == '9999') {
                $error_message = "Your account has been permanently banned. If you believe this is an error, please contact the owner.";
            } else {
                $ban_expiry = date('F j, Y, g:i a', strtotime($user['banned_until']));
                $error_message = "Your account is temporarily banned. Access is restricted until: " . $ban_expiry;
            }
        } else {
            // User is not banned - SUCCESSFUL LOGIN
            $_SESSION['login_attempts'] = 0; // Reset counter on success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if (!empty($_POST['remember_me'])) {
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);
                $expiry_date = date('Y-m-d H:i:s', time() + (86400 * 30)); 

                $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['user_id'], $token_hash, $expiry_date]);

                setcookie('remember_me', $token, time() + (86400 * 30), "/", "", false, true);
            }

            header("Location: forum.php");
            exit();
        }
    } else {
        // FAILED LOGIN - Invalid credentials
        $_SESSION['login_attempts']++; // Increment counter on failure
        $error_message = "Invalid username or password.";
    }
}

include 'header.php'; 
?>
<div class="form-container auth-form">
    <div class="auth-header">
        <h2>Welcome to NextRig</h2>
        <p>Sign in to your account or create a new one</p>
    </div>

    <div class="auth-tabs">
        <a href="login.php" class="active">Sign In</a>
        <a href="signup.php">Sign Up</a>
    </div>

    <?php if (!empty($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="success-banner" style="background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center;">Account created successfully! Please log in.</div>
    <?php elseif (!empty($_GET['status']) && $_GET['status'] === 'reset_success'): ?>
        <div class="success-banner" style="background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center;">Password has been reset successfully! Please log in.</div>
    <?php endif; ?>
    <?php if ($error_message): ?><div class="error-banner"><?= htmlspecialchars($error_message); ?></div><?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" placeholder="Enter your username or email" value="<?= htmlspecialchars($username_value) ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <div class="form-options">
            <div class="checkbox-group">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember me</label>
            </div>
            
            <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 2): ?>
                <a href="forgot_password.php">Forgot password?</a>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
    </form>
</div>
<?php include 'footer.php'; ?>
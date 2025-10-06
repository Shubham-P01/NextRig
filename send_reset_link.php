<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_POST['email'];

// Find the user by email
$stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Email not found, redirect back with an error
    header('Location: forgot_password.php?error=notfound');
    exit();
}

$user_id = $user['user_id'];

// Generate a secure token
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);

// Set an expiry date for 1 hour from now
$expires_at = date('Y-m-d H:i:s', time() + 3600);

// Store the hashed token in the database
try {
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $token_hash, $expires_at]);
} catch (PDOException $e) {
    die("Database error. Could not save reset token.");
}

// --- EMAIL SIMULATION ---
// Determine the base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
$base_url = rtrim("$protocol://$host$path", '/\\');
$reset_link = $base_url . "/reset_password.php?token=" . $token;

// Display the link in a user-friendly page
include 'header.php';
?>
<div class="form-container auth-form">
    <div class="auth-header">
        <h2>Reset Link Generated</h2>
        <p>In a real application, this link would be sent to your email.</p>
    </div>
    <div class="form-group">
        <label>You can copy the link or click the button below:</label>
        <textarea readonly style="width: 100%; height: 80px; padding: 10px; font-family: monospace; resize: none; margin-bottom: 20px;"><?= htmlspecialchars($reset_link) ?></textarea>
    </div>
    
    <a href="<?= htmlspecialchars($reset_link) ?>" class="btn btn-primary" style="width: 100%; margin-bottom: 15px;">Go to Reset Page</a>
    <a href="login.php" class="btn btn-secondary" style="width: 100%;">Back to Login</a>
</div>
<?php
include 'footer.php';
?>
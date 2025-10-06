<?php
$page_title = "Create an Account";
include 'db_connect.php';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error_message = "Username or email already exists.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Users (first_name, last_name, username, email, password_hash) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$first_name, $last_name, $username, $email, $password_hash])) {
                header("Location: login.php?status=success");
                exit();
            } else {
                $error_message = "An error occurred. Please try again.";
            }
        }
    }
}

include 'header.php';
?>

<div class="form-container auth-form">
    <div class="auth-header">
        <h2>Create Your Account</h2>
        <p>Join the NextRig community today</p>
    </div>

    <div class="auth-tabs">
        <a href="login.php">Sign In</a>
        <a href="signup.php" class="active">Sign Up</a>
    </div>

    <?php if ($error_message): ?><div class="error-banner"><?= htmlspecialchars($error_message); ?></div><?php endif; ?>

    <form action="signup.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required>
            </div>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Choose a username" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Create a password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Sign Up</button>
    </form>
</div>

<?php include 'footer.php'; ?>
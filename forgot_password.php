<?php
$page_title = "Forgot Password";
include 'header.php';
?>

<div class="form-container auth-form">
    <div class="auth-header">
        <h2>Reset Your Password</h2>
        <p>Enter your email address and we'll send you a link to reset your password.</p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-banner">Email not found. Please try again.</div>
    <?php endif; ?>

    <form action="send_reset_link.php" method="POST">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Link</button>
    </form>
</div>

<?php include 'footer.php'; ?>
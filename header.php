<?php
// We need the DB connection for token login, which happens before session_start
include_once 'db_connect.php'; // Also sets timezone

// This function attempts to log a user in via their "Remember Me" cookie
function login_with_token($pdo) {
    if (isset($_COOKIE['remember_me'])) {
        $token = $_COOKIE['remember_me'];
        $token_hash = hash('sha256', $token);

        // Find a valid, unexpired token and get the user's details
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, u.role, u.banned_until 
            FROM auth_tokens at
            JOIN Users u ON at.user_id = u.user_id
            WHERE at.token_hash = ? AND at.expires_at > NOW()
        ");
        $stmt->execute([$token_hash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $is_banned_via_token = !is_null($user['banned_until']) && strtotime($user['banned_until']) > time();
            if (!$is_banned_via_token) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
            }
        }
    }
}

// If no session has been started yet, try to log in using a "Remember Me" token
if (session_status() === PHP_SESSION_NONE) {
    login_with_token($pdo);
}

// Make sure a session is started for all pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- NEW: SESSION TIMEOUT LOGIC ---
if (isset($_SESSION['user_id'])) { // Check if the user is logged in via session
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) { // 1800 seconds = 30 minutes
        // If last request was more than 30 minutes ago, log them out.
        include 'logout.php'; // This will destroy the session and cookies, then exit.
    }
    $_SESSION['last_activity'] = time(); // Update last activity timestamp on every page load
}

// --- Central Status Checks ---
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = ($is_logged_in && isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$is_banned = false; // A user with an active session is by definition not banned

// Final security check for banning
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT banned_until FROM Users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $banned_until = $stmt->fetchColumn();

    if ($banned_until && strtotime($banned_until) > time()) {
        include 'logout.php';
    }
}

// Central place to check for user status
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$is_banned = false; // A logged-in user cannot be banned

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - NextRig' : 'NextRig Forum' ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>
<body>
    <header class="navbar">
    <div class="nav-container">
        <a href="#" class="nav-logo">NextRig</a>
        <nav class="nav-menu">
            <a href="index.php" class="nav-link">Home</a>
            <a href="shop.php" class="nav-link active">Shop</a>
            <a href="forum.php" class="nav-link">Forum</a>
            <a href="sell.php" class="nav-link">Sell</a>
            <a href="aboutus.php" class="nav-link">About Us</a>
            <a href="contact.php" class="nav-link">Contact</a>
        </nav>
        <div class="nav-icons">
    <a href="profile.php" title="My Account">
        <svg xmlns="http://www.w.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
    </a>

    <a href="cart.php" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
    </a>
    <div class="nav-user-actions">
                <?php if ($is_logged_in): ?>
                    <?php if ($is_admin): ?>
                        <a href="admin_panel.php" class="nav-link admin-link">Admin Panel</a>
                    <?php endif; ?>
                    <span class="nav-welcome">Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
</div>
    </div>
</header>
    <main class="main-content container">
        
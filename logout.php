<?php
session_start();
include 'db_connect.php';

// If a "remember me" cookie is set, delete its token from the database
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $token_hash = hash('sha256', $token);
    
    $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token_hash = ?");
    $stmt->execute([$token_hash]);

    // Unset the cookie by setting its expiration to the past
    setcookie('remember_me', '', time() - 3600, "/");
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
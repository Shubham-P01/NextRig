<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    header('Location: forum.php');
    exit();
}

$user_id_to_unban = $_POST['user_id'];

// Set 'banned_until' to NULL to unban the user
$stmt = $pdo->prepare("UPDATE Users SET banned_until = NULL WHERE user_id = ?");
$stmt->execute([$user_id_to_unban]);

// MODIFIED: Added cache buster (&t=time())
header('Location: manage_users.php?status=unbanned&t=' . time());
exit();
?>
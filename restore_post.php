<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id'])) {
    header('Location: forum.php');
    exit();
}

$post_id = $_POST['post_id'];

// Set deleted_at to NULL to restore the post
$stmt = $pdo->prepare("UPDATE Posts SET deleted_at = NULL WHERE post_id = ?");
$stmt->execute([$post_id]);

header('Location: manage_content.php?status=restored&t=' . time());
exit();
?>
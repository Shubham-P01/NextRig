<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment_id'])) {
    header('Location: forum.php');
    exit();
}

$comment_id = $_POST['comment_id'];

// Set deleted_at to NULL to restore the comment
$stmt = $pdo->prepare("UPDATE Comments SET deleted_at = NULL WHERE comment_id = ?");
$stmt->execute([$comment_id]);

header('Location: manage_content.php?status=restored&t=' . time());
exit();
?>
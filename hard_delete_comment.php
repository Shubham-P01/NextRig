<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment_id'])) {
    header('Location: forum.php');
    exit();
}

$comment_id = $_POST['comment_id'];

// Permanently delete the comment and its associated votes
$pdo->prepare("DELETE FROM Votes WHERE comment_id = ?")->execute([$comment_id]);
$pdo->prepare("DELETE FROM Comments WHERE comment_id = ?")->execute([$comment_id]);

header('Location: manage_content.php?status=deleted&t=' . time());
exit();
?>
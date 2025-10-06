<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id'])) {
    header('Location: forum.php');
    exit();
}

$post_id = $_POST['post_id'];

// Permanently delete the post and its associated votes and comments
$pdo->prepare("DELETE FROM Votes WHERE post_id = ?")->execute([$post_id]);
$pdo->prepare("DELETE FROM Comments WHERE post_id = ?")->execute([$post_id]);
$pdo->prepare("DELETE FROM Posts WHERE post_id = ?")->execute([$post_id]);

header('Location: manage_content.php?status=deleted&t=' . time());
exit();
?>
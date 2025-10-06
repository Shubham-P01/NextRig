<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    if ($is_admin) {
        // Admins can delete any post
        $sql = "UPDATE Posts SET deleted_at = NOW() WHERE post_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$post_id]);
    } else {
        // Users can only delete their own posts
        $sql = "UPDATE Posts SET deleted_at = NOW() WHERE post_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$post_id, $user_id]);
    }
}

header('Location: forum.php');
exit();
?>


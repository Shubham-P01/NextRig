<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment_id'])) {
    header('Location: forum.php');
    exit();
}

$comment_id = $_POST['comment_id'];
$post_id = $_POST['post_id']; // Needed to redirect back
$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

try {
    if ($is_admin) {
        // Admins can delete any comment
        $stmt = $pdo->prepare("UPDATE Comments SET deleted_at = NOW() WHERE comment_id = ?");
        $stmt->execute([$comment_id]);
    } else {
        // Users can only delete their own comments
        $stmt = $pdo->prepare("UPDATE Comments SET deleted_at = NOW() WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
    }
} catch (PDOException $e) {
    die("Error deleting comment: " . $e->getMessage());
}

// Redirect back to the post page
header("Location: post.php?id=" . $post_id);
exit();
?>


<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['report_id']) || !isset($_POST['action'])) {
    header('Location: forum.php');
    exit();
}

$report_id = $_POST['report_id'];
$action = $_POST['action'];

if ($action === 'dismiss') {
    // Just close the report
    $stmt = $pdo->prepare("UPDATE reports SET status = 'closed' WHERE report_id = ?");
    $stmt->execute([$report_id]);
} elseif ($action === 'delete_content') {
    // Find out what content this report is for
    $stmt = $pdo->prepare("SELECT post_id, comment_id FROM reports WHERE report_id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($report) {
        if ($report['post_id']) {
            // Soft-delete the post
            $pdo->prepare("UPDATE Posts SET deleted_at = NOW() WHERE post_id = ?")->execute([$report['post_id']]);
        } elseif ($report['comment_id']) {
            // Soft-delete the comment
            $pdo->prepare("UPDATE Comments SET deleted_at = NOW() WHERE comment_id = ?")->execute([$report['comment_id']]);
        }
    }
    // After handling the content, close the report
    $pdo->prepare("UPDATE reports SET status = 'closed' WHERE report_id = ?")->execute([$report_id]);
}

header('Location: manage_reports.php?status=handled&t=' . time());
exit();
?>
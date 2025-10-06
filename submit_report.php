<?php
session_start();
include 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Security: User must be logged in to report
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a report.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$reporter_user_id = $_SESSION['user_id'];
$reason = trim($_POST['reason'] ?? '');

// MODIFIED: Correctly convert empty strings to NULL
$post_id = !empty($_POST['post_id']) ? (int)$_POST['post_id'] : null;
$comment_id = !empty($_POST['comment_id']) ? (int)$_POST['comment_id'] : null;

// Validation
if (empty($reason)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A reason is required to submit a report.']);
    exit();
}
if ((!$post_id && !$comment_id) || ($post_id && $comment_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid report target.']);
    exit();
}

try {
    $sql = "INSERT INTO reports (reporter_user_id, post_id, comment_id, reason) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reporter_user_id, $post_id, $comment_id, $reason]);
    
    echo json_encode(['success' => true, 'message' => 'Report submitted successfully. Thank you for your feedback.']);

} catch (PDOException $e) {
    // This block was being triggered before the fix
    http_response_code(500);
    // For debugging, you could show the actual error: echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>
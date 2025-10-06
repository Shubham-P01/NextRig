<?php
session_start();
include 'db_connect.php';

// Security: User must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'You must be logged in to vote.']);
    exit();
}

// BAN CHECK
$stmt = $pdo->prepare("SELECT banned_until FROM Users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_status = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user_status && !is_null($user_status['banned_until']) && strtotime($user_status['banned_until']) > time()) {
    http_response_code(403);
    echo json_encode(['error' => 'Your account is banned. You cannot vote.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$post_id = $_POST['post_id'] ?? null;
$comment_id = $_POST['comment_id'] ?? null;
$vote_type = $_POST['vote_type'] ?? null;
$user_id = $_SESSION['user_id'];

// (The rest of the file is the same as the previous version)
if ((!$post_id && !$comment_id) || ($post_id && $comment_id) || !in_array($vote_type, [1, -1])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}
$vote_type = (int)$vote_type;

if ($post_id) {
    $id_column = 'post_id';
    $id_value = $post_id;
} else {
    $id_column = 'comment_id';
    $id_value = $comment_id;
}

try {
    $stmt = $pdo->prepare("SELECT vote_type FROM Votes WHERE user_id = ? AND {$id_column} = ?");
    $stmt->execute([$user_id, $id_value]);
    $existing_vote = $stmt->fetchColumn();

    if ($existing_vote === false) {
        $stmt = $pdo->prepare("INSERT INTO Votes (user_id, {$id_column}, vote_type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $id_value, $vote_type]);
    } elseif ((int)$existing_vote === $vote_type) {
        $stmt = $pdo->prepare("DELETE FROM Votes WHERE user_id = ? AND {$id_column} = ?");
        $stmt->execute([$user_id, $id_value]);
    } else {
        $stmt = $pdo->prepare("UPDATE Votes SET vote_type = ? WHERE user_id = ? AND {$id_column} = ?");
        $stmt->execute([$vote_type, $user_id, $id_value]);
    }

    $upvote_stmt = $pdo->prepare("SELECT COUNT(*) FROM Votes WHERE {$id_column} = ? AND vote_type = 1");
    $upvote_stmt->execute([$id_value]);
    $new_upvote_count = $upvote_stmt->fetchColumn();

    $downvote_stmt = $pdo->prepare("SELECT COUNT(*) FROM Votes WHERE {$id_column} = ? AND vote_type = -1");
    $downvote_stmt->execute([$id_value]);
    $new_downvote_count = $downvote_stmt->fetchColumn();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'new_upvote_count' => $new_upvote_count,
        'new_downvote_count' => $new_downvote_count
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
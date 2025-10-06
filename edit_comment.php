<?php
$page_title = "Edit Comment";
include 'header.php';
// db_connect is included in header.php

// Security check: Must be logged in
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Check if a comment ID is provided
if (!isset($_GET['id'])) {
    die("Error: No comment ID specified.");
}
$comment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch the comment to get its data and check permissions
$stmt = $pdo->prepare("SELECT * FROM Comments WHERE comment_id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

// Permission check: must be the comment owner OR an admin
if (!$comment || ($comment['user_id'] != $user_id && !$is_admin)) {
    die("Error: You do not have permission to edit this comment.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body']);
    
    if (empty($body)) {
        $error_message = "Comment body cannot be empty.";
    } else {
        $sql = "UPDATE Comments SET body = ? WHERE comment_id = ?";
        $stmt = $pdo->prepare($sql);
        // Execute and redirect back to the post page
        if ($stmt->execute([$body, $comment_id])) {
            header('Location: post.php?id=' . $comment['post_id'] . '&status=comment_edited&t=' . time());
            exit();
        } else {
            $error_message = "There was an error updating your comment.";
        }
    }
}
?>

<div class="form-container">
    <h2>Edit Comment</h2>
    <?php if (!empty($error_message)): ?><div class="error-banner"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
    
    <form action="edit_comment.php?id=<?= $comment_id ?>" method="POST">
        <div class="form-group">
            <label for="body">Comment Text</label>
            <textarea id="body" name="body" rows="8" required><?= htmlspecialchars($comment['body']) ?></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 15px;">
            <a href="post.php?id=<?= $comment['post_id'] ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
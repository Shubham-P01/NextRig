<?php
$page_title = "Create a New Post";
include 'header.php';
// db_connect.php is already included in header.php now

if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// BAN CHECK: If banned, kill the page with a message
if ($is_banned) {
    die('<div class="form-container error-banner">Your account is currently banned and you cannot create new posts.</div>');
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $user_id = $_SESSION['user_id'];
    if (empty($title)) {
        $error_message = "A title is required to create a post.";
    } else {
        $sql = "INSERT INTO Posts (user_id, title, body) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$user_id, $title, $body])) {
            header('Location: forum.php');
            exit();
        } else {
            $error_message = "Failed to create the post.";
        }
    }
}
?>

<div class="form-container">
    <h2>Create a New Post</h2>
    <p>Share your thoughts with the NextRig community.</p>
    <?php if ($error_message): ?><div class="error-banner"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
    <form action="create_post.php" method="POST">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" placeholder="e.g., Is the RTX 4060 still worth it in 2025?" required>
        </div>
        <div class="form-group">
            <label for="body">Body (Optional)</label>
            <textarea id="body" name="body" rows="8" placeholder="Share more details here..."></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 15px;">
             <a href="forum.php" class="btn btn-secondary">Cancel</a>
             <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
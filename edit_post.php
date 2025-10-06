<?php
$page_title = "Edit Post";
include 'header.php';
// db_connect is included in header.php

if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

if ($is_banned) {
    die('<div class="form-container error-banner">Your account is currently banned and you cannot edit posts.</div>');
}

if (!isset($_GET['id'])) {
    die("Error: No post ID specified.");
}
$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$error_message = '';

$stmt = $pdo->prepare("SELECT * FROM Posts WHERE post_id = ?");
$stmt->execute([$post_id]);
$post_data = $stmt->fetch(PDO::FETCH_ASSOC);

// MODIFIED: Permission check now allows admins
if (!$post_data || ($post_data['user_id'] != $user_id && !$is_admin)) {
    die("Error: You do not have permission to edit this post.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    
    if (empty($title)) {
        $error_message = "Title cannot be empty.";
    } else {
        // MODIFIED: Admins can update any post, users can only update their own
        if ($is_admin) {
            $sql = "UPDATE Posts SET title = ?, body = ? WHERE post_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $body, $post_id]);
        } else {
            $sql = "UPDATE Posts SET title = ?, body = ? WHERE post_id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $body, $post_id, $user_id]);
        }
        
        header('Location: forum.php?status=post_edited&t=' . time());
        exit();
    }
}
?>

<div class="form-container">
    <h2>Edit Your Post</h2>
    <?php if (!empty($error_message)): ?><div class="error-banner"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
    <form action="edit_post.php?id=<?= $post_id ?>" method="POST">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($post_data['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="body">Body (Optional)</label>
            <textarea id="body" name="body" rows="8"><?= htmlspecialchars($post_data['body']) ?></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 15px;">
            <a href="forum.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
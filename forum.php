<?php
$page_title = "Community Forum";
include 'header.php';
// db_connect is included in header.php

$sql = "
    SELECT p.post_id, p.title, p.user_id, p.created_at, u.username,
           (SELECT COUNT(*) FROM Comments WHERE post_id = p.post_id AND deleted_at IS NULL) as comment_count
    FROM Posts p
    JOIN Users u ON p.user_id = u.user_id
    WHERE p.deleted_at IS NULL
    ORDER BY p.created_at DESC";
$posts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h1>Community Forum</h1>
        <p>Discuss builds, ask questions, and share deals with other PC enthusiasts.</p>
    </div>
    <a href="create_post.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Post</a>
</div>

<div class="forum-feed">
    <?php if (empty($posts)): ?>
        <p>No posts yet. Be the first to start a discussion!</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <article class="forum-post-card">
            <div class="post-avatar"></div> 
            <div class="post-content">
                <div class="post-meta">
                    Posted by <strong><?= htmlspecialchars($post['username']) ?></strong>
                    <span>· <?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                </div>
                <h2 class="post-title">
                    <a href="post.php?id=<?= $post['post_id'] ?>">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h2>
                <div class="post-stats">
                    <div class="stat-item"><i class="fas fa-comment-alt"></i> <?= $post['comment_count'] ?> Comments</div>
                    <?php 
                        $user_is_owner = ($is_logged_in && $_SESSION['user_id'] == $post['user_id']);
                        if ($user_is_owner || $is_admin):
                    ?>
                    <div class="owner-actions">
                        <a href="edit_post.php?id=<?= $post['post_id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
                        <form action="delete_post.php" method="POST" onsubmit="return confirm('Are you sure?')">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <button type="submit"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
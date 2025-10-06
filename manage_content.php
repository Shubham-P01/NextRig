<?php
$page_title = "Manage Content";
include 'header.php';

// Security Check
if (!$is_admin) {
    header('Location: forum.php');
    exit();
}

// Fetch all posts (including soft-deleted ones)
$posts_stmt = $pdo->query("
    SELECT p.post_id, p.title, p.deleted_at, u.username 
    FROM Posts p 
    JOIN Users u ON p.user_id = u.user_id 
    ORDER BY p.created_at DESC
");
$posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all comments (including soft-deleted ones)
$comments_stmt = $pdo->query("
    SELECT c.comment_id, c.body, c.post_id, c.deleted_at, u.username 
    FROM Comments c 
    JOIN Users u ON c.user_id = u.user_id 
    ORDER BY c.created_at DESC
");
$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h1>Content Management</h1>
        <p>View, restore, and permanently delete posts and comments.</p>
    </div>
</div>

<div class="admin-card" style="margin-bottom: 30px;">
    <h3>All Posts</h3>
    <div class="admin-table-container">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Deleted On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): 
                    $is_deleted = !is_null($post['deleted_at']);
                ?>
                    <tr class="<?= $is_deleted ? 'deleted-row' : '' ?>">
                        <td><a href="post.php?id=<?= $post['post_id'] ?>" title="View Post"><?= htmlspecialchars($post['title']) ?></a></td>
                        <td><?= htmlspecialchars($post['username']) ?></td>
                        <td><?= $is_deleted ? 'Deleted' : 'Active' ?></td>
                        <td><?= $is_deleted ? date('M j, Y', strtotime($post['deleted_at'])) : '-' ?></td>
                        <td class="actions-cell">
                            <?php if ($is_deleted): ?>
                                <form action="restore_post.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                    <button type="submit" class="action-btn-icon" title="Restore Post"><i class="fas fa-undo"></i></button>
                                </form>
                                <form action="hard_delete_post.php" method="POST" onsubmit="return confirm('PERMANENTLY DELETE? This cannot be undone.');" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                    <button type="submit" class="action-btn-icon ban-btn" title="Permanently Delete"><i class="fas fa-times-circle"></i></button>
                                </form>
                            <?php else: ?>
                                <form action="delete_post.php" method="POST" onsubmit="return confirm('Are you sure you want to soft-delete this post?');" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                    <button type="submit" class="action-btn-icon" title="Soft Delete Post"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card">
    <h3>All Comments</h3>
    <div class="admin-table-container">
        <table>
            <thead>
                <tr>
                    <th>Comment</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Deleted On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): 
                    $is_deleted = !is_null($comment['deleted_at']);
                ?>
                    <tr class="<?= $is_deleted ? 'deleted-row' : '' ?>">
                        <td><a href="post.php?id=<?= $comment['post_id'] ?>" title="View Context"><?= htmlspecialchars(substr($comment['body'], 0, 70)) . (strlen($comment['body']) > 70 ? '...' : '') ?></a></td>
                        <td><?= htmlspecialchars($comment['username']) ?></td>
                        <td><?= $is_deleted ? 'Deleted' : 'Active' ?></td>
                        <td><?= $is_deleted ? date('M j, Y', strtotime($comment['deleted_at'])) : '-' ?></td>
                        <td class="actions-cell">
                            <?php if ($is_deleted): ?>
                                <form action="restore_comment.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                    <button type="submit" class="action-btn-icon" title="Restore Comment"><i class="fas fa-undo"></i></button>
                                </form>
                                <form action="hard_delete_comment.php" method="POST" onsubmit="return confirm('PERMANENTLY DELETE? This cannot be undone.');" style="display:inline;">
                                    <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                    <button type="submit" class="action-btn-icon ban-btn" title="Permanently Delete"><i class="fas fa-times-circle"></i></button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
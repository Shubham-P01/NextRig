<?php
$page_title = "Content Analytics";
include 'header.php';

// Security Check
if (!$is_admin) {
    header('Location: index.php');
    exit();
}

// Fetch Top 5 Most Active Posts (by comment count)
$most_active_posts = $pdo->query("
    SELECT p.post_id, p.title, COUNT(c.comment_id) AS comment_count
    FROM Posts p
    LEFT JOIN Comments c ON p.post_id = c.post_id
    WHERE p.deleted_at IS NULL
    GROUP BY p.post_id, p.title
    ORDER BY comment_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Top 5 Most Engaged Users (by total contributions)
$most_engaged_users = $pdo->query("
    SELECT u.username, COUNT(*) AS contribution_count
    FROM (
        SELECT user_id FROM Posts WHERE deleted_at IS NULL
        UNION ALL
        SELECT user_id FROM Comments WHERE deleted_at IS NULL
    ) AS contributions
    JOIN Users u ON contributions.user_id = u.user_id
    GROUP BY u.user_id, u.username
    ORDER BY contribution_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h1>Content Analytics</h1>
        <p>Insights into your community's most popular content and contributors.</p>
    </div>
</div>

<div class="admin-panel-layout">
    <div class="admin-card">
        <h3>Top 5 Most Active Posts</h3>
        <div class="admin-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Post Title</th>
                        <th>Comment Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($most_active_posts as $post): ?>
                        <tr>
                            <td><a href="post.php?id=<?= $post['post_id'] ?>" target="_blank"><?= htmlspecialchars($post['title']) ?></a></td>
                            <td><?= $post['comment_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <h3>Top 5 Most Engaged Users</h3>
        <div class="admin-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Total Contributions (Posts + Comments)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($most_engaged_users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= $user['contribution_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
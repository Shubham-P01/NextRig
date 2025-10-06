<?php
$page_title = "Manage Reports";
include 'header.php';

// Security Check
if (!$is_admin) {
    header('Location: forum.php');
    exit();
}

// Fetch all open reports, joining with user and content tables to get details
$stmt = $pdo->query("
    SELECT 
        r.report_id, r.reason, r.created_at, r.post_id, r.comment_id,
        reporter.username AS reporter_username,
        p.title AS post_title,
        c.body AS comment_body
    FROM reports AS r
    JOIN Users AS reporter ON r.reporter_user_id = reporter.user_id
    LEFT JOIN Posts AS p ON r.post_id = p.post_id
    LEFT JOIN Comments AS c ON r.comment_id = c.comment_id
    WHERE r.status = 'open'
    ORDER BY r.created_at ASC
");
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h1>Manage Reports</h1>
        <p>Review and take action on user-submitted reports.</p>
    </div>
</div>

<div class="admin-table-container">
    <table>
        <thead>
            <tr>
                <th>Reported Content</th>
                <th>Reason</th>
                <th>Reporter</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">There are no open reports.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td class="report-content-cell">
                            <?php if ($report['post_id']): ?>
                                <a href="post.php?id=<?= $report['post_id'] ?>" target="_blank">
                                    <strong>Post:</strong> <?= htmlspecialchars($report['post_title']) ?>
                                </a>
                            <?php elseif ($report['comment_id']): ?>
                                <?php 
                                    // Find which post this comment belongs to for the link
                                    $comment_post_stmt = $pdo->prepare("SELECT post_id FROM Comments WHERE comment_id = ?");
                                    $comment_post_stmt->execute([$report['comment_id']]);
                                    $comment_post_id = $comment_post_stmt->fetchColumn();
                                ?>
                                <a href="post.php?id=<?= $comment_post_id ?>#comment-<?= $report['comment_id'] ?>" target="_blank">
                                    <strong>Comment:</strong> "<?= htmlspecialchars(substr($report['comment_body'], 0, 50)) ?>..."
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($report['reason']) ?></td>
                        <td><?= htmlspecialchars($report['reporter_username']) ?></td>
                        <td><?= date('M j, Y', strtotime($report['created_at'])) ?></td>
                        <td class="actions-cell">
                            <form action="handle_report.php" method="POST" onsubmit="return confirm('Are you sure you want to dismiss this report?');" style="display:inline;">
                                <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                <input type="hidden" name="action" value="dismiss">
                                <button type="submit" class="action-btn-icon" title="Dismiss Report"><i class="fas fa-check"></i></button>
                            </form>
                            <form action="handle_report.php" method="POST" onsubmit="return confirm('This will soft-delete the content and close the report. Proceed?');" style="display:inline;">
                                <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                <input type="hidden" name="action" value="delete_content">
                                <button type="submit" class="action-btn-icon ban-btn" title="Delete Content & Close Report"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
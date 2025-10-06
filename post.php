<?php
include 'header.php';
// db_connect.php is already included in header.php now

if (!isset($_GET['id'])) { die("No post ID specified."); }
$post_id = $_GET['id'];
$current_user_id = $_SESSION['user_id'] ?? null;

// Check if user is banned before processing a new comment
if ($is_logged_in && !$is_banned && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_body'])) {
    $comment_body = trim($_POST['comment_body']);
    $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (!empty($comment_body)) {
        $stmt = $pdo->prepare("INSERT INTO Comments (post_id, user_id, body, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $comment_body, $parent_id]);
        header("Location: post.php?id=" . $post_id);
        exit();
    }
}

// Fetch the main post
$stmt = $pdo->prepare("
    SELECT p.title, p.body, p.created_at, u.username,
           (SELECT COUNT(*) FROM Votes WHERE post_id = p.post_id AND vote_type = 1) as upvote_count,
           (SELECT COUNT(*) FROM Votes WHERE post_id = p.post_id AND vote_type = -1) as downvote_count,
           (SELECT vote_type FROM Votes WHERE post_id = :post_id AND user_id = :user_id) as user_vote
    FROM Posts p JOIN Users u ON p.user_id = u.user_id
    WHERE p.post_id = :post_id AND p.deleted_at IS NULL
");
$stmt->execute([':post_id' => $post_id, ':user_id' => $current_user_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { die("Post not found."); }

// Fetches ALL comments (including deleted ones) to preserve thread structure
$comments_stmt = $pdo->prepare("
    SELECT c.comment_id, c.parent_id, c.user_id, c.body, c.created_at, c.deleted_at, u.username,
           (SELECT COUNT(*) FROM Votes WHERE comment_id = c.comment_id AND vote_type = 1) as upvote_count,
           (SELECT COUNT(*) FROM Votes WHERE comment_id = c.comment_id AND vote_type = -1) as downvote_count,
           (SELECT vote_type FROM Votes WHERE comment_id = c.comment_id AND user_id = :user_id) as user_vote
    FROM Comments c JOIN Users u ON c.user_id = u.user_id
    WHERE c.post_id = :post_id
    ORDER BY c.created_at ASC
");
$comments_stmt->execute([':post_id' => $post_id, ':user_id' => $current_user_id]);
$comments_flat = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build a comment tree from the flat list
$comments_by_id = [];
foreach ($comments_flat as $comment) {
    $comments_by_id[$comment['comment_id']] = $comment;
    $comments_by_id[$comment['comment_id']]['children'] = [];
}
$comment_tree = [];
foreach ($comments_by_id as $comment_id => &$comment) {
    if ($comment['parent_id'] && isset($comments_by_id[$comment['parent_id']])) {
        $comments_by_id[$comment['parent_id']]['children'][] = &$comment;
    } else {
        $comment_tree[] = &$comment;
    }
}
unset($comment);

$page_title = $post['title'];
$post_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$post_title_encoded = urlencode($post['title']);

// Recursive function with all features, including the new edit button
function display_comments($comments, $is_admin, $is_logged_in, $is_banned, $current_user_id, $post_id) {
    foreach ($comments as $comment) {
        $user_owns_comment = ($is_logged_in && $current_user_id == $comment['user_id']);
        $has_children = !empty($comment['children']);
        $is_deleted = !is_null($comment['deleted_at']);

        echo '<div class="comment-thread">';
        echo '  <div class="comment-card ' . ($comment['parent_id'] ? 'is-reply' : '') . '">';
        echo '    <div class="post-avatar"></div>';
        echo '    <div class="comment-content">';
        
        if ($is_deleted) {
            echo '<p class="deleted-comment-text">[This comment has been deleted]</p>';
        } else {
            echo '        <div class="comment-header">';
            echo '            <span class="post-meta"><strong>' . htmlspecialchars($comment['username']) . '</strong> · ' . date('M j, Y', strtotime($comment['created_at'])) . '</span>';
            echo '            <div class="comment-mod-actions">';
            if ($user_owns_comment || $is_admin) {
                echo '                <a href="edit_comment.php?id=' . $comment['comment_id'] . '" class="edit-comment-btn" title="Edit Comment"><i class="fas fa-edit"></i></a>';
                echo '                <form action="delete_comment.php" method="POST" onsubmit="return confirm(\'Are you sure?\');" class="delete-comment-form">';
                echo '                    <input type="hidden" name="comment_id" value="' . $comment['comment_id'] . '">';
                echo '                    <input type="hidden" name="post_id" value="' . $post_id . '">';
                echo '                    <button type="submit" class="delete-comment-btn" title="Delete Comment"><i class="fas fa-trash"></i></button>';
                echo '                </form>';
            }
            echo '            </div>';
            echo '        </div>';
            echo '        <p>' . nl2br(htmlspecialchars($comment['body'])) . '</p>';
        }
        
        echo '        <div class="comment-actions" data-comment-id="' . $comment['comment_id'] . '">';
        if (!$is_deleted) {
            echo '            <div class="vote-group">';
            echo '                <button class="vote-btn upvote ' . ($comment['user_vote'] == 1 ? 'active' : '') . '" data-vote-type="1"><i class="fas fa-thumbs-up"></i></button>';
            echo '                <span class="vote-count upvote-count">' . htmlspecialchars($comment['upvote_count']) . '</span>';
            echo '            </div>';
            echo '            <div class="vote-group">';
            echo '                <button class="vote-btn downvote ' . ($comment['user_vote'] == -1 ? 'active' : '') . '" data-vote-type="-1"><i class="fas fa-thumbs-down"></i></button>';
            echo '                <span class="vote-count downvote-count">' . htmlspecialchars($comment['downvote_count']) . '</span>';
            echo '            </div>';
            if($is_logged_in && !$is_banned) {
                echo '            <button class="reply-btn"><i class="fas fa-reply"></i> Reply</button>';
            }
            if($is_logged_in) {
                echo '            <button class="report-btn open-report-modal-btn" data-comment-id="' . $comment['comment_id'] . '"><i class="fas fa-flag"></i> Report</button>';
            }
        }
        if($has_children) {
            $reply_count = count($comment['children']);
            echo '            <button class="toggle-replies-btn" data-reply-count="' . $reply_count . '"><i class="fas fa-comments"></i> View ' . $reply_count . ' ' . ($reply_count > 1 ? 'Replies' : 'Reply') . '</button>';
        }
        echo '        </div>';

        if(!$is_deleted && $is_logged_in && !$is_banned) {
            echo '        <div class="reply-form-container" style="display:none;">';
            echo '            <form action="post.php?id=' . $post_id . '" method="POST">';
            echo '                <input type="hidden" name="parent_id" value="' . $comment['comment_id'] . '">';
            echo '                <textarea name="comment_body" placeholder="Write a reply..." rows="3" required></textarea>';
            echo '                <div class="form-actions"><button type="submit" class="btn btn-primary">Post Reply</button></div>';
            echo '            </form>';
            echo '        </div>';
        }

        echo '    </div>';
        echo '  </div>';

        if ($has_children) {
            echo '<div class="replies-container" style="display:none;">';
            display_comments($comment['children'], $is_admin, $is_logged_in, $is_banned, $current_user_id, $post_id);
            echo '</div>';
        }
        echo '</div>';
    }
}
?>

<a href="forum.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Forum</a>
<div class="single-post-card">
    <div class="single-post-header">
         <div class="post-avatar"></div>
         <div>
             <span class="post-meta">Posted by <strong><?= htmlspecialchars($post['username']) ?></strong> · <?= date('M j, Y', strtotime($post['created_at'])) ?></span>
         </div>
    </div>
    <h1 class="single-post-title"><?= htmlspecialchars($post['title']) ?></h1>
    <?php if (!empty($post['body'])): ?>
    <div class="single-post-body">
        <p><?= nl2br(htmlspecialchars($post['body'])) ?></p>
    </div>
    <?php endif; ?>
    <div class="single-post-actions" data-post-id="<?= $post_id ?>">
        <div class="vote-buttons">
            <div class="vote-group">
                <button class="vote-btn upvote <?= ($post['user_vote'] == 1) ? 'active' : '' ?>" data-vote-type="1" title="Upvote"><i class="fas fa-thumbs-up"></i></button>
                <span class="vote-count upvote-count"><?= htmlspecialchars($post['upvote_count']) ?></span>
            </div>
            <div class="vote-group">
                <button class="vote-btn downvote <?= ($post['user_vote'] == -1) ? 'active' : '' ?>" data-vote-type="-1" title="Downvote"><i class="fas fa-thumbs-down"></i></button>
                <span class="vote-count downvote-count"><?= htmlspecialchars($post['downvote_count']) ?></span>
            </div>
        </div>
        <a href="#comment-form" class="action-btn"><i class="fas fa-comment"></i><span><?= count($comments_flat) ?> Comments</span></a>
        <button id="open-share-modal-btn" class="action-btn"><i class="fas fa-share"></i><span>Share</span></button>
        <?php if ($is_logged_in): ?>
            <button class="action-btn open-report-modal-btn" data-post-id="<?= $post_id ?>"><i class="fas fa-flag"></i><span>Report</span></button>
        <?php endif; ?>
    </div>
</div>

<div class="comments-section">
    <div id="comment-form" class="comment-form-card">
        <h4>Leave a Comment</h4>
        <?php if ($is_banned): ?>
            <div class="error-banner">Your account is banned. You cannot leave comments.</div>
        <?php elseif ($is_logged_in): ?>
             <form action="post.php?id=<?= $post_id ?>" method="POST">
                 <textarea name="comment_body" placeholder="Write your thoughts..." rows="4" required></textarea>
                 <div class="form-actions">
                     <button type="submit" class="btn btn-primary">Post Comment</button>
                 </div>
             </form>
         <?php else: ?>
             <p><a href="login.php" style="color: var(--accent-purple);">Log in</a> or <a href="signup.php" style="color: var(--accent-purple);">sign up</a> to leave a comment.</p>
         <?php endif; ?>
    </div>
    
    <h3><?= count($comments_flat) ?> Comments</h3>
    <?php
    if (!empty($comment_tree)) {
        display_comments($comment_tree, $is_admin, $is_logged_in, $is_banned, $current_user_id, $post_id);
    }
    ?>
</div>

<div id="share-modal" class="share-modal-overlay">
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h3>Share Post</h3>
            <button id="share-modal-close-btn" class="share-modal-close-btn">&times;</button>
        </div>
        <div class="share-modal-body">
            <p>Copy the link below or share to one of these platforms.</p>
            <div class="link-container">
                <input id="post-url-input" type="text" value="<?= htmlspecialchars($post_url) ?>" readonly>
                <button id="copy-link-btn">Copy</button>
            </div>
            <div class="social-links">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($post_url) ?>" target="_blank" title="Share on Facebook"><i class="fab fa-facebook"></i></a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($post_url) ?>&title=<?= $post_title_encoded ?>" target="_blank" title="Share on LinkedIn"><i class="fab fa-linkedin"></i></a>
                <a href="https://api.whatsapp.com/send?text=<?= $post_title_encoded ?>%20<?= urlencode($post_url) ?>" target="_blank" title="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>
                <a href="mailto:?subject=<?= $post_title_encoded ?>&body=Check%20out%20this%20post:%20<?= urlencode($post_url) ?>" title="Share via Email"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
    </div>
</div>

<div id="report-modal" class="share-modal-overlay">
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h3>Report Content</h3>
            <button id="report-modal-close-btn" class="share-modal-close-btn">&times;</button>
        </div>
        <form id="report-form">
            <div class="form-group">
                <label for="reason">Please state your reason for reporting this content:</label>
                <textarea id="reason" name="reason" rows="4" required></textarea>
                <input type="hidden" name="post_id" id="report-post-id">
                <input type="hidden" name="comment_id" id="report-comment-id">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 15px;">
                <button type="button" id="report-modal-cancel-btn" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background-color: #d32f2f;">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Universal vote script
    document.querySelectorAll('.vote-btn').forEach(button => {
        button.addEventListener('click', function () {
            <?php if ($is_banned): ?>
                alert("Your account is banned. You cannot vote.");
                return;
            <?php endif; ?>
            
            const voteType = this.dataset.voteType;
            const actionsContainer = this.closest('[data-post-id], [data-comment-id]');
            const postId = actionsContainer.dataset.postId;
            const commentId = actionsContainer.dataset.commentId;

            if (!postId && !commentId) return;

            <?php if (!$is_logged_in): ?>
                window.location.href = 'login.php'; return;
            <?php endif; ?>

            let body = `vote_type=${voteType}`;
            if (postId) {
                body += `&post_id=${postId}`;
            } else {
                body += `&comment_id=${commentId}`;
            }

            fetch('vote.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
            .then(response => response.json()).then(data => {
                if (data.success) {
                    actionsContainer.querySelector('.upvote-count').textContent = data.new_upvote_count;
                    actionsContainer.querySelector('.downvote-count').textContent = data.new_downvote_count;
                    const upvoteBtn = actionsContainer.querySelector('.vote-btn.upvote');
                    const downvoteBtn = actionsContainer.querySelector('.vote-btn.downvote');
                    if (voteType == 1) { upvoteBtn.classList.toggle('active'); downvoteBtn.classList.remove('active'); } 
                    else if (voteType == -1) { downvoteBtn.classList.toggle('active'); upvoteBtn.classList.remove('active'); }
                } else if(data.error) {
                    alert(data.error);
                }
            });
        });
    });
    
    // Toggle reply forms script
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commentContent = this.closest('.comment-content');
            const replyFormContainer = commentContent.querySelector('.reply-form-container');
            if (replyFormContainer) {
                replyFormContainer.style.display = (replyFormContainer.style.display === 'none') ? 'block' : 'none';
            }
        });
    });

    // Toggle replies visibility script
    document.querySelectorAll('.toggle-replies-btn').forEach(button => {
        button.addEventListener('click', function() {
            const threadContainer = this.closest('.comment-thread');
            const repliesContainer = threadContainer.querySelector('.replies-container');
            if (repliesContainer) {
                const isHidden = repliesContainer.style.display === 'none';
                if (isHidden) {
                    repliesContainer.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-minus-circle"></i> Hide Replies';
                } else {
                    repliesContainer.style.display = 'none';
                    const count = this.dataset.replyCount;
                    this.innerHTML = `<i class="fas fa-comments"></i> View ${count} ${count > 1 ? 'Replies' : 'Reply'}`;
                }
            }
        });
    });

    // Share modal script
    const shareModal = document.getElementById('share-modal');
    const openModalBtn = document.getElementById('open-share-modal-btn');
    const closeModalBtn = document.getElementById('share-modal-close-btn');
    const copyLinkBtn = document.getElementById('copy-link-btn');
    const postUrlInput = document.getElementById('post-url-input');

    if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            if(shareModal) shareModal.style.display = 'flex';
        });
    }
    const closeShareModal = () => { if(shareModal) shareModal.style.display = 'none'; };
    if(closeModalBtn) closeModalBtn.addEventListener('click', closeShareModal);
    if (shareModal) {
        shareModal.addEventListener('click', (event) => {
            if (event.target === shareModal) { closeShareModal(); }
        });
    }
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', () => {
            postUrlInput.select();
            document.execCommand('copy');
            copyLinkBtn.textContent = 'Copied!';
            setTimeout(() => { copyLinkBtn.textContent = 'Copy'; }, 2000);
        });
    }

    // Report Modal Script
    const reportModal = document.getElementById('report-modal');
    const openReportModalBtns = document.querySelectorAll('.open-report-modal-btn');
    const closeReportModalBtn = document.getElementById('report-modal-close-btn');
    const cancelReportModalBtn = document.getElementById('report-modal-cancel-btn');
    const reportForm = document.getElementById('report-form');
    const reportPostIdInput = document.getElementById('report-post-id');
    const reportCommentIdInput = document.getElementById('report-comment-id');

    const openReportModal = (e) => {
        const postId = e.currentTarget.dataset.postId;
        const commentId = e.currentTarget.dataset.commentId;
        reportForm.reset();
        reportPostIdInput.value = '';
        reportCommentIdInput.value = '';
        if (postId) {
            reportPostIdInput.value = postId;
        } else if (commentId) {
            reportCommentIdInput.value = commentId;
        }
        if (reportModal) reportModal.style.display = 'flex';
    };
    const closeReportModal = () => { if (reportModal) reportModal.style.display = 'none'; };
    openReportModalBtns.forEach(btn => btn.addEventListener('click', openReportModal));
    if (closeReportModalBtn) closeReportModalBtn.addEventListener('click', closeReportModal);
    if (cancelReportModalBtn) cancelReportModalBtn.addEventListener('click', closeReportModal);
    if (reportModal) {
        reportModal.addEventListener('click', (event) => {
            if (event.target === reportModal) { closeReportModal(); }
        });
    }
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('submit_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeReportModal();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your report.');
            });
        });
    }
});
</script>

<?php include 'footer.php'; ?>
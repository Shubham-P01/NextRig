<?php
$page_title = "Manage Users";
include 'header.php';
// db_connect is included in header.php

// Security Check
if (!$is_admin) {
    header('Location: forum.php');
    exit();
}

$stmt = $pdo->prepare("SELECT user_id, username, email, role, member_since, banned_until FROM Users ORDER BY user_id ASC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h1>User Management</h1>
        <p>View and manage all registered users on NextRig.</p>
    </div>
</div>

<div class="admin-table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): 
                $is_banned = !is_null($user['banned_until']) && strtotime($user['banned_until']) > time();
            ?>
                <tr class="<?= $is_banned ? 'banned-user' : '' ?>">
                    <td><?= htmlspecialchars($user['user_id']) ?></td>
                    <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="role-tag role-<?= htmlspecialchars($user['role']) ?>"><?= htmlspecialchars($user['role']) ?></span>
                    </td>
                    <td><?= $is_banned ? 'Banned' : 'Active' ?></td>
                    <td class="actions-cell">
                        <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="action-btn-icon" title="Edit User"><i class="fas fa-edit"></i></a>
                        <?php if ($is_banned): ?>
                            <form action="unban_user.php" method="POST" onsubmit="return confirm('Are you sure you want to unban this user?');">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <button type="submit" class="action-btn-icon" title="Unban User"><i class="fas fa-check-circle"></i></button>
                            </form>
                        <?php else: ?>
                            <button class="action-btn-icon ban-btn open-ban-modal-btn" 
                                    data-user-id="<?= $user['user_id'] ?>" 
                                    data-username="<?= htmlspecialchars($user['username']) ?>" 
                                    title="Ban User">
                                <i class="fas fa-ban"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="ban-modal" class="share-modal-overlay" style="display: none;">
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h3 id="ban-modal-title">Ban User</h3>
            <button id="ban-modal-close-btn" class="share-modal-close-btn">&times;</button>
        </div>
        <form action="ban_user.php" method="POST">
            <div class="form-group">
                <label for="duration">Select Ban Duration</label>
                <select id="duration" name="duration" class="form-group input">
                    <option value="1">1 Hour</option>
                    <option value="24">24 Hours</option>
                    <option value="48">48 Hours</option>
                    <option value="120">120 Hours (5 Days)</option>
                    <option value="permanent">Until I Unban (Permanent)</option>
                </select>
            </div>
            <input type="hidden" id="ban-user-id-input" name="user_id" value="">
            <div style="display: flex; justify-content: flex-end; gap: 15px;">
                <button type="button" id="ban-modal-cancel-btn" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background-color: #d32f2f;">Apply Ban</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const banModal = document.getElementById('ban-modal');
    const openModalBtns = document.querySelectorAll('.open-ban-modal-btn');
    const closeModalBtn = document.getElementById('ban-modal-close-btn');
    const cancelModalBtn = document.getElementById('ban-modal-cancel-btn');
    const banUserIdInput = document.getElementById('ban-user-id-input');
    const banModalTitle = document.getElementById('ban-modal-title');

    openModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const username = this.dataset.username;
            
            banUserIdInput.value = userId;
            banModalTitle.textContent = `Ban User: ${username}`;
            banModal.style.display = 'flex';
        });
    });

    const closeModal = () => {
        banModal.style.display = 'none';
    };

    closeModalBtn.addEventListener('click', closeModal);
    cancelModalBtn.addEventListener('click', closeModal);
    banModal.addEventListener('click', (event) => {
        if (event.target === banModal) {
            closeModal();
        }
    });
});
</script>

<?php include 'footer.php'; ?>
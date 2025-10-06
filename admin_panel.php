<?php
$page_title = "Admin Dashboard";
include 'header.php';

// Security Check
if (!$is_admin) {
    header('Location: forum.php');
    exit();
}

// Fetch stats for the dashboard
$user_count = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
$post_count = $pdo->query("SELECT COUNT(*) FROM Posts WHERE deleted_at IS NULL")->fetchColumn();
$comment_count = $pdo->query("SELECT COUNT(*) FROM Comments WHERE deleted_at IS NULL")->fetchColumn();
?>

<div class="page-header">
    <div>
        <h1>Admin Dashboard</h1>
        <p>A quick overview of your NextRig community.</p>
    </div>
</div>

<div class="stat-cards-container">
    <div class="stat-card">
        <div class="stat-icon" style="color: #6c5ce7;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $user_count ?></span>
            <span class="stat-label">Total Users</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color: #0984e3;">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $post_count ?></span>
            <span class="stat-label">Active Posts</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color: #00b894;">
            <i class="fas fa-comments"></i>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $comment_count ?></span>
            <span class="stat-label">Total Comments</span>
        </div>
    </div>
</div>


<div class="admin-panel-layout">
    <div class="admin-card">
        <h3><i class="fas fa-tachometer-alt"></i> Admin Menu</h3>
        <div class="admin-nav-links">
             <a href="manage_users.php" class="admin-nav-item">
                <i class="fas fa-users-cog"></i>
                <span>Manage Users</span>
            </a>
            <a href="manage_content.php" class="admin-nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Manage Content</span>
            </a>
            <a href="manage_reports.php" class="admin-nav-item">
                <i class="fas fa-flag"></i>
                <span>Manage Reports</span>
            </a>
            <a href="analytics.php" class="admin-nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
        </div>
    </div>

    <div class="admin-card wip-card">
        <h3><i class="fas fa-cogs"></i> More Features Coming Soon</h3>
        <p>The admin panel is actively being developed. New tools and features will be added in future updates to enhance site management.</p>
        <strong>Work in Progress...</strong>
    </div>
</div>

<?php
include 'footer.php';
?>
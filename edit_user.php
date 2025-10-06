<?php
$page_title = "Edit User Role";
include 'header.php';
// db_connect is included in header.php

// Security Check: Must be an admin
if (!$is_admin) {
    header('Location: forum.php');
    exit();
}

// Check if a user ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage_users.php');
    exit();
}
$user_id_to_edit = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];

    // Update only the user's role
    $sql = "UPDATE Users SET role = ? WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$role, $user_id_to_edit]);
    
    // Redirect back to the user list with a cache-busting timestamp
    header('Location: manage_users.php?status=edited&t=' . time());
    exit();
}

// Fetch current user data to pre-fill the form
$stmt = $pdo->prepare("SELECT user_id, username, role FROM Users WHERE user_id = ?");
$stmt->execute([$user_id_to_edit]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: manage_users.php');
    exit();
}
?>

<div class="form-container">
    <h2>Edit Role for: <?= htmlspecialchars($user['username']) ?></h2>
    <p>Change the user's role below.</p>
    
    <form action="edit_user.php?id=<?= $user_id_to_edit ?>" method="POST">
        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" class="form-group input">
                <option value="user" <?= ($user['role'] === 'user') ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px;">
             <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
             <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Role</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
<?php
session_start();
include 'db_connect.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id']) || !isset($_POST['duration'])) {
    header('Location: forum.php');
    exit();
}

$user_id_to_ban = $_POST['user_id'];
$duration = $_POST['duration'];
$admin_user_id = $_SESSION['user_id'];

// Admins cannot ban themselves
if ($user_id_to_ban == $admin_user_id) {
    header('Location: manage_users.php?error=selfban');
    exit();
}

$ban_until_date = null;
switch ($duration) {
    case '1':
    case '24':
    case '48':
    case '120':
        $ban_until_date = date('Y-m-d H:i:s', strtotime("+" . $duration . " hours"));
        break;
    case 'permanent':
        $ban_until_date = '9999-12-31 23:59:59';
        break;
    default:
        header('Location: manage_users.php?error=invalidduration');
        exit();
}

$stmt = $pdo->prepare("UPDATE Users SET banned_until = ? WHERE user_id = ?");
$stmt->execute([$ban_until_date, $user_id_to_ban]);

// MODIFIED: Added cache buster (&t=time())
header('Location: manage_users.php?status=banned&t=' . time());
exit();
?>
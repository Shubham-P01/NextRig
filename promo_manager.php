<?php
session_start();
require 'connection.php'; 

// 🛑 Optional: Protect this page for admin only
// if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) {
//     header("Location: ../login.php");
//     exit();
// }

// ✅ Handle Add Promo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = (float)$_POST['discount_percent'];
    $status = $_POST['status'];

    if ($code && $discount > 0) {
        $stmt = $pdo->prepare("INSERT INTO promo_codes (code, discount_percent, status) VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE discount_percent=?, status=?");
        $stmt->execute([$code, $discount, $status, $discount, $status]);
        $message = "Promo code saved successfully!";
    } else {
        $error = "Please enter valid code and discount.";
    }
}

// ✅ Handle Delete Promo
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM promo_codes WHERE promo_id=?")->execute([$id]);
    header("Location: promo_manager.php");
    exit();
}

// ✅ Fetch All Promos
$promos = $pdo->query("SELECT * FROM promo_codes ORDER BY promo_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Promo Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 24px;
            text-align: center;
        }
        form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
        }
        input, select, button {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-family: inherit;
        }
        input[type="text"], input[type="number"] {
            flex: 1;
            min-width: 150px;
        }
        button {
            background: #1f2937;
            color: white;
            cursor: pointer;
            border: none;
            padding: 10px 16px;
            font-weight: 600;
        }
        button:hover {
            background: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #f3f4f6;
        }
        .status-active {
            color: green;
            font-weight: 600;
        }
        .status-inactive {
            color: red;
            font-weight: 600;
        }
        .delete-btn {
            color: red;
            text-decoration: none;
            font-weight: 600;
        }
        .message {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🎟 Promo Code Manager</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="code" placeholder="Promo Code (e.g. SAVE10)" required>
        <input type="number" step="0.01" name="discount_percent" placeholder="Discount %" required>
        <select name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button type="submit" name="add_promo">Save Promo</button>
    </form>

    <table>
        <tr>
            <th>Sr. No.</th>
            <th>Code</th>
            <th>Discount %</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php $sr_no = 1; foreach ($promos as $promo): ?>
        <tr>
            <td><?= $sr_no++ ?></td>
            <td><?= htmlspecialchars($promo['code']) ?></td>
            <td><?= $promo['discount_percent'] ?>%</td>
            <td class="status-<?= $promo['status'] ?>"><?= ucfirst($promo['status']) ?></td>
            <td><a href="?delete=<?= $promo['promo_id'] ?>" class="delete-btn" onclick="return confirm('Delete this promo?')">Delete</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($promos)): ?>
        <tr><td colspan="5" style="text-align:center;">No promo codes found.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>

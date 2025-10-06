<?php
// filepath: c:\wamp64\www\SSP Project\track_admin.php
session_start();
require 'connection.php';

// -------------------------------------------
// 🔐 Security check (optional)
$is_admin = true; // Replace with actual admin check logic if needed
if (!$is_admin) {
    header('Location: forum.php');
    exit();
}
// -------------------------------------------

// Handle search functionality
$search_query = '';
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search_query = trim($_GET['search']);

    // Only allow numeric order IDs to prevent partial text matches
    if (is_numeric($search_query)) {
        $sql = "SELECT o.*, u.username AS buyer_name, u.email AS buyer_email
                FROM orders o
                JOIN users u ON o.buyer_user_id = u.user_id
                WHERE o.order_id = ?
                ORDER BY o.order_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search_query]);
    } else {
        $orders = [];
    }
} else {
    // Fetch all orders sorted by ascending order date
    $sql = "SELECT o.*, u.username AS buyer_name, u.email AS buyer_email
            FROM orders o
            JOIN users u ON o.buyer_user_id = u.user_id
            ORDER BY o.order_date ASC";
    $stmt = $pdo->query($sql);
}

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextRig Admin Panel - Manage Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* Navbar */
        .navbar {
            background-color: #ffffff;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .navbar .logo {
            font-size: 1.6em;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
        }

        .navbar a {
            color: #555;
            text-decoration: none;
            margin-left: 25px;
            font-size: 1em;
            font-weight: 500;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: #2563eb;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            padding: 30px 40px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #1f2937;
            font-size: 1.9em;
            letter-spacing: -0.5px;
        }

        /* Search Bar */
        .search-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
            gap: 10px;
        }

        .search-bar input {
            padding: 10px 14px;
            width: 300px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            font-size: 1em;
            transition: 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-bar button {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: 0.3s;
        }

        .search-bar button:hover {
            background-color: #1e40af;
            transform: scale(1.03);
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1em;
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85em;
        }

        tr:hover {
            background-color: #f3f4f6;
        }

        /* Status Labels */
        .status-label {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-align: center;
            color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            text-transform: capitalize;
            min-width: 110px;
        }

        .OrderConfirmed {
            background-color: #0ea5e9;
        }

        .Processing {
            background-color: #facc15;
            color: #111827;
        }

        .Shipped {
            background-color: #3b82f6;
        }

        .OutforDelivery {
            background-color: #7c3aed;
        }

        .Delivered {
            background-color: #22c55e;
        }

        .Cancelled {
            background-color: #ef4444;
        }

        /* Buttons & Select */
        button {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: 0.3s;
        }

        button:hover {
            background-color: #1d4ed8;
            transform: scale(1.05);
        }

        select {
            padding: 7px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            font-size: 0.9em;
            background-color: #fff;
            color: #111827;
            transition: border 0.3s;
        }

        select:focus {
            border-color: #2563eb;
            outline: none;
        }

        /* Empty message */
        .no-results {
            text-align: center;
            color: #6b7280;
            margin: 25px 0;
            font-size: 1.05em;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <a href="index.php" class="logo">NextRig Admin</a>
        <div>
            <a href="index.php">View Site</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <!-- Container -->
    <div class="container">
        <h1>Manage Orders</h1>

        <!-- Search Bar -->
        <form class="search-bar" method="GET" action="track_admin.php">
            <input type="text" name="search" placeholder="Enter Order ID" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>

        <?php if (empty($orders)): ?>
            <p class="no-results">No orders found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): 
                    $status = $order['order_status'];
                    $date_column = null;

                    switch ($status) {
                        case 'Processing': $date_column = $order['processing_date']; break;
                        case 'Shipped': $date_column = $order['shipped_date']; break;
                        case 'Out for Delivery': $date_column = $order['out_for_delivery_date']; break;
                        case 'Delivered': $date_column = $order['delivered_date']; break;
                        default: $date_column = $order['order_date']; break;
                    }

                    $last_update = $date_column ? date("D, M j, Y", strtotime($date_column)) : "—";
                ?>
                    <tr>
                        <td><strong>#<?= htmlspecialchars($order['order_id']) ?></strong></td>
                        <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                        <td><?= htmlspecialchars($order['buyer_email']) ?></td>
                        <td><strong>₹<?= number_format($order['total_amount'], 2) ?></strong></td>
                        <td>
                            <span class="status-label <?= str_replace(' ', '', $status) ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
                        <td><?= date("D, M j, Y", strtotime($order['order_date'])) ?></td>
                        <td><?= $last_update ?></td>
                        <td>
                            <form method="GET" action="update_order_status.php" style="display:flex; gap:8px; justify-content:center;">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <select name="status" required>
                                    <?php
                                    $statuses = ['Order Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
                                    foreach ($statuses as $s) {
                                        $selected = ($s === $status) ? 'selected' : '';
                                        echo "<option value=\"$s\" $selected>$s</option>";
                                    }
                                    ?>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>
</html>

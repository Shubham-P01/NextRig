<?php
// filepath: c:\wamp64\www\SSP Project\cancel_order.php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id) {
    die("Invalid order ID.");
}

// Fetch order details
$sql = "SELECT * FROM orders WHERE order_id = ? AND buyer_user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found.");
}

// Fetch order items
$sql_items = "SELECT oi.quantity, oi.price_per_unit, p.name AS product_name, pi.image_url
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_main_image = 1
              WHERE oi.order_id = ?";
$stmt_items = $pdo->prepare($sql_items);
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Calculate total refund amount (excluding shipping charge)
$total_refund = 0;
foreach ($order_items as $item) {
    $total_refund += $item['quantity'] * $item['price_per_unit'];
}
$refund_amount = max(0, $total_refund - 250); // Deduct ₹250 shipping charge

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES, 'UTF-8');

    if (!$reason) {
        die("Please provide a reason for canceling the order.");
    }

    // Update the order status to "Cancelled" and save the reason in the order_notes column
    $sql = "UPDATE orders SET order_status = 'Cancelled', order_notes = ? WHERE order_id = ? AND buyer_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reason, $order_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        header("Location: track_order.php?message=Order cancelled successfully. Refund of ₹" . number_format($refund_amount, 2) . " will be processed within 3 days.");
        exit();
    } else {
        die("Failed to cancel the order or order not found.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Order - NextRig</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; color: #333; }
        .navbar { background-color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .navbar .logo { font-size: 1.5em; font-weight: bold; color: #333; text-decoration: none; }
        .navbar .nav-links { display: flex; align-items: center; }
        .navbar .nav-links a { color: #555; text-decoration: none; margin: 0 15px; font-size: 1em; font-weight: 500; }
        .navbar .nav-links a:hover { color: #007bff; }
        .navbar .nav-icons { display: flex; align-items: center; }
        .navbar .nav-icons a { color: #555; text-decoration: none; margin-left: 15px; font-size: 1.2em; }
        .navbar .nav-icons a:hover { color: #007bff; }
        .navbar .nav-icons svg { margin-right: 5px; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { text-align: center; }
        .order-details, .order-items { margin-top: 20px; }
        .order-items table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .order-items th, .order-items td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .order-items th { background-color: #f4f7f6; }
        .order-items img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        form { margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; }
        .form-group button { padding: 10px 20px; font-size: 1em; border: none; border-radius: 5px; cursor: pointer; background-color: #d9534f; color: white; }
        .refund-note { margin-top: 20px; padding: 10px; background-color: #f4f7f6; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; color: #333; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="index.php" class="logo">NextRig</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="forum.php">Forum</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="nav-icons">
            <a href="cart.php" title="Shopping Cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h1>Cancel Order</h1>
        <div class="order-details">
            <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
            <p><strong>Order Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>
        </div>
        <div class="order-items">
            <h2>Order Items:</h2>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price Per Unit</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($item['image_url'] ?? 'https://placehold.co/60x60/EEE/31343C?text=No+Img') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>"></td>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td>₹<?= htmlspecialchars(number_format($item['price_per_unit'], 2)) ?></td>
                            <td>₹<?= htmlspecialchars(number_format($item['quantity'] * $item['price_per_unit'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="POST" action="cancel_order.php?order_id=<?= htmlspecialchars($order_id) ?>">
            <div class="form-group">
                <label for="reason">Why are you canceling this order?</label>
                <textarea id="reason" name="reason" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <button type="submit">Submit Cancellation</button>
            </div>
        </form>
        <div class="refund-note">
            <p><strong>Refund Policy:</strong></p>
            <p>A refund of ₹<?= number_format($refund_amount, 2) ?> will be processed within 3 days. Please note that the shipping charge of ₹250 is non-refundable.</p>
        </div>
    </div>
</body>
</html>
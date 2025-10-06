<?php
// filepath: c:\wamp64\www\SSP Project\view_receipt.php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

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
$sql_items = "SELECT oi.quantity, oi.price_per_unit, p.name AS product_name
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              WHERE oi.order_id = ?";
$stmt_items = $pdo->prepare($sql_items);
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['quantity'] * $item['price_per_unit'];
}

// Define shipping charges (can be dynamic or fixed)
$shipping_charges = 250; // Example fixed shipping charge

// Calculate total
$total = $subtotal + $shipping_charges;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - NextRig</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { text-align: center; font-size: 1.8em; margin-bottom: 10px; }
        .subheading { text-align: center; font-size: 1em; color: #777; margin-bottom: 20px; }
        .order-details, .order-items, .order-summary { margin-top: 20px; }
        .order-details { display: flex; justify-content: space-between; }
        .order-details div { width: 48%; }
        .order-details p { margin: 5px 0; font-size: 0.9em; }
        .order-details strong { font-size: 1em; }
        .order-items table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .order-items th, .order-items td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .order-items th { background-color: #f4f7f6; font-weight: bold; }
        .order-summary { text-align: right; margin-top: 20px; }
        .order-summary p { margin: 5px 0; font-size: 1.1em; }
        .order-summary strong { font-size: 1.2em; }
        .divider { border-top: 1px solid #ddd; margin: 10px 0; }
        .print-btn { display: block; margin: 20px auto; padding: 10px 20px; background-color: #007bff; color: white; text-align: center; border: none; border-radius: 5px; font-size: 1em; cursor: pointer; transition: background-color 0.3s ease; }
        .print-btn:hover { background-color: #0056b3; }
        .print-btn:active { transform: scale(0.98); }
    </style>
    <script>
        function printReceipt() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>NextRig</h1>
        <p class="subheading">Tax Invoice/Bill of Supply</p>
        <div class="order-details">
            <div>
                <p><strong>Order ID:</strong> #<?= htmlspecialchars($order['order_id']) ?></p>
                <p><strong>Order Date:</strong> <?= htmlspecialchars(date('d M, Y', strtotime($order['order_date']))) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>
                <p><strong>Billed To:</strong></p>
                <p><?= htmlspecialchars($order['billing_name']) ?></p>
                <p><?= htmlspecialchars($order['billing_email']) ?></p>
            </div>
            <div>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                <p><strong>Shipping Address:</strong></p>
                <p><?= htmlspecialchars($order['shipping_address']) ?></p>
            </div>
        </div>
        <div class="order-items">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td>₹<?= htmlspecialchars(number_format($item['price_per_unit'], 2)) ?></td>
                            <td>₹<?= htmlspecialchars(number_format($item['quantity'] * $item['price_per_unit'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="order-summary">
            <p><strong>Subtotal:</strong> ₹<?= number_format($subtotal, 2) ?></p>
            <p><strong>Shipping:</strong> ₹<?= number_format($shipping_charges, 2) ?></p>
            <div class="divider"></div>
            <p><strong>Grand Total:</strong> ₹<?= number_format($total, 2) ?></p>
        </div>
        <button class="print-btn" onclick="printReceipt()">Print Receipt</button>
    </div>
</body>
</html>
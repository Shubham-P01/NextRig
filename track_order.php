<?php
session_start();
require 'connection.php';

$error_message = '';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM orders WHERE buyer_user_id = ? ORDER BY order_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$orders) {
    $error_message = "No orders found for your account.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Your Orders - NextRig</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
body {
    font-family: 'Inter', sans-serif;
    background-color: #f4f7f6;
    margin: 0; padding: 20px;
    color: #333;
}
.navbar {
    background-color: #fff;
    padding: 10px 20px;
    display: flex; justify-content: space-between; align-items: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.navbar .logo { font-size: 1.5em; font-weight: bold; text-decoration: none; color: #333; }
.navbar .nav-links a {
    margin: 0 15px; text-decoration: none; color: #555; font-weight: 500;
}
.navbar .nav-links a:hover { color: #007bff; }
.navbar .nav-icons a { color: #6f42c1; font-size: 1.2em; margin-left: 15px; text-decoration: none; }

.container { max-width: 900px; margin: 0 auto; }
.tracking-header { text-align: center; margin-bottom: 30px; }
.error {
    color: #d9534f; background-color: #f2dede; border: 1px solid #ebccd1;
    padding: 15px; border-radius: 4px; text-align: center;
}
.order-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
}

/* ✅ Centered “Delivered” Stamp */
.delivered-stamp {
    position: absolute;
    top: 50%;
    right: 30%;
    transform: rotate(-12deg) translateY(-50%);
    font-size: 2.2em;
    font-weight: 800;
    color: rgba(40, 167, 69, 0.7);
    border: 5px solid rgba(40, 167, 69, 0.7);
    padding: 15px 35px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 2px;
    background-color: transparent;
    opacity: 0.8;
}

/* ❌ Centered “Cancelled” Stamp */
.cancelled-stamp {
    position: absolute;
    top: 50%;
    right: 30%;
    transform: rotate(-35deg) translateY(-50%);
    font-size: 2.2em;
    font-weight: 800;
    color: rgba(220, 53, 69, 0.65);
    border: 5px solid rgba(220, 53, 69, 0.65);
    padding: 15px 35px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 2px;
    background-color: transparent;
    opacity: 0.8;
}

/* Timeline */
.timeline {
    list-style: none;
    padding: 0;
    margin: 20px 0;
}
.timeline-item {
    display: flex;
    align-items: center;
    position: relative;
    padding-bottom: 30px;
}
.timeline-item:last-child { padding-bottom: 0; }
.timeline-icon {
    width: 40px; height: 40px;
    background: #ddd; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white; margin-right: 20px; z-index: 2;
}
.timeline-item::before {
    content: ''; position: absolute;
    top: 20px; left: 19px;
    width: 2px; height: 100%;
    background: #ddd; z-index: 1;
}
.timeline-item:last-child::before { display: none; }
.timeline-content h4 { margin: 0 0 5px; }
.timeline-content p { margin: 0; font-size: 0.9em; color: #777; }
.timeline-item.completed .timeline-icon { background: #28a745; }
.timeline-item.completed::before { background: #28a745; }
.timeline-item.active .timeline-icon { background: #007bff; }

.delivery-info { text-align: right; }
.delivery-info strong {
    display: block;
    font-size: 1.2em;
    color: #28a745;
}
.product-item img {
    width: 60px; height: 60px;
    border-radius: 4px; object-fit: cover;
}
.action-buttons {
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
}
.view-receipt-btn, .cancel-order-btn {
    padding: 10px 20px; border: none;
    border-radius: 5px; cursor: pointer;
    font-size: 1em; font-weight: 500;
    transition: 0.3s;
}
.view-receipt-btn { background: #007bff; color: #fff; }
.view-receipt-btn:hover { background: #0056b3; transform: scale(1.05); }
.cancel-order-btn { background: #d9534f; color: #fff; }
.cancel-order-btn:hover { background: #c9302c; transform: scale(1.05); }
.note-text { color: #6c757d; font-style: italic; text-align: right; margin-top: 10px; }
</style>
</head>
<body>

<div class="navbar">
    <a href="index.php" class="logo">NextRig</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="shop.php">Shop</a>
        <a href="forum.php">Forum</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact</a>
    </div>
    <div class="nav-icons">
        <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="tracking-header">
        <h1>Track Your Orders</h1>
        <p>Below are all the orders associated with your account.</p>
    </div>

    <?php if ($error_message): ?>
        <p class="error"><?= $error_message ?></p>
    <?php else: ?>
        <?php foreach ($orders as $order):
            $order_date = new DateTime($order['order_date']);
            $current_status = $order['order_status'] ?? 'Order Confirmed';
            $statuses = [
                'Order Confirmed' => ['icon' => 'fas fa-check-circle', 'column' => 'order_date'],
                'Processing' => ['icon' => 'fas fa-cogs', 'column' => 'processing_date'],
                'Shipped' => ['icon' => 'fas fa-shipping-fast', 'column' => 'shipped_date'],
                'Out for Delivery' => ['icon' => 'fas fa-truck', 'column' => 'out_for_delivery_date'],
                'Delivered' => ['icon' => 'fas fa-check-circle', 'column' => 'delivered_date']
            ];
            $status_keys = array_keys($statuses);
            $current_status_index = array_search($current_status, $status_keys);
            if ($current_status_index === false) $current_status_index = 0;
            $estimated_delivery_date = (clone $order_date)->modify('+4 days')->format('l, F j, Y');
        ?>
        <div class="order-card">
            <?php if ($current_status === 'Delivered'): ?>
                <div class="delivered-stamp">DELIVERED</div>
            <?php elseif ($current_status === 'Cancelled'): ?>
                <div class="cancelled-stamp">CANCELLED</div>
            <?php endif; ?>

            <h3>Order #<?= htmlspecialchars($order['order_id']) ?></h3>
            <p>Shipped via: NextRig</p>
            <div class="delivery-info">
                <p>Estimated Delivery</p>
                <strong><?= $current_status === 'Cancelled' ? 'Cancelled' : $estimated_delivery_date ?></strong>
            </div>

            <ul class="timeline">
                <?php foreach ($statuses as $status_key => $status_info):
                    $status_index = array_search($status_key, $status_keys);
                    $class = '';
                    if ($status_index < $current_status_index) $class = 'completed';
                    elseif ($status_index === $current_status_index) $class = 'active';
                    $column_name = $status_info['column'];
                    $date_value = $order[$column_name];
                    $display_date = !empty($date_value)
                        ? (new DateTime($date_value))->format('D, M j, Y')
                        : 'Pending';
                ?>
                <li class="timeline-item <?= $class ?>">
                    <div class="timeline-icon"><i class="<?= $status_info['icon'] ?>"></i></div>
                    <div class="timeline-content">
                        <h4><?= $status_key ?></h4>
                        <p><?= $display_date ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="order-summary">
                <h4>Order Contains:</h4>
                <?php
                $items_sql = "SELECT oi.quantity, oi.price_per_unit, p.name AS product_name, pi.image_url
                              FROM order_items oi
                              JOIN products p ON oi.product_id = p.product_id
                              LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_main_image = 1
                              WHERE oi.order_id = ?";
                $items_stmt = $pdo->prepare($items_sql);
                $items_stmt->execute([$order['order_id']]);
                $order_items = $items_stmt->fetchAll();

                foreach ($order_items as $item): ?>
                <div class="product-item" style="display:flex;align-items:center;margin-bottom:15px;">
                    <img src="<?= htmlspecialchars($item['image_url'] ?? 'https://placehold.co/60x60') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                    <div style="flex:1;margin-left:15px;">
                        <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                        <small>Quantity: <?= $item['quantity'] ?></small>
                    </div>
                    <div style="font-weight:bold;">₹<?= number_format($item['quantity'] * $item['price_per_unit'], 2) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="action-buttons">
                <form method="POST" action="view_receipt.php">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                    <button type="submit" class="view-receipt-btn">View Receipt</button>
                </form>

                <?php if ($current_status === 'Delivered'): ?>
                    <p class="note-text">Once delivered, cannot be cancelled</p>
                <?php elseif ($current_status === 'Cancelled'): ?>
                    <p class="note-text" style="color:#dc3545;">Order has been cancelled</p>
                <?php else: ?>
                    <form method="GET" action="cancel_order.php">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                        <button type="submit" class="cancel-order-btn">Cancel Order</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>

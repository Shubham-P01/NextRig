<?php
session_start();
require 'connection.php';

// Get the order ID from the URL
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id) {
    header("Location: index.php"); 
    exit();
}

// Fetch main order details
$sql = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit();
}

// Fetch order items
$items_sql = "SELECT oi.quantity, oi.price_per_unit, p.name AS product_name
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              WHERE oi.order_id = ?";
$items_stmt = $pdo->prepare($items_sql);
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price_per_unit'] * $item['quantity'];
}
$shipping = 250.00;
$total = $subtotal + $shipping;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - NextRig</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-text: #111827;
            --secondary-text: #6B7280;
            --border-color: #E5E7EB;
            --background-color: #F9FAFB;
            --card-background: #FFFFFF;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--background-color); 
            margin: 0; 
            color: var(--primary-text);
        }
        
        /* --- NAVBAR STYLES --- */
        .navbar {
            background-color: var(--card-background);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 0;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-text);
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            gap: 32px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-link {
            text-decoration: none;
            color: var(--secondary-text);
            font-weight: 500;
        }
        .nav-link:hover {
            color: var(--primary-text);
        }
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .nav-icons a {
            color: var(--primary-text);
            display: flex;
            align-items: center;
        }
        
        /* --- PAGE CONTENT STYLES --- */
        .container {
             max-width: 800px;
             margin: 40px auto;
             padding: 0 20px;
        }
        .success-container { text-align: center; margin-bottom: 20px; }
        .success-container h1 { color: #28a745; margin-bottom: 10px; }
        .receipt-wrapper { background: #fff; padding: 30px; width: 100%; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 8px; box-sizing: border-box; }
        .receipt-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .receipt-header h2 { margin: 0; font-size: 2em; }
        .receipt-header p { margin: 5px 0 0; color: #666; }
        .order-details, .customer-details { display: flex; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 20px;}
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .items-table th { background-color: #f8f8f8; }
        .totals { text-align: right; }
        .totals-table { float: right; width: 40%; min-width: 250px; }
        .totals-table td { padding: 5px; }
        .print-button { display: inline-block; width: auto; min-width: 200px; margin: 10px; padding: 12px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; text-align: center; text-decoration: none; }
        .button-container { text-align: center; margin-top: 20px;}
        
        .tracking-container { background-color: #e9f5ff; border: 1px solid #b3d7ff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-sizing:border-box; text-align: center; }
        .tracking-container.processing { background-color: #fffbe6; border-color: #ffe58f; }
        .tracking-container h2 { margin-top: 0; color: #0056b3; }
        .tracking-container.processing h2 { color: #d46b08; }
        .track-button { display: inline-block; background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px; }

        @media print {
            body { background-color: #fff; padding: 0; }
            .non-printable { display: none; }
            .container { max-width: 100%; margin: 0; padding: 0; }
            .receipt-wrapper { box-shadow: none; border: 1px solid #ccc; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <audio id="success-sound" preload="auto">
        <source src="payment_success.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <header class="navbar non-printable">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">NextRig</a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="shop.php" class="nav-link">Shop</a></li>
                <li><a href="forum.php" class="nav-link">Forum</a></li>
                <li><a href="about.php" class="nav-link">About Us</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
            </ul>
            <div class="nav-icons">
                <a href="#" title="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </a>
                <a href="cart.php" title="Shopping Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                </a>
                <a href="<?= isset($_SESSION['user_id']) ? 'profile.php' : 'login.php' ?>" title="My Account">
                     <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="success-container non-printable">
            <h1><i class="fas fa-check-circle"></i> Payment Successful!</h1>
            <p>Thank you for your order. A copy of your receipt is below.</p>
        </div>
    
        <div class="tracking-container non-printable <?= ($order['order_status'] !== 'Shipped') ? 'processing' : '' ?>">
            <h2><i class="fas fa-box-open"></i> Order Status: <?= htmlspecialchars($order['order_status']) ?></h2>
            <p>You can view detailed tracking information for your order.</p>
            <a href="track_order.php?order_id=<?= htmlspecialchars($order['order_id']) ?>" class="track-button">View Detailed Tracking</a>
        </div>
    
        <div id="receipt" class="receipt-wrapper">
            <div class="receipt-header">
                <h2>NextRig</h2>
                <p>Tax Invoice/Bill of Supply</p>
            </div>
            <div class="order-details">
                <div>
                    <strong>Order ID:</strong> #<?= htmlspecialchars($order['order_id']) ?><br>
                    <strong>Order Date:</strong> <?= date("d M, Y", strtotime($order['order_date'])) ?>
                </div>
                <div>
                    <strong>Payment Method:</strong> <?= strtoupper(htmlspecialchars($order['payment_method'])) ?><br>
                    <?php if (isset($order['payment_id']) && !empty($order['payment_id'])): ?>
                        <strong>Transaction ID:</strong> <?= htmlspecialchars($order['payment_id']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="customer-details">
                <div>
                    <strong>Billed To:</strong><br>
                    <?= htmlspecialchars($order['billing_name']) ?><br>
                    <?= htmlspecialchars($order['billing_email']) ?>
                </div>
                <div>
                    <strong>Shipping Address:</strong><br>
                    <?= htmlspecialchars($order['shipping_address']) ?>
                </div>
            </div>
    
            <table class="items-table">
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
                        <td><?= $item['quantity'] ?></td>
                        <td>₹<?= number_format($item['price_per_unit'], 2) ?></td>
                        <td>₹<?= number_format($item['price_per_unit'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    
            <div class="totals">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td>₹<?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Shipping:</td>
                        <td>₹<?= number_format($shipping, 2) ?></td>
                    </tr>
                     <tr>
                        <td style="font-weight: bold; border-top: 2px solid #333;">Grand Total:</td>
                        <td style="font-weight: bold; border-top: 2px solid #333;">₹<?= number_format($total, 2) ?></td>
                    </tr>
                </table>
                <div style="clear:both;"></div>
            </div>
        </div>
    
        <div class="button-container non-printable">
            <div id="play-sound-button-container" style="display: none; margin-bottom: 10px;">
                <button onclick="document.getElementById('success-sound').play()" class="print-button" style="background-color: #ffc107;">
                    <i class="fas fa-volume-up"></i> Play Success Sound
                </button>
            </div>
            <button onclick="window.print()" class="print-button"><i class="fas fa-print"></i> Print Receipt</button>
            <a href="shop.php" class="print-button" style="background-color: #6c757d;"><i class="fas fa-shopping-cart"></i> Continue Shopping</a>
        </div>
    </div>
    
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Define a unique key for this specific order in sessionStorage
        const soundPlayedKey = 'sound_played_for_order_<?= $order_id ?>';
        
        // Check if the sound has NOT been played in this session for this order
        if (!sessionStorage.getItem(soundPlayedKey)) {
            const audio = document.getElementById('success-sound');
            const playButtonContainer = document.getElementById('play-sound-button-container');

            const playPromise = audio.play();

            if (playPromise !== undefined) {
                playPromise.then(_ => {
                    // If autoplay starts successfully, set the flag in sessionStorage
                    sessionStorage.setItem(soundPlayedKey, 'true');
                }).catch(error => {
                    // Autoplay was blocked by the browser. Show a manual play button.
                    console.log('Autoplay was prevented by the browser.');
                    playButtonContainer.style.display = 'block';
                    // Set the flag anyway, so we don't try to autoplay on subsequent reloads.
                    sessionStorage.setItem(soundPlayedKey, 'true');
                });
            }
        } else {
            console.log('Sound has already been played for this order in this session.');
        }
    });
</script>
</body>
</html>
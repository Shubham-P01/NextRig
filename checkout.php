<?php
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with the current page as a query parameter
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details for pre-filling the form
$user_sql = "SELECT first_name, last_name, email, phone_number, street_address, apartment_unit, city, state_province, zip_postal_code FROM users WHERE user_id = ?";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    $user_data = [];
}

// Fetch cart products
$sql = "SELECT 
            ci.product_id, p.name AS product_name, p.price, ci.quantity,
            pi.image_url
        FROM cart_items ci
        JOIN shopping_cart sc ON ci.cart_id = sc.cart_id
        JOIN products p ON ci.product_id = p.product_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_main_image = 1
        WHERE sc.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$cart_products = $stmt->fetchAll();

if (empty($cart_products)) {
    header("Location: cart.php");
    exit();
}

$subtotal = 0;
foreach ($cart_products as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 250.00; 
$total = $subtotal + $shipping;

$your_upi_id = 'devangvijithp@okicici';
$your_name = 'NextRig';
$transaction_note = 'Order payment for NextRig';
$upi_uri = "upi://pay?pa=" . urlencode($your_upi_id) . "&pn=" . urlencode($your_name) . "&am=" . urlencode(number_format($total, 2, '.', '')) . "&cu=INR" . "&tn=" . urlencode($transaction_note);
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($upi_uri);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address']);
    $apartment = htmlspecialchars($_POST['apartment'] ?? '');
    $city = htmlspecialchars($_POST['city']);
    $state = htmlspecialchars($_POST['state']);
    $pin = htmlspecialchars($_POST['pin']);
    $payment_method = htmlspecialchars($_POST['payment_method']);
    $order_notes = htmlspecialchars($_POST['order_notes'] ?? '');
    
    $full_address = $address . (!empty($apartment) ? ", " . $apartment : "");
    $shipping_address = "$full_address, $city, $state - $pin";
    $billing_name = $first_name . ' ' . $last_name;
    $billing_email = $email;
    $payment_id = null;
    
    if ($payment_method === 'upi') {
        $payment_id = htmlspecialchars($_POST['upi_transaction_id']);
    } elseif ($payment_method === 'card') {
        $payment_id = 'ch_' . uniqid(); 
    }

    $order_sql = "INSERT INTO orders (buyer_user_id, total_amount, order_date, shipping_address, billing_name, billing_email, payment_method, order_notes) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([$user_id, $total, $shipping_address, $billing_name, $billing_email, $payment_method, $order_notes]);
    $order_id = $pdo->lastInsertId();

    foreach ($cart_products as $item) {
        $order_item_sql = "INSERT INTO order_items (order_id, product_id, price_per_unit, quantity) VALUES (?, ?, ?, ?)";
        $order_item_stmt = $pdo->prepare($order_item_sql);
        $order_item_stmt->execute([$order_id, $item['product_id'], $item['price'], $item['quantity']]);
    }

    $clear_cart_sql = "DELETE ci FROM cart_items ci JOIN shopping_cart sc ON ci.cart_id = sc.cart_id WHERE sc.user_id = ?";
    $pdo->prepare($clear_cart_sql)->execute([$user_id]);
    
    header("Location: success.php?order_id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - NextRig</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{
             font-family: 'Popins', sans-serif;
        }
        :root {
            --primary-text: #111827;
            --secondary-text: #6B7280;
            --border-color: #E5E7EB;
            --background-color: #F9FAFB;
            --card-background: #FFFFFF;
            --primary-brand-color: #4F46E5;
            --error-color: #EF4444;
        }
        body {
            font-family: 'Popins', sans-serif;
            background-color: var(--background-color);
            margin: 0;
            color: var(--primary-text);
        }
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .main-content {
             margin-top: 40px;
        }
        .checkout-header {
            margin-bottom: 24px;
        }
        .back-link {
            color: var(--secondary-text);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .checkout-header h1 { font-size: 28px; margin: 8px 0 4px; }
        .checkout-header p { color: var(--secondary-text); margin: 0; }
        .checkout-layout {
            display: grid;
            grid-template-columns: 6fr 4fr;
            gap: 48px;
            align-items: flex-start;
        }
        .form-section {
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .form-section h2 { font-size: 18px; margin: 0 0 20px; }
        .form-row { display: flex; gap: 16px; }
        .form-group { flex: 1; margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .payment-method-options { border: 1px solid var(--border-color); border-radius: 8px; }
        .payment-method-options label {
            display: block;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
        }
        .payment-method-options label:last-child { border-bottom: none; }
        .payment-method-options input[type="radio"] { margin-right: 12px; }
        .payment-info-box {
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .expiry-cvv-group { display: flex; gap: 10px; }
        .expiry-cvv-group .form-group { flex: 1; }
        .summary-card {
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            position: sticky;
            top: 40px;
        }
        .product-item {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
        }
        .product-thumbnail {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            background-color: #F3F4F6;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .product-thumbnail img { max-width: 50px; max-height: 50px; object-fit: contain; }
        .product-details { flex-grow: 1; }
        .product-details p { margin: 0; font-weight: 600; }
        .product-details span { font-size: 14px; color: var(--secondary-text); }
        .product-price { font-weight: 600; }
        .summary-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: var(--secondary-text);
        }
        .summary-line.total {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary-text);
            margin-top: 16px;
        }
        .summary-card hr { border: none; border-top: 1px solid var(--border-color); margin: 20px 0; }
        .pay-button {
            display: flex;
            width: 100%;
            padding: 14px;
            background-color: var(--primary-brand-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .pay-button:hover { background-color: #4338CA; }
        #upi-payment-info { text-align: center; }
        #upi-payment-info img { margin: 0 auto; display: block; max-width: 150px; }
        .help-card {
            margin-top: 24px;
        }
        .help-card h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .help-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        .help-item:not(:last-child) {
            margin-bottom: 20px;
        }
        .help-item svg {
            flex-shrink: 0;
            margin-top: 3px;
            color: var(--secondary-text);
        }
        .help-item div strong {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: var(--primary-text);
            margin-bottom: 2px;
        }
        .help-item div a {
            font-size: 14px;
            color: var(--secondary-text);
            text-decoration: none;
        }
        .help-item div a:hover {
            color: var(--primary-brand-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="navbar">
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
                    <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="nav-link">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <div class="checkout-header">
                <a href="cart.php" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Cart
                </a>
                <h1>Checkout</h1>
                <p>Complete your order securely</p>
            </div>
    
            <form id="checkout-form" method="POST">
                <div class="checkout-layout">
                    <div class="checkout-form-main">
                        <div class="form-section">
                            <h2>Shipping Information</h2>
                            <div class="form-row">
                                <div class="form-group"><label for="first-name">First Name *</label><input type="text" id="first-name" name="first_name" required value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>"></div>
                                <div class="form-group"><label for="last-name">Last Name *</label><input type="text" id="last-name" name="last_name" required value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>"></div>
                            </div>
                            <div class="form-group"><label for="email">Email Address *</label><input type="email" id="email" name="email" required value="<?= htmlspecialchars($user_data['email'] ?? '') ?>"></div>
                            <div class="form-group"><label for="phone">Phone Number *</label><input type="text" id="phone" name="phone" required maxlength="10" pattern="\d{10}" title="Phone number must be exactly 10 digits" value="<?= htmlspecialchars($user_data['phone_number'] ?? '') ?>"></div>
                            <div class="form-group"><label for="address">Street Address *</label><input type="text" id="address" name="address" required value="<?= htmlspecialchars($user_data['street_address'] ?? '') ?>"></div>
                            <div class="form-group"><label for="apartment">Apartment, Suite, etc.</label><input type="text" id="apartment" name="apartment" value="<?= htmlspecialchars($user_data['apartment_unit'] ?? '') ?>"></div>
                            <div class="form-row">
                                <div class="form-group"><label for="city">City *</label><input type="text" id="city" name="city" required value="<?= htmlspecialchars($user_data['city'] ?? '') ?>"></div>
                                <div class="form-group"><label for="state">State *</label><input type="text" id="state" name="state" required value="<?= htmlspecialchars($user_data['state_province'] ?? '') ?>"></div>
                                <div class="form-group"><label for="pin">PIN Code *</label><input type="text" id="pin" name="pin" required value="<?= htmlspecialchars($user_data['zip_postal_code'] ?? '') ?>"></div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h2>Payment Method</h2>
                            <div class="payment-method-options">
                                <label><input type="radio" name="payment_method" value="card" required> Credit / Debit Card</label>
                                <label><input type="radio" name="payment_method" value="upi" required> UPI</label>
                                <label><input type="radio" name="payment_method" value="cod" required> Cash on Delivery</label>
                            </div>
                        </div>
    
                        <div id="card-payment-info" class="payment-info-box" style="display: none; margin-bottom: 24px;">
                            <h4>Enter Card Details</h4>
                            <div class="form-group">
                                <label for="card_number">Card Number *</label>
                                <input type="text" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX">
                            </div>
                            <div class="expiry-cvv-group">
                                <div class="form-group">
                                    <label for="expiry_month">Expiry Month *</label>
                                    <select id="expiry_month" name="expiry_month">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="expiry_year">Expiry Year *</label>
                                    <select id="expiry_year" name="expiry_year">
                                        <?php $currentYear = date('Y'); for ($y = 0; $y < 10; $y++): ?>
                                            <option value="<?= $currentYear + $y ?>"><?= $currentYear + $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV *</label>
                                    <input type="text" id="cvv" name="cvv" placeholder="XXX">
                                </div>
                            </div>
                        </div>

                        <div id="upi-payment-info" class="payment-info-box" style="display: none; margin-bottom: 24px;">
                            <h4>Scan to Pay with any UPI App</h4>
                            <img src="<?= htmlspecialchars($qr_code_url) ?>" alt="UPI QR Code">
                            <p style="text-align:center; font-weight: bold;">UPI ID: <?= htmlspecialchars($your_upi_id) ?></p>
                            <p style="text-align:center;">Amount to Pay: <strong>₹<?= number_format($total, 2) ?></strong></p>
                            <hr>
                            <div class="form-group">
                                <label for="upi_transaction_id">Enter UPI Transaction ID *</label>
                                <input type="text" id="upi_transaction_id" name="upi_transaction_id" placeholder="Enter the 12-digit Ref ID" maxlength="12" pattern="\d{12}" title="UPI Transaction ID must be exactly 12 digits">
                            </div>
                        </div>
    
                        <div class="form-section">
                            <h2>Order Notes</h2>
                            <div class="form-group"><label for="order_notes">Special instructions for delivery (optional)</label><textarea id="order_notes" name="order_notes" placeholder="e.g., Please call before arriving."></textarea></div>
                        </div>
                    </div>
    
                    <aside class="checkout-sidebar">
                        <div class="summary-card">
                            <h2>Order Summary</h2>
                            <?php foreach ($cart_products as $item): ?>
                            <div class="product-item">
                                <div class="product-thumbnail"><img src="<?= htmlspecialchars($item['image_url'] ?: 'placeholder.png') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>"></div>
                                <div class="product-details"><p><?= htmlspecialchars($item['product_name']) ?></p><span>Qty: <?= $item['quantity'] ?></span></div>
                                <div class="product-price">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="summary-line"><span>Subtotal</span><span>₹<?= number_format($subtotal, 2) ?></span></div>
                            <div class="summary-line"><span>Shipping</span><span>₹<?= number_format($shipping, 2) ?></span></div>
                            <hr>
                            <div class="summary-line total"><span>Total</span><span>₹<?= number_format($total, 2) ?></span></div>
                            <button type="submit" class="pay-button">Place Order</button>
                        </div>

                        <div class="summary-card help-card">
                            <h2>Need Help?</h2>
                            <div class="help-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                <div>
                                    <strong>Call us</strong>
                                    <a href="tel:+9118001234567">+91 1800-123-4567</a>
                                </div>
                            </div>
                            <div class="help-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                <div>
                                    <strong>Email support</strong>
                                    <a href="mailto:support@nextrig.com">support@nextrig.com</a>
                                </div>
                            </div>
                            <div class="help-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                <div>
                                    <strong>WhatsApp</strong>
                                    <a href="https://wa.me/919876543210" target="_blank" rel="noopener noreferrer">+91 98765-43210</a>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
            const upiInfoBox = document.getElementById('upi-payment-info');
            const upiTransactionInput = document.getElementById('upi_transaction_id');
            const cardInfoBox = document.getElementById('card-payment-info');
            const cardInputs = [
                document.getElementById('card_number'),
                document.getElementById('expiry_month'),
                document.getElementById('expiry_year'),
                document.getElementById('cvv')
            ];

            paymentRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // First, hide all boxes and set fields to not required
                    upiInfoBox.style.display = 'none';
                    upiTransactionInput.required = false;
                    cardInfoBox.style.display = 'none';
                    cardInputs.forEach(input => input.required = false);

                    // Then, show the selected one and set its fields to required
                    if (this.value === 'upi') {
                        upiInfoBox.style.display = 'block';
                        upiTransactionInput.required = true;
                    } else if (this.value === 'card') {
                        cardInfoBox.style.display = 'block';
                        cardInputs.forEach(input => input.required = true);
                    }
                });
            });

            // Validate phone number and UPI transaction ID on form submission
            const checkoutForm = document.getElementById('checkout-form');
            checkoutForm.addEventListener('submit', function(event) {
                const phoneInput = document.getElementById('phone');
                const upiTransactionInput = document.getElementById('upi_transaction_id');

                if (!/^\d{10}$/.test(phoneInput.value)) {
                    alert('Phone number must be exactly 10 digits.');
                    event.preventDefault();
                    return;
                }

                if (upiTransactionInput.required && !/^\d{12}$/.test(upiTransactionInput.value)) {
                    alert('UPI Transaction ID must be exactly 12 digits.');
                    event.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>
</html>
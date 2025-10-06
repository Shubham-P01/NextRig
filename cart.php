<?php
// Start session and include PDO connection
session_start();
require 'connection.php'; // This file should create the $pdo object

// Set the current user ID (defaulting to user 3 for testing)
$user_id = $_SESSION['user_id'] ?? 3;

// Handle form submissions for quantity updates and item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'])) {
    $cart_item_id = (int)$_POST['cart_item_id'];
    $action = $_POST['action'] ?? '';

    // Security Check: Verify the cart item belongs to the current user
    $check_sql = "SELECT ci.quantity FROM cart_items ci
                  JOIN shopping_cart sc ON ci.cart_id = sc.cart_id
                  WHERE ci.cart_item_id = ? AND sc.user_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$cart_item_id, $user_id]);
    $item_data = $check_stmt->fetch();

    if ($item_data) {
        if ($action === 'remove') {
            // Delete the item entirely
            $delete_sql = "DELETE FROM cart_items WHERE cart_item_id = ?";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([$cart_item_id]);
        } else {
            $current_quantity = $item_data['quantity'];
            $new_quantity = $current_quantity;

            if ($action === 'increase') {
                $new_quantity++;
            } elseif ($action === 'decrease') {
                $new_quantity--;
            }

            if ($new_quantity <= 0) {
                // If quantity becomes 0 or less, remove the item
                $delete_sql = "DELETE FROM cart_items WHERE cart_item_id = ?";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute([$cart_item_id]);
            } else {
                // Otherwise, update the quantity
                $update_sql = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$new_quantity, $cart_item_id]);
            }
        }
    }

    // Redirect to prevent form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle promo code submission
$promo_code = '';
$discount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_code'])) {
    $promo_code = trim($_POST['promo_code']);

    // Check if the promo code exists and is active
    $promo_sql = "SELECT discount_percent FROM promo_codes WHERE code = ? AND status = 'active'";
    $promo_stmt = $pdo->prepare($promo_sql);
    $promo_stmt->execute([$promo_code]);
    $promo_data = $promo_stmt->fetch();

    if ($promo_data) {
        $_SESSION['promo_code'] = $promo_code;
        $_SESSION['discount_percent'] = $promo_data['discount_percent'];
    } else {
        $_SESSION['promo_code'] = null;
        $_SESSION['discount_percent'] = 0;
        $error_message = "Invalid or inactive promo code.";
    }
} else {
    // Clear promo code session variables if no promo code is submitted
    $_SESSION['promo_code'] = null;
    $_SESSION['discount_percent'] = 0;
}

// Fetch all cart items for display
$sql = "SELECT 
    ci.cart_item_id, p.name AS product_name, p.price, ci.quantity,
    pi.image_url
FROM cart_items ci
JOIN shopping_cart sc ON ci.cart_id = sc.cart_id
JOIN products p ON ci.product_id = p.product_id
LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_main_image = 1
WHERE sc.user_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_products as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 250.00;

// Apply discount if a valid promo code is used
$discount_percent = $_SESSION['discount_percent'] ?? 0; // Fetch discount percent from session
$discount_amount = ($subtotal * $discount_percent) / 100; // Calculate discount amount
$total = $subtotal - $discount_amount + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - NextRig</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{
            font-family: 'Poppins', sans-serif;
        }
        :root {
            --primary-text: #111827;
            --secondary-text: #6B7280;
            --border-color: #E5E7EB;
            --background-color: #F9FAFB;
            --card-background: #FFFFFF;
            --button-primary-bg: #1F2937;
            --button-primary-text: #FFFFFF;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
.navbar {
    background-color: var(--card-background);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 24px;
}
.nav-container {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nav-logo {
    font-weight: 700;
    font-size: 24px;
    color: var(--primary-text);
    text-decoration: none;
}
.nav-menu {
    display: flex;
    gap: 32px;
}
.nav-link {
    text-decoration: none;
    color: var(--secondary-text);
    font-weight: 500;
    padding: 4px 0;
}
.nav-link.active,
.nav-link:hover {
    color: var(--primary-text);
}
.nav-icons {
    display: flex;
    gap: 20px;
    color: var(--primary-text);
}
.nav-icons svg {
    cursor: pointer;
}
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--primary-text);
            line-height: 1.6;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 24px; }
        form { display: contents; }
        .page-header h1 { font-size: 28px; font-weight: 700; margin-bottom: 4px; }
        .page-header p { color: var(--secondary-text); margin-bottom: 32px; }
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            align-items: flex-start;
        }
        .cart-items-container {
            background-color: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        .cart-items-container h2 {
            font-size: 18px;
            font-weight: 600;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
        }
        .cart-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
        }
        .cart-items-list .cart-item:last-child { border-bottom: none; }
        .item-image {
            width: 80px;
            height: 80px;
            background-color: #F3F4F6;
            border-radius: 8px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .item-image img {
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }
        .item-details { flex-grow: 1; }
        .item-details h3 { font-size: 16px; font-weight: 600; margin: 0; }
        .item-details p { font-size: 14px; color: var(--secondary-text); margin: 2px 0 0; }
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 1px solid var(--border-color);
            border-radius: 6px;
        }
        .quantity-selector button {
            background: none; border: none; width: 32px; height: 32px; font-size: 18px;
            color: var(--secondary-text); cursor: pointer;
        }
        .quantity-selector span {
            width: 40px; text-align: center; font-weight: 500;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            padding: 4px 0;
        }
        .item-price { font-weight: 600; width: 90px; text-align: right; }
        .item-remove button {
            background: none; border: none; color: var(--secondary-text); cursor: pointer; padding: 8px;
        }
        .item-remove button:hover { color: #EF4444; }
        .cart-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; border-top: 1px solid var(--border-color);
            font-weight: 500; font-size: 14px; color: var(--secondary-text);
        }
        .cart-footer a { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px; }
        .cart-footer a:hover { color: var(--primary-text); }

        /* --- Sidebar (Right Side) --- */
        .sidebar { display: flex; flex-direction: column; gap: 24px; }
        .card {
            background-color: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }
        .card h3 { font-size: 18px; font-weight: 600; margin-bottom: 24px; }
        .summary-item, .summary-total {
            display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 16px;
        }
        .summary-item span:first-child { color: var(--secondary-text); }
        .summary-total { font-weight: 700; font-size: 18px; }
        .card hr { border: none; border-top: 1px solid var(--border-color); margin: 16px 0; }
        .promo-code label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .promo-input { display: flex; }
        .promo-input input {
            flex-grow: 1; border: 1px solid var(--border-color); border-radius: 6px 0 0 6px;
            padding: 10px 12px; font-size: 16px; outline: none;
        }
        .promo-input input:focus { border-color: #A5B4FC; }
        .promo-input button {
            background-color: var(--button-primary-bg); /* Change to primary button color */
    border: none; /* Remove unnecessary border */
    border-radius: 0 6px 6px 0;
    padding: 0 16px;
    font-weight: 500;
    cursor: pointer; /* Enable pointer cursor */
    color: var(--button-primary-text); /* Change text color to white */
        }
        .checkout-btn {
            width: 100%; background-color: var(--button-primary-bg); color: var(--button-primary-text);
            padding: 14px; border-radius: 8px; font-size: 16px; font-weight: 600;
            margin-top: 24px; border: none; cursor: pointer; display: flex; align-items: center;
            justify-content: center; gap: 8px;
        }
        .secure-checkout-info {
            text-align: center; font-size: 12px; color: var(--secondary-text); margin-top: 16px;
        }
        .payment-icons { text-align: center; margin-top: 16px; }
        .payment-icons img { display: inline-block; height: 24px; }
    
        .buyer-protection-card ul { list-style: none; }
        .buyer-protection-card li { display: flex; align-items: flex-start; gap: 16px; }
        .buyer-protection-card li + li { margin-top: 20px; }
        .buyer-protection-card .icon { flex-shrink: 0; color: var(--secondary-text); }
        .buyer-protection-card strong { font-size: 16px; font-weight: 600; display: block; }
        .buyer-protection-card p { font-size: 14px; color: var(--secondary-text); margin: 0; }
    </style>
</head>
<header class="navbar">
    <div class="nav-container">
        <a href="#" class="nav-logo">NextRig</a>
        <nav class="nav-menu">
            <a href="index.php" class="nav-link">Home</a>
            <a href="shop.php" class="nav-link active">Shop</a>
            <a href="form.php" class="nav-link">Forum</a>
            <a href="sell.php" class="nav-link">Sell</a>
            <a href="aboutus.php" class="nav-link">About Us</a>
            <a href="contact.php" class="nav-link">Contact</a>
        </nav>
        <div class="nav-icons">
    <a href="profile.php" title="My Account">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
    </a>

    <a href="cart.php" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
    </a>
</div>
    </div>
</header>
<body>
    <main class="container">
        <div class="page-header">
            <h1>Shopping Cart</h1>
            <p>Review your items before checkout</p>
        </div>

        <div class="cart-layout">
            <div class="cart-items-container">
                <h2>Cart Items (<?= count($cart_products) ?>)</h2>
                <div class="cart-items-list">
                    <?php if (empty($cart_products)): ?>
                        <p style="padding: 24px;">Your cart is empty.</p>
                    <?php else: ?>
                        <?php foreach ($cart_products as $item): ?>
                            <div class="cart-item">
                                <div class="item-image">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://placehold.co/80x80/eee/ccc?text=No+Image') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                </div>
                                <div class="item-details">
                                    <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                                </div>
                                <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                    <div class="quantity-selector">
                                        <button type="submit" name="action" value="decrease">-</button>
                                        <span><?= $item['quantity'] ?></span>
                                        <button type="submit" name="action" value="increase">+</button>
                                    </div>
                                </form>
                                <div class="item-price" id="item-total-price-<?= $item['cart_item_id'] ?>">
                                ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                            </div>
                                <div class="item-remove">
                                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                                        <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                        <button type="submit" name="action" value="remove" title="Remove item">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="cart-footer">
                    <a href="shop.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Continue Shopping
                    </a>
                </div>
            </div>

            <aside class="sidebar">
                <div class="order-summary card">
                    <h3>Order Summary</h3>
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span>₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Shipping</span>
                        <span>₹<?= number_format($shipping, 2) ?></span>
                    </div>
                    <div class="summary-item">
    <span>Discount (<?= htmlspecialchars($promo_code) ?>)</span>
    <span>-₹<?= number_format($discount_amount, 2) ?></span>
</div>
                    <hr>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>₹<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="promo-code">
    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
        <label for="promo">Promo Code</label>
        <div class="promo-input">
            <input type="text" id="promo" name="promo_code" placeholder="Enter code" value="<?= htmlspecialchars($promo_code) ?>">
            <button type="submit">Apply</button> <!-- Ensure this button is clickable -->
        </div>
    </form>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>
</div>
                    <a href="checkout.php" class="checkout-btn">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
    Proceed to Checkout
</a>
                    <p class="secure-checkout-info">
                        Secure checkout with 256-bit SSL encryption
                    </p>
                </div>

                <div class="buyer-protection-card card">
                    <h3>Buyer Protection</h3>
                    <ul>
                        <li>
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            </div>
                            <div>
                                <strong>Money-back guarantee</strong>
                                <p>Full refund if item not as described</p>
                            </div>
                        </li>
                        <li>
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            </div>
                            <div>
                                <strong>Secure shipping</strong>
                                <p>Tracked and insured delivery</p>
                            </div>
                        </li>
                        <li>
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </div>
                            <div>
                                <strong>24/7 support</strong>
                                <p>Help when you need it</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>
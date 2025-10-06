<?php
// Include the PDO database configuration file.
require 'connection.php';
include 'header.php';
$sql = "
    SELECT 
    p.product_id, p.name, p.description, p.price, p.`condition`,
    c.name as category_name,
    (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_main_image = 1 LIMIT 1) as main_image
FROM products p
JOIN categories c ON p.category_id = c.category_id
";

// --- Handle Filtering ---
$where_clauses = ["p.status = 'Available'"];
$params = [];

if (!empty($_GET['categories']) && is_array($_GET['categories'])) {
    $placeholders = implode(',', array_fill(0, count($_GET['categories']), '?'));
    $where_clauses[] = "p.category_id IN ($placeholders)";
    $params = array_merge($params, $_GET['categories']);
}
if (!empty($_GET['brands']) && is_array($_GET['brands'])) {
    $placeholders = implode(',', array_fill(0, count($_GET['brands']), '?'));
    $where_clauses[] = "p.brand_id IN ($placeholders)";
    $params = array_merge($params, $_GET['brands']);
}
if (!empty($_GET['min_price'])) {
    $where_clauses[] = "p.price >= ?";
    $params[] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where_clauses[] = "p.price <= ?";
    $params[] = $_GET['max_price'];
}
if (!empty($_GET['condition'])) {
    $where_clauses[] = "p.condition = ?";
    $params[] = $_GET['condition'];
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// --- Handle Sorting ---
$order_by = " ORDER BY p.listing_created_at DESC";
if (!empty($_GET['sort-by'])) {
    switch ($_GET['sort-by']) {
        case 'price_asc':
            $order_by = " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $order_by = " ORDER BY p.price DESC";
            break;
    }
}
$sql .= $order_by;

// --- Fetch Data ---
$product_stmt = $pdo->prepare($sql);
$product_stmt->execute($params);
$products = $product_stmt->fetchAll();

$category_stmt = $pdo->query("SELECT c.category_id, c.name, COUNT(p.product_id) as product_count FROM categories c LEFT JOIN products p ON c.category_id = p.category_id WHERE p.status = 'Available' GROUP BY c.category_id, c.name");
$categories = $category_stmt->fetchAll();
$brand_stmt = $pdo->query("SELECT brand_id, name FROM brands");
$brands = $brand_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - NextRig</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/4a24449835.js" crossorigin="anonymous"></script>
    <style>
        :root {
    --primary-text: #111827;
    --secondary-text: #6B7280;
    --border-color: #E5E7EB;
    --background-color: #F9FAFB;
    --card-background: #FFFFFF;
    --button-primary-bg: #1F2937;
    --button-primary-text: #FFFFFF;
    --help-button-bg: #7C3AED;
}
*{
    font-family: 'Poppins', sans-serif;
}

/* --- Base & Reset --- */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Inter', sans-serif;
    background-color: var(--background-color);
    color: var(--primary-text);
    line-height: 1.6;
}

a {
    text-decoration: none;
    color: inherit;
}

ul {
    list-style: none;
}

button {
    font-family: inherit;
    border: none;
    cursor: pointer;
    background: none;
}

img {
    max-width: 100%;
    display: block;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}
/* --- Main Content --- */
main.container {
    padding-top: 48px;
    padding-bottom: 48px;
}

.main-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.main-header p {
    color: var(--secondary-text);
    margin-bottom: 32px;
}

.cart-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
    align-items: flex-start;
}

/* --- Cart Items Section (Left) --- */
.cart-items-section h2 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px 0;
    border-bottom: 1px solid var(--border-color);
}

.item-image {
    width: 100px;
    height: 100px;
    background-color: #F3F4F6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.item-image img {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

.item-details {
    flex-grow: 1;
}

.item-details h3 {
    font-size: 16px;
    font-weight: 600;
}

.item-details .condition, .item-details .seller {
    font-size: 14px;
    color: var(--secondary-text);
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 1px solid var(--border-color);
    border-radius: 6px;
}

.quantity-selector button {
    width: 32px;
    height: 32px;
    font-size: 18px;
    color: var(--secondary-text);
}

.quantity-selector span {
    width: 40px;
    text-align: center;
    font-weight: 500;
}

.item-price {
    font-weight: 600;
    width: 100px;
    text-align: right;
}

.remove-item {
    color: var(--secondary-text);
    padding: 8px;
}

.remove-item:hover {
    color: #EF4444; /* red-500 */
}

.cart-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 24px;
    font-weight: 500;
}

.cart-footer a {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--secondary-text);
}
.cart-footer a:hover {
    color: var(--primary-text);
}

/* --- Sidebar (Right) --- */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.card {
    background-color: var(--card-background);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 24px;
}

.order-summary h2 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 24px;
}

.summary-item, .summary-total {
    display: flex;
    justify-content: space-between;
    margin-bottom: 16px;
    font-size: 16px;
}
.summary-item span:first-child {
    color: var(--secondary-text);
}
.summary-total {
    font-weight: 700;
    font-size: 18px;
}

.order-summary hr {
    border: none;
    border-top: 1px solid var(--border-color);
    margin: 16px 0;
}

.promo-code label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
}

.promo-input {
    display: flex;
}

.promo-input input {
    flex-grow: 1;
    border: 1px solid var(--border-color);
    border-radius: 6px 0 0 6px;
    padding: 10px 12px;
    font-size: 16px;
    outline: none;
}

.promo-input input:focus {
    border-color: #A5B4FC;
}

.promo-input button {
    background-color: #F3F4F6;
    border: 1px solid var(--border-color);
    border-left: none;
    border-radius: 0 6px 6px 0;
    padding: 0 16px;
    font-weight: 500;
}

.checkout-btn {
    width: 100%;
    background-color: var(--button-primary-bg);
    color: var(--button-primary-text);
    padding: 14px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    margin-top: 24px;
}

.secure-checkout-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 12px;
    color: var(--secondary-text);
    margin-top: 16px;
}

.payment-methods {
    text-align: center;
    margin-top: 16px;
}

.payment-methods img {
    display: inline-block;
    height: 24px;
}

/* Buyer Protection Card */
.buyer-protection h2 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
}

.buyer-protection ul li {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.buyer-protection ul li + li {
    margin-top: 20px;
}

.buyer-protection svg {
    flex-shrink: 0;
    color: var(--secondary-text);
}

.buyer-protection h3 {
    font-size: 16px;
    font-weight: 600;
}

.buyer-protection p {
    font-size: 14px;
    color: var(--secondary-text);
}

/* --- Floating Help Button --- */
.help-button {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background-color: var(--help-button-bg);
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
    </style>
</head>
<body>
    <main class="container">
        <div class="shop-layout">
            <aside class="filters-sidebar">
                <h3>Filters</h3>
                <form action="shop.php" method="GET">
                     <div class="filter-group">
                        <label for="sort-by">Sort By</label>
                        <select id="sort-by" name="sort-by">
                            <option value="default">Most Popular</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h4>Categories</h4>
                        <?php foreach ($categories as $category): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="cat<?php echo $category['category_id']; ?>" name="categories[]" value="<?php echo $category['category_id']; ?>"> 
                                <label for="cat<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></label> 
                                <span>(<?php echo $category['product_count']; ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="filter-group">
                        <h4>Brand</h4>
                        <?php foreach ($brands as $brand): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="brand<?php echo $brand['brand_id']; ?>" name="brands[]" value="<?php echo $brand['brand_id']; ?>"> 
                                <label for="brand<?php echo $brand['brand_id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="filter-group">
                        <h4>Price Range</h4>
                        <div class="price-inputs">
                            <input type="number" name="min_price" placeholder="Min">
                            <input type="number" name="max_price" placeholder="Max">
                        </div>
                    </div>

                    <div class="filter-group">
                        <h4>Condition</h4>
                        <div class="radio-item"><input type="radio" id="cond1" name="condition" value="Like New"> <label for="cond1">Like New</label></div>
                        <div class="radio-item"><input type="radio" id="cond2" name="condition" value="Excellent"> <label for="cond2">Excellent</label></div>
                        <div class="radio-item"><input type="radio" id="cond3" name="condition" value="Good"> <label for="cond3">Good</label></div>
                        <div class="radio-item"><input type="radio" id="cond4" name="condition" value="Fair"> <label for="cond4">Fair</label></div>
                    </div>
                    
                    <button type="submit" class="apply-filters-btn">Apply Filters</button>
                </form>
            </aside>

            <section class="product-listings">
                <div class="toolbar">
                    <p class="results-count">Showing <?php echo count($products); ?> results</p>
                    <div class="view-options">
                        <i id="grid-view-btn" class="fas fa-th-large active"></i>
                        <i id="list-view-btn" class="fas fa-bars"></i>
                    </div>
                    <input type="search" placeholder="Search products..." class="search-bar">
                </div>
                
                <!-- This is the container where products will be displayed -->
                <div id="product-container" class="product-grid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($product['main_image'] ?? 'https://placehold.co/300x200/e0e0e0/777?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                                <div class="card-content">
                                    <div class="card-tags">
                                        <span class="tag tag-excellent"><?php echo htmlspecialchars($product['condition']); ?></span>
                                        <span class="tag tag-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    </div>
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="description"><?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?></p>
                                    <span class="price">₹<?php echo htmlspecialchars($product['price']); ?></span>
                                    <div class="rating">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                        <span>(24)</span>
                                    </div>
                                    <div class="card-footer">    
                                        <a href="product.php?id=<?php echo $product['product_id']; ?>" class="details-btn">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products found matching your criteria.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gridViewBtn = document.getElementById('grid-view-btn');
            const listViewBtn = document.getElementById('list-view-btn');
            const productContainer = document.getElementById('product-container');

            // Function to set view
            const setView = (view) => {
                if (view === 'list') {
                    productContainer.classList.remove('product-grid');
                    productContainer.classList.add('product-list');
                    listViewBtn.classList.add('active');
                    gridViewBtn.classList.remove('active');
                    localStorage.setItem('shopView', 'list');
                } else {
                    productContainer.classList.remove('product-list');
                    productContainer.classList.add('product-grid');
                    gridViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                    localStorage.setItem('shopView', 'grid');
                }
            };

            // Event Listeners
            gridViewBtn.addEventListener('click', () => setView('grid'));
            listViewBtn.addEventListener('click', () => setView('list'));

            // Check localStorage for saved view preference
            const savedView = localStorage.getItem('shopView');
            if (savedView) {
                setView(savedView);
            }
        });
    </script>

</body>
</html>


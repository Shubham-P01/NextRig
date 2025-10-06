<?php
session_start();

// NOTE: Ensure your database connection file is correctly named 'connection.php'
require 'connection.php';
$conn = $pdo; // Assuming $pdo is the PDO connection object from connection.php

// === FILTER LOGIC ===
$categoryFilter = $_GET['category'] ?? '';
$sortFilter = $_GET['sort'] ?? '';
$page = 1; 
$limit = 8;
$offset = ($page - 1) * $limit;

// === Fetch all categories dynamically (FIXED: Uses 'name' column) ===
$catStmt = $conn->query("SELECT name FROM categories");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Prepare filter parameters (Still needed for the filter form)
$filter_params = '';
if ($categoryFilter && $categoryFilter != 'all') {
    $filter_params .= '&category=' . urlencode($categoryFilter);
}
if ($sortFilter) {
    $filter_params .= '&sort=' . urlencode($sortFilter);
}


// === Build product query (FIXED: Uses 'c.name' alias) ===
$query = "SELECT p.*, i.image_url, c.name AS category_name
          FROM products p
          JOIN product_images i ON p.product_id = i.product_id
          JOIN categories c ON p.category_id = c.category_id
          WHERE i.is_main_image = 1 AND p.status='Available'";

if ($categoryFilter && $categoryFilter != 'all') {
    $query .= " AND c.name = :category";
}

if ($sortFilter == 'low-high') {
    $query .= " ORDER BY p.price ASC";
} elseif ($sortFilter == 'high-low') {
    $query .= " ORDER BY p.price DESC";
} else {
    $query .= " ORDER BY p.product_id DESC";
}

$query .= " LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
if ($categoryFilter && $categoryFilter != 'all') {
    $stmt->bindParam(':category', $categoryFilter);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Count total products for pagination (Logic kept for potential future use) ===
$countQuery = "SELECT COUNT(p.product_id)
               FROM products p
               JOIN categories c ON p.category_id = c.category_id
               WHERE p.status='Available'";

if ($categoryFilter && $categoryFilter != 'all') {
    $countQuery .= " AND c.name = :category";
}

$countStmt = $conn->prepare($countQuery);

if ($categoryFilter && $categoryFilter != 'all') {
    $countStmt->bindParam(':category', $categoryFilter);
}

$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);


// === Featured Products (Random items for Hot Deals) ===
$featuredProducts = $conn->query("
    SELECT p.*, i.image_url FROM products p
    JOIN product_images i ON p.product_id = i.product_id
    WHERE i.is_main_image = 1 AND p.status='Available'
    ORDER BY RAND() LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// === Forum ===
$forumStmt = $conn->query("
    SELECT f.*, u.first_name, u.last_name
    FROM forum_posts f
    JOIN users u ON f.user_id = u.user_id
    ORDER BY f.created_at DESC
    LIMIT 4
");
$forumPosts = $forumStmt->fetchAll(PDO::FETCH_ASSOC);

// === Hero Slides with Dummy Text (FIXED/UPDATED) ===
$hero_slides = [
    ['title' => 'Featured Categories', 'subtitle' => 'Graphics Cards • Processors • Memory • Storage'],
    ['title' => 'Lightning Fast Shipping', 'subtitle' => 'All orders ship within 24 hours of purchase.'],
    ['title' => 'Certified Pre-Owned', 'subtitle' => 'Every component is tested for performance and reliability.'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextRig - Pre-owned PC Components</title>
    <script src="https://kit.fontawesome.com/4a24449835.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="niger.css"> 
    <style>
        :root {
    --dark-bg: #222831;
    --medium-bg: #393E46;
    --light-bg: #F8F9FA;
    --text-light: #EEEEEE;
    --text-dark: #333333;
    --accent-purple: #6c5ce7;
    --border-color: #E0E0E0;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--light-bg);
    color: var(--text-dark);
}

a {
    text-decoration: none;
    color: inherit;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* HEADER */
.header {
    background-color: var(--dark-bg);
    color: var(--text-light);
    padding: 15px 0;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-logo {
    font-size: 1.8rem;
    font-weight: 700;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 40px;
}

.nav-link {
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav-link:hover {
    color: var(--accent-purple);
}

.nav-icons {
    display: flex;
    gap: 25px;
    font-size: 1.2rem;
}

.nav-icons i {
    cursor: pointer;
    transition: color 0.3s ease;
}

.nav-icons i:hover {
    color: var(--accent-purple);
}
.nav-username,
.nav-logout,
.nav-login {
    color: var(--text-light);
    font-weight: bold;
    text-decoration: none;
    margin-left: 10px;
    font-size: 1rem;
}
.nav-logout:hover,
.nav-login:hover,
.nav-username:hover {
    color: var(--accent-purple);
}
.special {
    color: var(--text-light);
    margin-left: 2px;
}


/* HERO SECTION: FINAL SLIDER FIXES */
.hero {
    background-color: var(--medium-bg);
    color: var(--text-light);
    text-align: center;
    padding: 100px 0;
    position: relative; 
    overflow: hidden;
    min-height: 350px; 
}

/* Container for all hero slides (FIXED: Added height to contain content) */
.hero-slider-content {
    position: relative;
    width: 100%;
    /* Fixed height for the dynamic content area */
    min-height: 100px; 
}

/* Individual slide item style (FIXED BUGGY TRANSITION) */
.hero-slide-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    opacity: 0; 
    transition: opacity 1s ease-in-out; 
}

/* Active slide style */
.hero-slide-item.active-hero-slide {
    opacity: 1;
}

.hero h1 {
    font-size: 3rem;
    margin-bottom: 10px;
}

.hero p {
    font-size: 1.2rem;
    color: #bdc3c7;
}

/* Hero Dots (FIXED OVERLAP) */
.hero-dots {
    /* Position dots below the content area, relative to .hero */
    position: absolute; 
    bottom: 80px; 
    left: 50%;
    transform: translateX(-50%);
    z-index: 5;
    margin-top: 0;
}

.hero-dots .dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    background-color: #7f8c8d;
    border-radius: 50%;
    margin: 0 5px;
    cursor: pointer; 
}

.hero-dots .dot.active {
    background-color: var(--text-light);
}

/* HOT DEALS SECTION */
.hot-deals {
    padding: 50px 0;
}

.hot-deals h2 {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 40px;
}

/* --- HOT DEALS SLIDER FIXES FOR ANIMATION & SIZE (UPDATED) --- */
.deal-slider {
    background-color: var(--medium-bg);
    color: var(--text-light);
    padding: 80px; 
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px;
    position: relative;
    overflow: hidden; 
    height: 180px; 
}
 
/* Arrow styles (FINAL BULLETPROOF COSMETIC FIX: Minimal Transparent Circle) */
.slider-arrow {
    font-size: 1.8rem; /* Slightly larger icon */
    cursor: pointer;
    
    /* VISUALS: Minimalist transparent button */
    color: var(--text-light); /* White icon color for visibility */
    background-color: rgba(255, 255, 255, 0.1); /* Slight white transparency */
    
    width: 45px; /* Larger click area */
    height: 45px;
    border-radius: 50%; /* Back to the clean circular shape */
    
    display: flex;
    justify-content: center;
    align-items: center;
    
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4); /* Clear shadow */
    
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    
    /* Ensure it's on top */
    z-index: 9999; 
    
    /* Transition for smooth hover effect */
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Positioning remains the same */
.deal-slider #prevDeal { left: 10px; } 
.deal-slider #nextDeal { right: 10px; } 

.slider-arrow:hover {
    /* Hover Effect: Use the ACCENT PURPLE */
    background-color: var(--accent-purple); 
    color: var(--text-light); /* Icon remains white for max contrast against purple */
    transform: translateY(-50%) scale(1.05); /* Subtle zoom */
}

.deal-content {
    /* Ensures the link is full size and text color is right */
    text-align: center;
    text-decoration: none; 
    color: var(--text-light); 

    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 80px; 
    
    opacity: 0;
    transform: translateX(100%); 
    transition: opacity 0.7s ease-in-out, transform 0.7s ease-in-out;
}

.deal-content.active-deal {
    opacity: 1; 
    transform: translateX(0); 
    position: relative;
}
/* --- END HOT DEALS SLIDER FIXES --- */

.deal-content h3 {
    font-size: 2.8rem;
    font-weight: 700;
}

.deal-content p {
    font-size: 1.1rem;
    color: #bdc3c7;
    margin-top: 10px;
}

.chat-bubble {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background-color: var(--accent-purple);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.5rem;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    z-index: 1000;
}

/* MAIN CONTENT (SHOP & FORUM) */
.main-content {
    padding: 50px 0;
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
}

/* SHOP SECTION */
.shop-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.shop-header h2 {
    font-size: 2rem;
}

.shop-filters {
    display: flex;
    gap: 15px;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    background-color: #fff;
    font-family: 'Poppins', sans-serif;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.product-card {
    background-color: #fff;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    padding: 20px;
}

/* --- PRODUCT GRID IMAGE FIXES --- */
.product-card img.product-image {
    width: 100%;
    height: 200px; 
    object-fit: cover; 
    border-radius: 5px;
    margin-bottom: 15px; 
}
.product-image-placeholder {
    display: none; 
}
/* --- END PRODUCT GRID IMAGE FIXES --- */


.product-card h3 {
    font-size: 1.2rem;
    font-weight: 600;
}

.product-card .description {
    font-size: 0.9rem;
    color: #666;
    margin: 10px 0;
    min-height: 40px;
}

.product-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.product-price {
    font-size: 1.8rem;
    font-weight: 700;
    position: relative;
}
.product-price::before {
    content: '₹'; 
    font-size: 1rem;
    font-weight: 500;
    position: relative;
    top: -5px;
    margin-right: 2px;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-dark {
    background-color: var(--text-dark);
    color: var(--text-light);
    border: none;
}
.btn-dark:hover {
    background-color: #555;
}

/* Pagination styles are removed in PHP, but keeping this for completeness */
.pagination {
    margin-top: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

/* FORUM SECTION */
.forum-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.forum-header h2 {
    font-size: 1.5rem;
}

.forum-post {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid var(--border-color);
}
.forum-post:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.avatar {
    width: 40px;
    height: 40px;
    background-color: #ccc;
    border-radius: 50%;
    flex-shrink: 0;
}

.post-author {
    font-weight: 600;
}
.post-time {
    color: #888;
    font-size: 0.8rem;
    margin-left: 5px;
}
.post-title {
    font-weight: 500;
    margin: 4px 0;
    color: #222;
}
.post-excerpt {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 10px;
}
.post-stats {
    display: flex;
    gap: 15px;
    font-size: 0.8rem;
    color: #888;
}
.post-stats i {
    margin-right: 4px;
}

.btn-new-discussion {
    display: block;
    width: 100%;
    text-align: center;
    margin-top: 20px;
    background-color: var(--text-dark);
    color: var(--text-light);
    border: none;
}
.btn-new-discussion:hover {
    background-color: #555;
}

/* FOOTER */
.footer {
    background-color: var(--dark-bg);
    color: var(--text-light);
    padding: 60px 0 20px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid var(--medium-bg);
}

.footer-col h4 {
    font-size: 1.2rem;
    margin-bottom: 20px;
}
.footer-col p, .footer-col a {
    color: #bdc3c7;
    display: block;
    margin-bottom: 10px;
    font-size: 0.9rem;
}
.footer-col a:hover {
    color: var(--text-light);
}
.footer-logo {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.footer-copyright {
    text-align: center;
    padding-top: 20px;
    font-size: 0.9rem;
    color: #bdc3c7;
}
/* Remaining CSS from your original file is implicitly kept */
        </style>
</head>
<body>

 <header class="header">
    <nav class="navbar container">
        <a href="#" class="nav-logo">NextRig</a>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="shop.php" class="nav-link">Shop</a></li>
            <li><a href="forum.php" class="nav-link">Forum</a></li>
            <li href="sell.php" class="nav-link">Sell</a></li>
            <li><a href="aboutus.php" class="nav-link">About Us</a></li>
            <li><a href="contact.php" class="nav-link">Contact</a></li>
        </ul>
        <div class="nav-icons">
            <i class="fas fa-search"></i>
            <i class="fas fa-shopping-cart"></i>
            <i class="fas fa-user"></i>

            <?php if(isset($_SESSION['first_name'])): ?>
                <a href="profile.php" class="nav-username"><?= htmlspecialchars($_SESSION['first_name']) ?></a>
                <a href="logout.php" class="nav-logout">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-light nav-login" >Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

    <section class="hero">
        <div class="hero-slider-content">
            <?php foreach ($hero_slides as $index => $slide): ?>
                <div class="hero-slide-item <?= $index === 0 ? 'active-hero-slide' : '' ?>" data-hero-index="<?= $index ?>">
                    <h1><?= $slide['title'] ?></h1>
                    <p><?= $slide['subtitle'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="hero-dots">
            <?php foreach ($hero_slides as $index => $slide): ?>
                <span class="dot <?= $index === 0 ? 'active' : '' ?>" data-dot-index="<?= $index ?>"></span>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="hot-deals container">
        <h2>Hot Deals</h2>
        <div class="deal-slider">
            <i class="fas fa-chevron-left slider-arrow" id="prevDeal"></i>
            
            <?php foreach ($featuredProducts as $index => $f): ?>
                <a href="product.php?id=<?= $f['product_id'] ?>" class="deal-content <?= $index === 0 ? 'active-deal' : '' ?>" data-index="<?= $index ?>">
                    <img src="<?= htmlspecialchars($f['image_url']) ?>" alt="<?= htmlspecialchars($f['name']) ?>" class="deal-image" style="display:none;">
                    <h3><?= htmlspecialchars($f['name']) ?> - ₹<?= htmlspecialchars($f['price']) ?></h3>
                    <p><?= htmlspecialchars(substr($f['description'], 0, 70)) ?>...</p>
                </a>
            <?php endforeach; ?>
            
            <i class="fas fa-chevron-right slider-arrow" id="nextDeal"></i>
        </div>
    </section>
    
    <div class="chat-bubble">
        <i class="fas fa-comment-dots"></i>
    </div>

    <main class="main-content container">
        <section class="shop-section">
            <div class="shop-header">
                <h2>Shop</h2>
                <form method="GET" class="shop-filters">
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $categoryFilter == $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="">Sort By Price</option>
                        <option value="low-high" <?= $sortFilter == 'low-high' ? 'selected' : '' ?>>Low to High</option>
                        <option value="high-low" <?= $sortFilter == 'high-low' ? 'selected' : '' ?>>High to Low</option>
                    </select>
                </form>
            </div>
            
            <div class="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <div class="product-card">
                            <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="product-image">
                            <h3><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="description"><?= htmlspecialchars(substr($p['description'], 0, 80)) ?>...</p>
                            <div class="product-card-footer">
                                <span class="product-price">₹<?= htmlspecialchars($p['price']) ?></span>
                                <a href="product.php?id=<?= $p['product_id'] ?>" class="btn btn-dark">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products found matching your criteria.</p>
                <?php endif; ?>
            </div>
            
            <div class="view-more-container" style="text-align: center; margin-top: 40px;">
                <a href="shop.php" class="btn btn-dark" style="padding: 12px 30px;">View More Products</a>
            </div>
            
        </section>

        <aside class="forum-section">
            <div class="forum-header">
                <h2>Forum</h2>
            </div>
            <div class="forum-posts">
                <?php foreach ($forumPosts as $post): ?>
                    <article class="forum-post">
                        <div class="avatar"></div>
                        <div class="post-content">
                            <p>
                                <span class="post-author"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></span>
                                <span class="post-time"><?= date("M d, H:i", strtotime($post['created_at'])) ?></span>
                            </p>
                            <h4 class="post-title"><?= htmlspecialchars($post['title']) ?></h4>
                            <p class="post-excerpt"><?= htmlspecialchars(substr($post['content'], 0, 80)) ?>...</p>
                            <div class="post-stats">
                                <span><i class="fas fa-comment"></i>12 replies</span>
                                <span><i class="fas fa-thumbs-up"></i>8 likes</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <a href="forum.php" class="btn btn-new-discussion">+ New Discussion</a>
        </aside>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4 class="footer-logo">NextRig</h4>
                    <p>Your trusted marketplace for pre-owned PC Components.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <a href="#">Shop</a>
                    <a href="#">Forum</a>
                    <a href="#">About Us</a>
                    <a href="#">Contact</a>
                </div>
                <div class="footer-col">
                    <h4>Categories</h4>
                    <a href="#">Graphics Cards</a>
                    <a href="#">Processors</a>
                    <a href="#">Memory</a>
                    <a href="#">Storage</a>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <a href="#">Help Center</a>
                    <a href="#">Return Information</a>
                    <a href="#">Seller Guidelines</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            <p class="footer-copyright">&copy; 2025 NextRig. All rights reserved.</p>
            <a href="https://youtube.com/shorts/QtUWMlA-W4s?si=Jbb-nQEzWEx08XmI" class="special">i</a>
        </div>
    </footer>

    <script>
        // --- HOT DEALS SLIDER LOGIC ---
        const dealContents = document.querySelectorAll('.deal-content');
        const prevDealBtn = document.getElementById('prevDeal');
        const nextDealBtn = document.getElementById('nextDeal');
        let currentDeal = 0;

        function showDeal(index) {
            dealContents.forEach((deal, i) => {
                if (i === index) {
                    deal.classList.add('active-deal');
                    deal.style.transform = 'translateX(0)';
                } else {
                    deal.classList.remove('active-deal');
                    
                    if (i < index) {
                        deal.style.transform = 'translateX(-100%)'; 
                    } else {
                        deal.style.transform = 'translateX(100%)'; 
                    }
                }
            });
        }

        function nextDeal() {
            currentDeal = (currentDeal + 1) % dealContents.length;
            showDeal(currentDeal);
        }

        function prevDeal() {
            currentDeal = (currentDeal - 1 + dealContents.length) % dealContents.length;
            showDeal(currentDeal);
        }

        // Manual controls
        if (nextDealBtn) nextDealBtn.addEventListener('click', nextDeal);
        if (prevDealBtn) prevDealBtn.addEventListener('click', prevDeal);

        // Auto-advance
        setInterval(nextDeal, 5000);
        showDeal(currentDeal);
        
        // --- HERO SLIDER LOGIC ---
        const heroSlides = document.querySelectorAll('.hero-slide-item');
        const heroDots = document.querySelectorAll('.hero-dots .dot');
        let currentHeroSlide = 0;
        
        function showHeroSlide(index) {
            heroSlides.forEach((slide, i) => {
                heroDots[i].classList.remove('active');
                if (i === index) {
                    slide.classList.add('active-hero-slide');
                    slide.style.opacity = '1';
                    heroDots[i].classList.add('active');
                } else {
                    slide.classList.remove('active-hero-slide');
                    slide.style.opacity = '0';
                }
            });
        }

        function nextHeroSlide() {
            currentHeroSlide = (currentHeroSlide + 1) % heroSlides.length;
            showHeroSlide(currentHeroSlide);
        }
        
        // Dot navigation
        heroDots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentHeroSlide = index;
                showHeroSlide(currentHeroSlide);
            });
        });

        // Auto-advance
        setInterval(nextHeroSlide, 5000); 
        showHeroSlide(currentHeroSlide);
    </script>
</body>
</html>
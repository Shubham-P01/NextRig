<?php
// Include the PDO database configuration file.
require 'connection.php';

// Start the session to access session variables
session_start();

// --- HANDLE ALL FORM SUBMISSIONS ON THIS PAGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Logic for Adding a Product to the Cart (NOW FIXED) ---
    if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])) {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        // Use the logged-in user's ID from the session
        $current_user_id = $_SESSION['user_id'] ?? null; 

        // Important: Only proceed if the user is actually logged in
        if ($current_user_id && $product_id > 0 && $quantity > 0) {

            // Find or create a shopping cart
            $cart_sql = "SELECT cart_id FROM shopping_cart WHERE user_id = :user_id";
            $cart_stmt = $pdo->prepare($cart_sql);
            $cart_stmt->execute(['user_id' => $current_user_id]);
            $cart = $cart_stmt->fetch();
            $cart_id = $cart ? $cart['cart_id'] : null;

            if (!$cart_id) {
                $insert_cart_sql = "INSERT INTO shopping_cart (user_id) VALUES (:user_id)";
                $pdo->prepare($insert_cart_sql)->execute(['user_id' => $current_user_id]);
                $cart_id = $pdo->lastInsertId();
            }
            
            // **THE FIX:** Check if item exists, then UPDATE or INSERT.
            $item_sql = "SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id";
            $item_stmt = $pdo->prepare($item_sql);
            $item_stmt->execute(['cart_id' => $cart_id, 'product_id' => $product_id]);
            $existing_item = $item_stmt->fetch();

            if ($existing_item) {
                // If it exists, UPDATE the quantity
                $new_quantity = $existing_item['quantity'] + $quantity;
                $update_sql = "UPDATE cart_items SET quantity = :quantity WHERE cart_item_id = :cart_item_id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute(['quantity' => $new_quantity, 'cart_item_id' => $existing_item['cart_item_id']]);
            } else {
                // If it's a new item, INSERT it
                $insert_item_sql = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)";
                $insert_item_stmt = $pdo->prepare($insert_item_sql);
                $insert_item_stmt->execute(['cart_id' => $cart_id, 'product_id' => $product_id, 'quantity' => $quantity]);
            }
            
            // Redirect based on button clicked
            if (isset($_POST['buy_now'])) {
                header('Location: checkout.php');
            } else {
                header('Location: cart.php');
            }
            exit();
        }
    }

    // --- Other form submissions (questions, replies) remain the same ---
    if (isset($_POST['submit_question'])) {
        $question_text = trim($_POST['question'] ?? '');
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if (!empty($question_text) && $product_id > 0) {
            $current_user_id = 3; 
            $sql = "INSERT INTO product_questions (product_id, user_id, question) VALUES (:product_id, :user_id, :question)";
            $pdo->prepare($sql)->execute(['product_id' => $product_id, 'user_id' => $current_user_id, 'question' => $question_text]);
            header('Location: product.php?id=' . $product_id);
            exit();
        }
    }
    if (isset($_POST['submit_answer'])) {
        $answer_text = trim($_POST['answer'] ?? '');
        $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
        $parent_answer_id = isset($_POST['parent_answer_id']) && !empty($_POST['parent_answer_id']) ? (int)$_POST['parent_answer_id'] : null;
        $product_id_redirect = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if (!empty($answer_text) && $question_id > 0 && $product_id_redirect > 0) {
            $replying_user_id = 1; 
            $sql = "INSERT INTO product_answers (question_id, user_id, answer, parent_answer_id) VALUES (:question_id, :user_id, :answer, :parent_answer_id)";
            $pdo->prepare($sql)->execute(['question_id' => $question_id, 'user_id' => $replying_user_id, 'answer' => $answer_text, 'parent_answer_id' => $parent_answer_id]);
            header('Location: product.php?id=' . $product_id_redirect);
            exit();
        }
    }
}

// --- DISPLAY PRODUCT PAGE (Normal GET Request) ---
// ... (The rest of the file remains exactly the same as before) ...
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) { die("Error: Invalid Product ID."); }
// Fetch product data...
$sql = "SELECT 
    p.*, 
    c.name AS category_name, 
    b.name AS brand_name 
FROM products p 
JOIN categories c ON p.category_id = c.category_id 
LEFT JOIN brands b ON p.brand_id = b.brand_id 
WHERE p.product_id = :product_id AND p.status = 'Available'";
$stmt = $pdo->prepare($sql);
$stmt->execute(['product_id' => $product_id]);
$product = $stmt->fetch();
if (!$product) { die("Product not found or is unavailable."); }
$specifications = json_decode($product['specifications'], true);
$image_sql = "SELECT image_url, is_main_image FROM product_images WHERE product_id = :product_id ORDER BY is_main_image DESC";
$image_stmt = $pdo->prepare($image_sql);
$image_stmt->execute(['product_id' => $product_id]);
$images = $image_stmt->fetchAll();
$main_image = $images[0]['image_url'] ?? 'https://placehold.co/600x450/e0e0e0/777?text=No+Image';
$thumbnails = $images;
$questions_sql = "SELECT q.question_id, q.question, q.created_at, u.first_name AS questioner_name FROM product_questions q JOIN users u ON q.user_id = u.user_id WHERE q.product_id = :product_id ORDER BY q.created_at DESC";
$questions_stmt = $pdo->prepare($questions_sql);
$questions_stmt->execute(['product_id' => $product_id]);
$questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
$question_ids = array_column($questions, 'question_id');
$answers_by_question = [];
if (!empty($question_ids)) {
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    $answers_sql = "SELECT a.answer_id, a.question_id, a.parent_answer_id, a.answer, a.created_at, u.first_name AS answerer_name FROM product_answers a JOIN users u ON a.user_id = u.user_id WHERE a.question_id IN ($placeholders) ORDER BY a.created_at ASC";
    $answers_stmt = $pdo->prepare($answers_sql);
    $answers_stmt->execute($question_ids);
    $all_answers = $answers_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_answers as $answer) {
        $answers_by_question[$answer['question_id']][] = $answer;
    }
}
function display_answers($all_answers, $parent_id, $product_id, $question_id) {
    echo '<div class="replies">';
    foreach ($all_answers as $answer) {
        if ($answer['parent_answer_id'] == $parent_id) {
            echo '<div class="answer">';
            echo '    <div class="avatar"></div>';
            echo '    <div class="qa-content">';
            echo '        <p><strong>' . htmlspecialchars($answer['answerer_name']) . '</strong> <span class="time">' . date('M j, Y', strtotime($answer['created_at'])) . '</span></p>';
            echo '        <p>' . htmlspecialchars($answer['answer']) . '</p>';
            echo '        <a class="reply-link" onclick="toggleReplyForm(' . $question_id . ', ' . $answer['answer_id'] . ')">Reply</a>';
            echo '    </div>';
            echo '</div>';
            echo '<div class="answer reply-form-container" id="reply-form-q' . $question_id . '-a' . $answer['answer_id'] . '" style="display: none;">';
            echo '    <div class="avatar"></div>';
            echo '    <div class="qa-content">';
            echo '        <form action="product.php?id=' . $product_id . '" method="POST">';
            echo '            <input type="hidden" name="product_id" value="' . $product_id . '">';
            echo '            <input type="hidden" name="question_id" value="' . $question_id . '">';
            echo '            <input type="hidden" name="parent_answer_id" value="' . $answer['answer_id'] . '">';
            echo '            <textarea name="answer" rows="2" placeholder="Add a public reply..." required></textarea>';
            echo '            <button type="submit" name="submit_answer" class="btn btn-primary">Reply</button>';
            echo '        </form>';
            echo '    </div>';
            echo '</div>';
            display_answers($all_answers, $answer['answer_id'], $product_id, $question_id);
        }
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - NextRig</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/4a24449835.js" crossorigin="anonymous"></script>
    <style>
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
    font-weight: 500;
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
        *{
            font-family: 'Poppins', sans-serif;
        }
        .ask-question-form textarea, .reply-form-container textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: 'Poppins', sans-serif; margin-bottom: 10px; resize: vertical; }
        .reply-link { font-size: 0.9rem; font-weight: 500; color: var(--text-muted); cursor: pointer; }
        .reply-link:hover { color: var(--text-dark); }
        .reply-form-container { margin-top: 15px; }
        .replies { padding-left: 20px; margin-left: 35px; border-left: 2px solid var(--border-color); }
        .replies:empty { display: none; }
    </style>
</head>
<body>
    <header class="navbar">
    <div class="nav-container">
        <a href="#" class="nav-logo">NextRig</a>
        <nav class="nav-menu">
            <a href="index.php" class="nav-link">Home</a>
            <a href="shop.php" class="nav-link active">Shop</a>
            <a href="form.php" class="nav-link">Forum</a>
            <a href="#" class="nav-link">About Us</a>
            <a href="#" class="nav-link">Contact</a>
        </nav>
        <div class="nav-icons">
    <a href="/search" title="Search">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </a>
    
    <a href="/account" title="My Account">
        <svg xmlns="http://www.w.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
    </a>

    <a href="cart.php" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
    </a>
</div>
    </div>
</header>
    <main class="container">
        <nav class="breadcrumbs">
             <a href="#">Home</a> &gt; <a href="shop.php">Shop</a> &gt; <a href="#"><?php echo htmlspecialchars($product['category_name']); ?></a> &gt; <span><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
        <section class="product-main-section">
            <div class="product-gallery">
                 <div class="main-image"><img id="main-product-image" src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:100%; height:100%; object-fit:contain;"></div>
                <div class="thumbnail-images">
                    <?php foreach ($thumbnails as $index => $thumb): ?>
                        <div class="thumb-img <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($thumb['image_url']); ?>', this)">
                             <img src="<?php echo htmlspecialchars($thumb['image_url']); ?>" alt="Thumbnail <?php echo $index + 1; ?>" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="product-details">
                <div class="product-header">
                    <span class="condition-tag"><?php echo htmlspecialchars($product['condition']); ?> Condition</span>
                    <button class="share-btn"><i class="fas fa-share-alt"></i> Share</button>
                </div>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="reviews"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i> <span>(Static)</span></div>
                <div class="price-info"><span class="current-price">₹<?php echo htmlspecialchars($product['price']); ?></span></div>
                <form action="product.php?id=<?php echo $product_id; ?>" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <span>Quantity</span>
                            <div class="selector">
                                <button type="button" id="qty-minus">-</button>
                                <input type="text" id="qty-input" name="quantity" value="1" readonly>
                                <button type="button" id="qty-plus">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="submit" name="buy_now" class="btn btn-primary">Buy Now</button>
                        <button type="submit" name="add_to_cart" class="btn btn-secondary">Add to Cart</button>
                    </div>
                </form>
            </div>
        </section>
        <section class="product-description-section">
            <div class="product-description-content">
                <h3>Product Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            <aside class="product-specs-sidebar">
                <div class="policies card">
                    <h4>Policies</h4>
                    <ul>
                        <li><i class="fas fa-undo-alt"></i> 30-day replace policy</li>
                        <li><i class="fas fa-shipping-fast"></i>Qulity checks</li>
                        <li><i class="fas fa-shield-alt"></i> Authenticity guaranteed</li>
                    </ul>
                </div>
                <div class="specs card">
                    <h3>Specifications</h3>
                    <div class="spec-item"><span>Brand:</span> <span><?php echo htmlspecialchars($product['brand_name']); ?></span></div>
                    <div class="spec-item"><span>Condition:</span> <span><?php echo htmlspecialchars($product['condition']); ?></span></div>
                    <?php if ($specifications): foreach ($specifications as $key => $value): ?>
                        <div class="spec-item">
                            <span><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:</span>
                            <span><?php echo htmlspecialchars($value); ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </aside>
        </section>
        <section class="product-questions">
            <div class="questions-header"><h3>Product Questions</h3></div>
            <div class="ask-question-form card">
                 <form action="product.php?id=<?php echo $product_id; ?>" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <textarea name="question" rows="3" placeholder="Ask a question..." required></textarea>
                    <button type="submit" name="submit_question" class="btn btn-primary">Ask Question</button>
                </form>
            </div>
            <div class="qa-list">
                 <?php if ($questions): foreach ($questions as $question): ?>
                    <div class="qa-item">
                        <div class="question">
                            <div class="avatar"></div>
                            <div class="qa-content">
                                <p><strong><?php echo htmlspecialchars($question['questioner_name']); ?></strong> <span class="time"><?php echo date('M j, Y', strtotime($question['created_at'])); ?></span></p>
                                <p><?php echo htmlspecialchars($question['question']); ?></p>
                                <a class="reply-link" onclick="toggleReplyForm(<?php echo $question['question_id']; ?>, null)">Reply</a>
                            </div>
                        </div>
                        <div class="answer reply-form-container" id="reply-form-q<?php echo $question['question_id']; ?>-a" style="display: none;">
                            <div class="avatar"></div>
                            <div class="qa-content">
                                <form action="product.php?id=<?php echo $product_id; ?>" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                                    <textarea name="answer" rows="2" placeholder="Add a public reply..." required></textarea>
                                    <button type="submit" name="submit_answer" class="btn btn-primary">Reply</button>
                                </form>
                            </div>
                        </div>
                        <?php 
                        if (isset($answers_by_question[$question['question_id']])) {
                            display_answers($answers_by_question[$question['question_id']], null, $product_id, $question['question_id']);
                        }
                        ?>
                    </div>
                <?php endforeach; else: ?>
                    <p>Be the first to ask a question!</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <footer class="footer"></footer>
    <script>
        function changeImage(imageUrl, clickedElement) {
            document.getElementById('main-product-image').src = imageUrl;
            document.querySelectorAll('.thumb-img').forEach(thumb => thumb.classList.remove('active'));
            clickedElement.classList.add('active');
        }
        function toggleReplyForm(questionId, answerId) {
            const formId = answerId ? `reply-form-q${questionId}-a${answerId}` : `reply-form-q${questionId}-a`;
            const formContainer = document.getElementById(formId);
            if (formContainer) {
                formContainer.style.display = (formContainer.style.display === 'none') ? 'flex' : 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const qtyInput = document.getElementById('qty-input');
            const qtyPlus = document.getElementById('qty-plus');
            const qtyMinus = document.getElementById('qty-minus');
            if(qtyPlus) qtyPlus.addEventListener('click', () => { qtyInput.value = parseInt(qtyInput.value) + 1; });
            if(qtyMinus) qtyMinus.addEventListener('click', () => { if (qtyInput.value > 1) qtyInput.value = parseInt(qtyInput.value) - 1; });
        });
    </script>
</body>
</html>


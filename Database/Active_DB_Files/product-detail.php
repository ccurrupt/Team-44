<?php
// Start the session - needed for login and cart
session_start();

// Check if user is logged in by looking for user_id in session
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

// Set up cart if it doesn't exist yet
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate cart count for the icon
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += $item['quantity'];
}

// Handle cart actions (add, update, remove items)
if (isset($_GET['action'])) {
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    switch ($_GET['action']) {
        case 'add':
            // Add item to cart
            if (isset($_GET['id'], $_GET['size'])) {
                $product_id = intval($_GET['id']);
                $size = $_GET['size'];
                $name = $_GET['name'] ?? 'Product';
                $price = floatval($_GET['price']);
                $image = $_GET['image'] ?? 'images/placeholder.jpg';
                
                // Check if item already in cart (same product + size)
                $item_exists = false;
                foreach ($_SESSION['cart'] as $index => $item) {
                    if ($item['id'] == $product_id && $item['size'] == $size) {
                        $_SESSION['cart'][$index]['quantity']++; // Increase quantity
                        $item_exists = true;
                        break;
                    }
                }
                
                // If not in cart, add as new item
                if (!$item_exists) {
                    $_SESSION['cart'][] = [
                        'id' => $product_id,
                        'name' => $name,
                        'price' => $price,
                        'size' => $size,
                        'image' => $image,
                        'quantity' => 1
                    ];
                }
                
                // Update cart count
                $cartCount++;
                
                // Go back to product page (prevents adding twice if page refreshes)
                header('Location: product-detail.php?id=' . $product_id);
                exit();
            }
            break;
            
        case 'update':
            // Change quantity of cart item
            if (isset($_GET['index'], $_GET['change'])) {
                $index = intval($_GET['index']);
                $change = intval($_GET['change']);
                
                if (isset($_SESSION['cart'][$index])) {
                    $_SESSION['cart'][$index]['quantity'] += $change;
                    
                    // Remove item if quantity becomes 0 or less
                    if ($_SESSION['cart'][$index]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$index]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']); // Fix array indexes
                    }
                }
                
                // Recalculate cart count
                $cartCount = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
                
                header('Location: product-detail.php?' . http_build_query($_GET));
                exit();
            }
            break;
            
        case 'remove':
            // Remove item from cart
            if (isset($_GET['index'])) {
                $index = intval($_GET['index']);
                if (isset($_SESSION['cart'][$index])) {
                    unset($_SESSION['cart'][$index]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']); // Fix array indexes
                }
                
                // Recalculate cart count
                $cartCount = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
                
                header('Location: product-detail.php?' . http_build_query($_GET));
                exit();
            }
            break;
            
        case 'clear':
            // Empty the entire cart
            $_SESSION['cart'] = [];
            $cartCount = 0;
            header('Location: product-detail.php?' . http_build_query($_GET));
            exit();
            break;
    }
}

// Database connection details
$host = 'localhost';
$dbname = 'cs2team44_db';
$username = 'cs2team44';
$password = 'wpRwMNcuA4uajOG92dzRRqbhb';

// Try to connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null; // No database connection
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$product = [];
$images = [];

// Get product data from database
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Make sure we have rating field (some DBs call it 'rating', we use 'category_rating')
            if (isset($product['rating']) && !isset($product['category_rating'])) {
                $product['category_rating'] = $product['rating'];
            }
            
            // Collect all product images, avoid duplicates
            $images = [];
            $columns = ['image_main', 'image_1', 'image_2', 'image_3'];

            foreach ($columns as $col) {
                if (!empty($product[$col]) && !in_array($product[$col], $images, true)) {
                    $images[] = $product[$col];
                }
            }
        }
    } catch (Exception $e) {
        // If DB fails, we'll use dummy data
    }
}

// If no product data, use dummy data for testing
if (empty($product)) {
    $product = [
        'product_id' => 1,
        'name' => 'Boxy Logo T-Shirt - Black',
        'price' => 14.00,
        'description' => 'Discover your new everyday essential...',
        'rating' => 4.8,
        'category_rating' => 4.8,
        'materials' => 'Cotton, Recycled Polyester',
        'is_sustainable' => 1,
        'stock_quantity' => 50
    ];
    $images = [
        'images/tshirts1.png',
        'images/tshirts2.png',
        'images/tshirts3.png',
        'images/tshirts4.png'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - EveryWear</title>
    
    <!-- Icons library -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
    
    <style>
        /* Basic reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        /* HEADER - Matching productdescrip.html */
        .navbar {
            width: 100%;
            background: white;
            height: auto;
            padding: 4px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .navbar::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, #30CDF5, #00FAA0);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 250px;
            max-width: 300px;
        }

        .logo-section img {
            height: 90px;
            width: 240px;
        }

        /* NAV BUTTONS */
        .nav-buttons {
            display: flex;
            gap: 20px;
            flex: 1 1 auto;
            min-width: 0;
        }

        /* NAV BUTTONS — gradient hover + scale */
        .nav-buttons a.nav-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 25px;
            background: #e7e9eb;
            border-radius: 7px;
            text-decoration: none;
            color: inherit;
            font-size: 14px;
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
            transform: translateY(-2px);
        }

        .nav-buttons a.nav-button:hover,
        .nav-buttons a.nav-button:focus {
            background: linear-gradient(to right, #30CDF5, #00FAA0);
            color: #000;
            transform: translateY(-2px) scale(1.07);
            outline: none;
        }

        /* RIGHT CONTROL BUTTONS */
        .right-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            position: relative;
        }

        /* DEFAULT RIGHT SIDE */
        .right-default {
            display: flex;
            align-items: center;
            gap: 12px;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        /* LOGIN BUTTON */
        .login-btn {
            background: #4b74ff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
            transform: translateY(-2px);
            text-decoration: none;
            font-size: 14px;
        }

        .login-btn:hover {
            background: linear-gradient(to right, #30CDF5, #00FAA0);
            color: #000;
            transform: translateY(-2px) scale(1.07);
        }

        .login-btn:active { transform: translateY(0); }

        /* CREATE ACCOUNT BUTTON */
        .create-btn {
            background: black;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
            transform: translateY(-2px);
            text-decoration: none;
            font-size: 14px;
        }

        .create-btn:hover {
            background: linear-gradient(to right, #30CDF5, #00FAA0);
            color: #000;
            transform: translateY(-2px) scale(1.07);
        }

        .create-btn:active { transform: translateY(0); }

        /* ICON WRAPPER LINK */
        .icon-link {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            width: 36px;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.25s ease, opacity 0.25s ease;
            position: relative;
        }

        /* PNG ICON IMAGE */
        .nav-icon {
            width: 22px;
            height: 22px;
            object-fit: contain;
            pointer-events: none;
        }

        /* HOVER EFFECT */
        .icon-link:hover {
            transform: scale(1.15);
            opacity: 0.9;
        }

        /* Cart count badge */
        .cart-count-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* SEARCH BAR OVERLAY */
        .search-bar-overlay {
            position: absolute;
            right: 36px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
            transition: width 0.3s ease, opacity 0.2s ease;
        }

        .search-bar-overlay input {
            width: 320px;
            height: 36px;
            padding: 0 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        /* When shown */
        .search-bar-overlay.active {
            width: 320px;
            opacity: 1;
            pointer-events: auto;
        }

        /* Close icon sits exactly where the normal search icon was */
        .search-close {
            position: absolute;
            height: 36px;
            width: 36px;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            display: none;
            z-index: 60;
            align-items: center;
            justify-content: center;
        }

        .search-bar-overlay.active + .search-close {
            display: flex;
        }

        .search-close:hover {
            transform: translateY(-50%) scale(1.15) !important;
        }

        .hidden {
            opacity: 0;
            transform: scale(0.95);
            pointer-events: none;
        }
        
        /* Main content area */
        .product-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: flex;
            gap: 60px;
        }
        
        /* Left column - images */
        .product-images {
            flex: 1;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Image thumbnails */
        .thumbnail-row {
            display: flex;
            gap: 15px;
        }
        
        .thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .thumbnail:hover {
            border: 2px solid #333;
        }
        
        /* Right column - product info */
        .product-info {
            flex: 1;
        }
        
        .product-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        /* Star rating */
        .rating {
            color: #ffc107;
            margin-bottom: 15px;
        }
        
        /* Price */
        .price {
            font-size: 28px;
            font-weight: bold;
            color: #000;
            margin: 20px 0;
        }
        
        /* Stock status */
        .stock-info {
            margin: 15px 0;
            font-size: 14px;
        }
        
        .in-stock { color: green; }
        .low-stock { color: orange; }
        .out-of-stock { color: red; }
        
        /* Green eco badge */
        .sustainable-badge {
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        /* Size buttons */
        .size-options {
            margin: 25px 0;
        }
        
        .size-options p {
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .size-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .size-btn:hover {
            background: #f0f0f0;
        }
        
        .size-btn.active {
            background: black;
            color: white;
            border-color: black;
        }
        
        /* Add to cart button */
        .add-to-cart {
            width: 100%;
            padding: 15px;
            background: black;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 25px 0;
            transition: 0.3s;
        }
        
        .add-to-cart:hover {
            background: #333;
        }
        
        .add-to-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Description text */
        .description {
            margin-top: 30px;
            line-height: 1.6;
        }
        
        .description h3 {
            margin-bottom: 15px;
        }
        
        /* Cart sidebar (hidden by default) */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 350px;
            height: 100%;
            background: white;
            padding: 25px;
            box-shadow: -2px 0 10px rgba(0,0,0,0.2);
            transition: 0.3s;
            z-index: 1001;
            overflow-y: auto;
        }
        
        .cart-sidebar.active {
            right: 0;
        }
        
        /* Individual cart item */
        .cart-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .cart-details {
            flex: 1;
        }
        
        .cart-remove {
            color: #ff4757;
            cursor: pointer;
        }
        
        /* Quantity +/- buttons */
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }
        
        .qty-btn {
            width: 25px;
            height: 25px;
            border: 1px solid #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        /* Close cart button */
        .close-cart {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Checkout button */
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: black;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        /* Footer at bottom */
        footer {
            background: #111;
            color: white;
            padding: 50px 20px 30px;
            margin-top: 80px;
        }
        
        .footer-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }
        
        .footer-col h4 {
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col li {
            margin-bottom: 10px;
        }
        
        .footer-col a {
            color: #aaa;
            text-decoration: none;
        }
        
        .footer-col a:hover {
            color: white;
        }
        
        /* Social media icons */
        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .social-icons i {
            font-size: 20px;
            cursor: pointer;
        }
        
        /* Copyright text */
        .copyright {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #888;
            font-size: 14px;
        }
        
        /* Make page work on tablets */
        @media (max-width: 900px) {
            .product-page {
                flex-direction: column;
            }
            
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Make page work on phones */
        @media (max-width: 600px) {
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>

<!-- HEADER - Matching productdescrip.html exactly -->
<div class="navbar">
    <div class="logo-section">
        <a href="index.php">
            <img src="logo.png" alt="Logo">
        </a>
    </div>

    <div class="nav-buttons">
        <a href="about.php" class="nav-button">About Us</a>
        <a href="products.php" class="nav-button">Products</a>
        <a href="reviews.php" class="nav-button">Reviews</a>
        <a href="orders.php" class="nav-button">Orders</a>
    </div>

    <div class="right-controls" id="rightControls">
        <div class="right-default" id="rightDefault">
            <?php if($isLoggedIn): ?>
                <!-- user is logged in -->
                <span style="margin-right: 10px;">Hi <?php echo htmlspecialchars($userName); ?>!</span>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <!-- user not logged in -->
                <a href="login.php" class="login-btn">Log in</a>
                <a href="create-account.php" class="create-btn">Create Account</a>
            <?php endif; ?>

            <a id="cartToggle" class="icon-link">
                <img src="basket.png" class="nav-icon">
                <?php if($cartCount > 0): ?>
                    <span class="cart-count-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>

            <div id="searchToggle" class="icon-link">
                <img src="search.png" class="nav-icon">
            </div>
        </div>

        <div id="searchBar" class="search-bar-overlay">
            <input type="text" placeholder="Search...">
        </div>

        <div id="searchClose" class="icon-link search-close">
            <img src="search.png" class="nav-icon">
        </div>
    </div>
</div>

<!-- Shopping cart sidebar (slides in from right) -->
<div class="cart-sidebar" id="cartSidebar">
    <h2>Your Cart</h2>
    
    <div class="cart-items">
        <?php if(empty($_SESSION['cart'])): ?>
            <!-- Empty cart message -->
            <p style="color: #666; margin-top: 20px;">Your cart is empty</p>
        <?php else: ?>
            <!-- Show all cart items -->
            <?php 
            $total = 0;
            foreach($_SESSION['cart'] as $index => $item): 
                $item_total = $item['price'] * $item['quantity'];
                $total += $item_total;
            ?>
                <div class="cart-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="cart-details">
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                        Size: <?php echo htmlspecialchars($item['size']); ?><br>
                        £<?php echo number_format($item['price'], 2); ?>
                        <div class="qty-controls">
                            <!-- Decrease quantity -->
                            <span class="qty-btn" onclick="updateQty(<?php echo $index; ?>, -1)">-</span>
                            <span><?php echo $item['quantity']; ?></span>
                            <!-- Increase quantity -->
                            <span class="qty-btn" onclick="updateQty(<?php echo $index; ?>, 1)">+</span>
                        </div>
                    </div>
                    <!-- Remove from cart -->
                    <div class="cart-remove" onclick="removeItem(<?php echo $index; ?>)">
                        <i class="ri-delete-bin-line"></i>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Cart total and checkout button -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                <h3>Total: £<?php echo number_format($total, 2); ?></h3>
                <a href="checkout.php" class="checkout-btn">Go to Checkout</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- X button to close cart -->
    <i class="ri-close-line close-cart" id="closeCart"></i>
</div>

<!-- Main product content -->
<div class="product-page">
    <!-- Left: Product images -->
    <div class="product-images">
        <?php if(!empty($images)): ?>
            <!-- Main big image -->
            <img src="<?php echo htmlspecialchars($images[0]); ?>" class="main-image" id="mainImage">
            
            <!-- Small thumbnail images -->
            <div class="thumbnail-row">
                <?php foreach($images as $index => $img): ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" 
                         class="thumbnail" 
                         onclick="changeImage(this, <?php echo $index; ?>)">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- If no images, show placeholder -->
            <img src="images/placeholder.jpg" class="main-image" alt="No image">
        <?php endif; ?>
    </div>
    
    <!-- Right: Product info -->
    <div class="product-info">
        <h1 class="product-title">
            <?php echo htmlspecialchars($product['name']); ?>
            <!-- Show green badge if eco-friendly -->
            <?php if($product['is_sustainable'] == 1): ?>
                <span class="sustainable-badge">Eco-Friendly</span>
            <?php endif; ?>
        </h1>
        
        <!-- Star rating -->
        <div class="rating">
            <?php 
            $avg_rating = $product['category_rating'];
            $review_count = 124; // Fixed number (not from database)
            
            // Show 5 stars based on rating
            for($i = 1; $i <= 5; $i++): 
                if ($i <= $avg_rating) {
                    // Full star
                    echo '<i class="ri-star-fill" style="color: #ffc107;"></i>';
                } elseif ($i - 0.5 <= $avg_rating) {
                    // Half star
                    echo '<i class="ri-star-half-line" style="color: #ffc107;"></i>';
                } else {
                    // Empty star
                    echo '<i class="ri-star-line" style="color: #ffc107;"></i>';
                }
            endfor; 
            ?>
            
            <!-- Rating number and review count -->
            <span style="color: #333; margin-left: 5px;">
                <?php echo number_format($avg_rating, 1); ?> 
                (<?php echo $review_count; ?> reviews)
            </span>
        </div>
        
        <!-- Price -->
        <div class="price">£<?php echo number_format($product['price'], 2); ?></div>
        
        <!-- Stock status -->
        <div class="stock-info">
            <?php 
            $stock = $product['stock_quantity'];
            if($stock > 20): ?>
                <!-- Good stock -->
                <span class="in-stock">✓ In Stock (<?php echo $stock; ?> available)</span>
            <?php elseif($stock > 0): ?>
                <!-- Low stock warning -->
                <span class="low-stock">⚠ Only <?php echo $stock; ?> left!</span>
            <?php else: ?>
                <!-- Out of stock -->
                <span class="out-of-stock">✗ Out of Stock</span>
            <?php endif; ?>
        </div>
        
        <!-- Materials -->
        <?php if(!empty($product['materials'])): ?>
            <p style="margin: 15px 0;"><strong>Material:</strong> <?php echo htmlspecialchars($product['materials']); ?></p>
        <?php endif; ?>
        
        <!-- Size selection -->
        <div class="size-options">
            <p>Select Size:</p>
            <?php 
            $sizes = ['XS', 'S', 'M', 'L', 'XL'];
            foreach($sizes as $size): ?>
                <span class="size-btn" onclick="selectSize(this, '<?php echo $size; ?>')">
                    <?php echo $size; ?>
                </span>
            <?php endforeach; ?>
        </div>
        
        <!-- Add to cart button -->
        <button class="add-to-cart" id="addCartBtn" onclick="addToCart()"
                <?php if($stock <= 0) echo 'disabled'; ?>>
            <?php echo $stock > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
        </button>
        
        <!-- Product description -->
        <div class="description">
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>
        
        <!-- Extra product details table -->
        <div style="margin-top: 30px; background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h4>Product Details</h4>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">Product ID</td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $product['product_id']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">Materials</td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($product['materials']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">Sustainable</td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        <?php echo $product['is_sustainable'] ? 'Yes' : 'No'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px;">Stock</td>
                    <td style="padding: 8px;"><?php echo $stock; ?> units</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4>Shop</h4>
            <ul>
                <li><a href="#">Men</a></li>
                <li><a href="#">Women</a></li>
                <li><a href="#">Accessories</a></li>
                <li><a href="#">New Arrivals</a></li>
            </ul>
        </div>
        
        <div class="footer-col">
            <h4>Help</h4>
            <ul>
                <li><a href="#">Contact Us</a></li>
                <li><a href="#">Shipping Info</a></li>
                <li><a href="#">Returns</a></li>
                <li><a href="#">FAQ</a></li>
            </ul>
        </div>
        
        <div class="footer-col">
            <h4>About</h4>
            <ul>
                <li><a href="#">Our Story</a></li>
                <li><a href="#">Sustainability</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Press</a></li>
            </ul>
        </div>
        
        <div class="footer-col">
            <h4>Connect</h4>
            <div class="social-icons">
                <i class="ri-instagram-line"></i>
                <i class="ri-facebook-circle-line"></i>
                <i class="ri-twitter-line"></i>
                <i class="ri-tiktok-line"></i>
            </div>
            <p style="margin-top: 20px; color: #aaa; font-size: 14px;">
                Student project for university
            </p>
        </div>
    </div>
    
    <div class="copyright">
        &copy; 2025 EveryWear. This is a university project.
    </div>
</footer>

<script>
// JavaScript for interactive features

// Store product info in JavaScript variables
let selectedSize = null;
const productId = <?php echo $product['product_id']; ?>;
const productName = "<?php echo addslashes($product['name']); ?>";
const productPrice = <?php echo $product['price']; ?>;
const productStock = <?php echo $product['stock_quantity']; ?>;

// Switch main image when clicking thumbnails
function changeImage(img, index) {
    const mainImg = document.getElementById('mainImage');
    if (mainImg) {
        mainImg.src = img.src;
        
        // Remove border from all thumbnails
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.style.border = '2px solid transparent';
        });
        
        // Add border to clicked thumbnail
        img.style.border = '2px solid #333';
    }
}

// Select size button
function selectSize(btn, size) {
    // Remove active class from all size buttons
    document.querySelectorAll('.size-btn').forEach(b => {
        b.classList.remove('active');
    });
    
    // Add active class to clicked button
    btn.classList.add('active');
    selectedSize = size;
    
    // Update button text to show selected size
    const cartBtn = document.getElementById('addCartBtn');
    if (cartBtn && !cartBtn.disabled) {
        cartBtn.textContent = 'Add to Cart - Size: ' + size;
    }
}

// Add product to cart
function addToCart() {
    if (!selectedSize) {
        alert('Please select a size first!');
        return;
    }
    
    if (productStock <= 0) {
        alert('Sorry, out of stock!');
        return;
    }
    
    // Get first image to show in cart
    const firstImg = document.querySelector('.thumbnail');
    const productImg = firstImg ? firstImg.src : '<?php echo isset($images[0]) ? addslashes($images[0]) : "images/placeholder.jpg"; ?>';
    
    // Create URL to add item to cart
    const url = 'product-detail.php?' + 
                'action=add&' +
                'id=' + productId + 
                '&size=' + encodeURIComponent(selectedSize) + 
                '&name=' + encodeURIComponent(productName) + 
                '&price=' + productPrice + 
                '&image=' + encodeURIComponent(productImg);
    
    // Go to that URL (triggers PHP add to cart)
    window.location.href = url;
}

// Change quantity of cart item
function updateQty(index, change) {
    window.location.href = 'product-detail.php?action=update&index=' + index + '&change=' + change + '&id=' + productId;
}

// Remove item from cart
function removeItem(index) {
    if (confirm('Remove item from cart?')) {
        window.location.href = 'product-detail.php?action=remove&index=' + index + '&id=' + productId;
    }
}

// Show/hide cart sidebar
document.getElementById('cartToggle').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('cartSidebar').classList.add('active');
});

// Close cart sidebar
document.getElementById('closeCart').addEventListener('click', function() {
    document.getElementById('cartSidebar').classList.remove('active');
});

// Close cart if clicking outside of it
document.addEventListener('click', function(e) {
    const cart = document.getElementById('cartSidebar');
    const toggle = document.getElementById('cartToggle');
    
    if (cart.classList.contains('active') && 
        !cart.contains(e.target) && 
        e.target !== toggle && 
        !toggle.contains(e.target)) {
        cart.classList.remove('active');
    }
});

// Search functionality matching productdescrip.html
document.getElementById('searchToggle').onclick = function() {
    document.getElementById('rightDefault').classList.add('hidden');
    document.getElementById('searchBar').classList.add('active');
    document.getElementById('searchClose').classList.remove('hidden');
    document.getElementById('searchBar').querySelector('input').focus();
};

document.getElementById('searchClose').onclick = function() {
    document.getElementById('searchBar').classList.remove('active');
    document.getElementById('rightDefault').classList.remove('hidden');
    document.getElementById('searchClose').classList.add('hidden');
};

// Search on Enter key
document.querySelector('#searchBar input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const searchTerm = this.value.trim();
        if (searchTerm) {
            window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
        }
    }
});

// When page loads, auto-select first size
window.onload = function() {
    const firstSizeBtn = document.querySelector('.size-btn');
    if (firstSizeBtn) {
        const sizeText = firstSizeBtn.textContent.trim();
        selectSize(firstSizeBtn, sizeText);
    }
    
    // If out of stock, disable the button
    if (productStock <= 0) {
        const btn = document.getElementById('addCartBtn');
        if (btn) {
            btn.disabled = true;
            btn.style.cursor = 'not-allowed';
        }
    }
};
</script>

</body>
</html>
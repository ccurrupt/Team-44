<?php

require_once 'dbconfig.php';   // gives us $pdo and starts the session

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// flags for login state (used by header)
$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$userName   = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

// Only logged-in users can view this page
if (!$isLoggedIn) {
    header("Location: login.php?error=login_required");
    exit();
}

// Make sure the cart array exists so the header never breaks
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartCount = count($_SESSION['cart']);

// ---- Fetch this user's orders from the database ----
try {
    $stmt = $pdo->prepare("
        SELECT order_id, order_date, status, total_price
        FROM Orders
        WHERE user_id = :uid
        ORDER BY order_date DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Orders error: ' . $e->getMessage());
    $orders = [];
}

// Small helper for formatting money
function formatPrice(float $v): string {
    return '&pound;' . number_format($v, 2);
}

// Pre-calculate cart total for the sidebar
$cartTotal = 0.0;
foreach ($_SESSION['cart'] as $item) {
    $price    = isset($item['price']) ? (float)$item['price'] : 0;
    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
    $cartTotal += $price * $quantity;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - EveryWear</title>

    <!-- Global stylesheet + logo -->
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="images/logo.png">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet" />

    <style>
        /* Overall orders layout */
        .orders-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px 40px;
        }

        .orders-page h1 {
            margin-bottom: 8px;
        }

        .orders-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .orders-list {
            margin-top: 10px;
        }

        .order-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 16px 18px;
            margin: 10px 0;
            background: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-main {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-card h2 {
            margin: 0;
            font-size: 18px;
        }

        .order-meta {
            font-size: 14px;
            color: #666;
        }

        .order-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .order-total {
            font-size: 16px;
            font-weight: 700;
            color: #111;
        }

        .order-status {
            font-size: 13px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e6fbef;
            color: #176944;
            border: 1px solid #b7eed2;
            white-space: nowrap;
        }

        .empty-orders {
            margin-top: 15px;
            font-size: 15px;
            color: #555;
        }

        /* --- Cart sidebar styling --- */
        .cart-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 99;
        }
        .cart-overlay.active { display: block; }

        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -420px;
            width: 400px;
            height: 100%;
            background: #ffffff;
            padding: 60px 20px 20px;
            box-shadow: -2px 0 10px rgba(0,0,0,0.15);
            transition: right 0.35s ease;
            z-index: 100;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .cart-sidebar.active { right: 0; }

        .cart-sidebar h2 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .cart-item {
            display: flex;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .cart-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            margin-right: 12px;
            border-radius: 6px;
        }

        .cart-details {
            flex: 1;
            font-size: 13px;
        }

        .cart-details strong {
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
        }

        .cart-summary-total {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 2px solid #eee;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }

        .btn-view-cart {
            display: block;
            width: 100%;
            padding: 12px;
            background: #fff;
            color: #111827;
            text-align: center;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid #111827;
            transition: background 0.2s, color 0.2s;
            margin-top: 16px;
            margin-bottom: 8px;
        }
        .btn-view-cart:hover {
            background: #111827;
            color: #fff;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            padding: 12px 16px;
            background: #111827;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .checkout-btn:hover { background: #000; }

        #cart-close {
            position: absolute;
            top: 18px;
            right: 18px;
            font-size: 28px;
            cursor: pointer;
            color: #333;
        }
        #cart-close:hover { color: #000; }

        .cart-empty-msg {
            color: #999;
            font-size: 14px;
            margin-top: 20px;
            text-align: center;
        }

        /* Dark mode toggle */
        .theme-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 4px 6px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.25s ease;
        }
        .theme-toggle-btn:hover { transform: scale(1.15); }

        /* Back to top */
        #backToTop {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: none;
            background: black;
            color: white;
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.35);
            opacity: 0;
            transform: translateY(8px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
            z-index: 40;
        }
        #backToTop.is-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        #backToTop:hover { background: #111827; }

        @media (max-width: 600px) {
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
            .order-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .order-right {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <!-- TOP NAVIGATION -->
    <div class="navbar">

        <!-- LOGO -->
        <div class="logo-section">
            <a href="index.php" class="logo-link" aria-label="Go to homepage">
                <img src="images/logo.png" loading="eager" alt="EveryWear Logo" width="120" height="90" class="site-logo">
            </a>
        </div>

        <!-- NAV BUTTONS -->
        <div class="nav-buttons">
            <a href="about.php" class="nav-button">About Us</a>
            <a href="productline.php" class="nav-button">Products</a>
            <a href="reviews.php" class="nav-button">Reviews</a>
            <a href="contact.php" class="nav-button">Contact Us</a>
        </div>

        <!-- RIGHT SIDE -->
        <div class="right-controls" id="rightControls">
            <div class="right-default" id="rightDefault">
                <span class="welcome-msg">Hi <?php echo htmlspecialchars($userName); ?>!</span>
                <?php if (!empty($_SESSION['is_admin'])): ?>
                    <a href="admin-orders.php" class="login-btn" style="background:#111;color:white;">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="login-btn">Logout</a>

                <a href="#" id="cartToggle" class="icon-link" aria-label="Open basket">
                    <img src="images/basket.png" alt="Basket" class="nav-icon">
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                <!-- DARK MODE TOGGLE -->
                <button type="button" id="themeToggle" class="theme-toggle-btn" aria-label="Toggle dark mode">
                    <span id="themeIcon">&#127769;</span>
                </button>
            </div>
        </div>
    </div> <!-- End of NAVBAR -->

    <!-- CART OVERLAY -->
    <div class="cart-overlay" id="cartOverlay"></div>

    <!-- CART SIDEBAR -->
    <div class="cart-sidebar" id="cartSidebar">
        <h2>Your Cart</h2>
        <i id="cart-close" class="ri-close-circle-fill"></i>

        <div class="cart-items">
            <?php if (empty($_SESSION['cart'])): ?>
                <p class="cart-empty-msg">Your cart is empty.</p>
            <?php else: ?>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <?php
                        $name  = $item['name']  ?? 'Item';
                        $size  = $item['size']  ?? '';
                        $img   = $item['image'] ?? 'images/placeholder.jpg';
                        $price = isset($item['price']) ? (float)$item['price'] : 0;
                        $qty   = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                    ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($img); ?>"
                             alt="<?php echo htmlspecialchars($name); ?>">
                        <div class="cart-details">
                            <strong><?php echo htmlspecialchars($name); ?></strong>
                            <?php if ($size !== ''): ?>
                                Size: <?php echo htmlspecialchars($size); ?><br>
                            <?php endif; ?>
                            Qty: <?php echo $qty; ?><br>
                            £<?php echo number_format($price, 2); ?> each
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="cart-summary-total">
                    <span>Total</span>
                    <span><?php echo formatPrice($cartTotal); ?></span>
                </div>

                <a href="cart.php" class="btn-view-cart">View Cart</a>
                <a href="checkout.php" class="checkout-btn">Go to Checkout</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN ORDERS CONTENT -->
    <main class="orders-page">
        <h1>Your Orders</h1>
        <p class="orders-subtitle">
            Here's a list of orders placed with your EveryWear account.
        </p>

        <?php if (empty($orders)): ?>
            <p class="empty-orders">
                You don't have any orders yet. Once you place an order at checkout,
                it will appear here.
            </p>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <article class="order-card">
                        <div class="order-main">
                            <h2>Order #<?php echo (int) $order['order_id']; ?></h2>
                            <div class="order-meta">
                                Placed on: <?php echo htmlspecialchars($order['order_date']); ?>
                            </div>
                        </div>
                        <div class="order-right">
                            <span class="order-total"><?php echo formatPrice((float)$order['total_price']); ?></span>
                            <span class="order-status">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- FOOTER (matching the rest of the site) -->
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h4>Shop</h4>
                <ul>
                    <li><a href="productline.php?category=Tops">Tops</a></li>
                    <li><a href="productline.php?category=Bottoms">Bottoms</a></li>
                    <li><a href="productline.php?category=Outerwear">Outerwear</a></li>
                    <li><a href="productline.php?category=Footwear">Footwear</a></li>
                    <li><a href="productline.php?category=Accessories">Accessories</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Customer Service</h4>
                <ul>
                    <li><a href="contact.php">Delivery &amp; Returns</a></li>
                    <li><a href="login.php">10% Student Discount</a></li>
                    <li><a href="contact.php">FAQs</a></li>
                    <li><a href="login.php">My Account</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Join Now</h4>
                <ul>
                    <li><a href="create-account.php">Become a member today and get exclusive benefits!</a></li>
                </ul>
            </div>

            <div class="footer-col footer-col-right">
                <h4>EveryWear</h4>
                <p>Designed for all.</p>
                <p>Follow Us On:</p>
                <div class="footer-socials">
                    <i class="ri-instagram-line"></i>
                    <i class="ri-tiktok-line"></i>
                    <i class="ri-youtube-line"></i>
                </div>

                <div class="footer-app">
                    <h5>Download Our App</h5>
                    <div class="store-badges">
                        <a href="#" aria-label="Get it on Google Play">
                            <img src="images/image1.png" alt="Get it on Google Play">
                        </a>
                        <a href="#" aria-label="Download on the App Store">
                            <img src="images/image2.png" alt="Download on the App Store">
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; 2025 EveryWear. All rights reserved.
        </div>
    </footer>

    <!-- Back to top -->
    <button id="backToTop" aria-label="Back to top">?</button>

    <script>
    // ?? Cart sidebar open/close ??
    const cartToggle  = document.getElementById('cartToggle');
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartClose   = document.getElementById('cart-close');

    function openCart() {
        cartSidebar.classList.add('active');
        cartOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeCart() {
        cartSidebar.classList.remove('active');
        cartOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (cartToggle)  cartToggle.addEventListener('click', function(e) { e.preventDefault(); openCart(); });
    if (cartClose)   cartClose.addEventListener('click', closeCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (cartSidebar && cartSidebar.classList.contains('active')) closeCart();
        }
    });

    // ?? DARK MODE (matching productline.php) ??
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon   = document.getElementById("themeIcon");

    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
        themeIcon.innerHTML = "&#9728;&#65039;";
    }

    themeToggle.addEventListener("click", function(e) {
        e.stopPropagation();
        document.body.classList.toggle("dark");
        if (document.body.classList.contains("dark")) {
            localStorage.setItem("theme", "dark");
            themeIcon.innerHTML = "&#9728;&#65039;";
        } else {
            localStorage.setItem("theme", "light");
            themeIcon.innerHTML = "&#127769;";
        }
    });

    // ?? Back to top ??
    const backToTopBtn = document.getElementById("backToTop");

    window.addEventListener("scroll", function() {
        if (window.scrollY > 350) {
            backToTopBtn.classList.add("is-visible");
        } else {
            backToTopBtn.classList.remove("is-visible");
        }
    });

    backToTopBtn.addEventListener("click", function() {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
    </script>

    <?php include 'chatbot-widget.php'; ?>
</body>
</html>

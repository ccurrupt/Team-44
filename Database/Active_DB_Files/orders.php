<?php

require_once 'dbconfig.php';   // gives us $pdo and starts the session

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

// ---- Fetch this user's orders from the database ----
try {
    $stmt = $pdo->prepare("
        SELECT order_id, created_at
        FROM Orders
        WHERE user_id = :uid
        ORDER BY created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Orders error: ' . $e->getMessage());
    $orders = [];
}

// Small helper for formatting money
function formatPrice(float $v): string {
    return '£' . number_format($v, 2);
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
    <title>Your Orders - EveryWear</title>

    <!-- Global stylesheet + logo -->
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet" />

    <style>
        /* Keep this page-specific CSS lightweight and readable */

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

        /* Simple welcome text in header */
        .welcome-msg {
            font-size: 14px;
            margin-right: 8px;
        }

        /* --- Cart sidebar styling  --- */

        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 350px;
            height: 100%;
            background: #ffffff;
            padding: 25px;
            box-shadow: -2px 0 10px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            z-index: 1001;
            overflow-y: auto;
        }

        .cart-sidebar.active {
            right: 0;
        }

        .cart-item {
            display: flex;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .cart-item img {
            width: 60px;
            height: 60px;
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
        }

        .cart-summary-total {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 2px solid #eee;
            font-size: 15px;
            display: flex;
            justify-content: space-between;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            padding: 12px 16px;
            background: black;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 16px;
            font-weight: 600;
        }

        .close-cart {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 22px;
            cursor: pointer;
        }

        .cart-empty-msg {
            color: #666;
            font-size: 14px;
            margin-top: 16px;
        }

        @media (max-width: 600px) {
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR – based on the product-detail page so everything feels consistent -->
<div class="navbar">
    <div class="logo-section">
        <a href="index.php" class="logo-link">
            <img src="logo.png" alt="EveryWear" class="site-logo">
        </a>
        
    </div>

    <div class="nav-buttons">
        <a href="about.php"    class="nav-button">About</a>
        <a href="products.php" class="nav-button">Products</a>
        <a href="reviews.php"  class="nav-button">Reviews</a>
        <a href="orders.php"   class="nav-button active">Orders</a>
    </div>

    <div class="right-controls">
        <!-- We know user is logged in on this page, so we say Hi -->
        <span class="welcome-msg">Hi <?php echo htmlspecialchars($userName); ?>!</span>
        <a href="logout.php" class="login-btn">Logout</a>

        <!-- Cart toggle with number of items -->
        <a href="#" id="cartToggle" class="icon-link">
            <i class="ri-shopping-cart-line" style="font-size: 22px;"></i>
            <span class="cart-count-badge"><?php echo count($_SESSION['cart']); ?></span>
        </a>
    </div>
</div>

<!-- CART SIDEBAR -->
<div class="cart-sidebar" id="cartSidebar">
    <h2>Your Cart</h2>

    <div class="cart-items">
        <?php if (empty($_SESSION['cart'])): ?>
            <p class="cart-empty-msg">Your cart is empty right now.</p>
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
                        <strong><?php echo htmlspecialchars($name); ?></strong><br>
                        <?php if ($size !== ''): ?>
                            Size: <?php echo htmlspecialchars($size); ?><br>
                        <?php endif; ?>
                        Qty: <?php echo $qty; ?><br>
                        <?php echo formatPrice($price); ?> each
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cart-summary-total">
                <span>Total</span>
                <span><?php echo formatPrice($cartTotal); ?></span>
            </div>

            <a href="checkout.php" class="checkout-btn">Go to Checkout</a>
        <?php endif; ?>
    </div>

    <!-- Close icon -->
    <i class="ri-close-line close-cart" id="closeCart"></i>
</div>

<!-- MAIN ORDERS CONTENT -->
<main class="orders-page">
    <h1>Your Orders</h1>
    <p class="orders-subtitle">
        Here’s a list of orders placed with your EveryWear account.
    </p>

    <?php if (empty($orders)): ?>
        <p class="empty-orders">
            You don’t have any orders yet. Once you place an order at checkout,
            it will appear here.
        </p>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <article class="order-card">
                    <div class="order-main">
                        <h2>Order #<?php echo (int) $order['order_id']; ?></h2>
                        <div class="order-meta">
                            Placed on:
                            <?php echo htmlspecialchars($order['created_at']); ?>
                        </div>
                    </div>
                    <!-- You can expand this later with real status, total, etc. -->
                    <div class="order-status">
                        In progress
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
//JS to open/close the cart sidebar

const cartToggle = document.getElementById('cartToggle');
const cartSidebar = document.getElementById('cartSidebar');
const closeCart = document.getElementById('closeCart');

if (cartToggle && cartSidebar && closeCart) {
    cartToggle.addEventListener('click', function (e) {
        e.preventDefault();
        cartSidebar.classList.add('active');
    });

    closeCart.addEventListener('click', function () {
        cartSidebar.classList.remove('active');
    });

    // Close if user clicks outside the sidebar
    document.addEventListener('click', function (e) {
        if (!cartSidebar.classList.contains('active')) return;

        const clickedInsideSidebar = cartSidebar.contains(e.target);
        const clickedToggle = cartToggle.contains(e.target);

        if (!clickedInsideSidebar && !clickedToggle) {
            cartSidebar.classList.remove('active');
        }
    });
}
</script>

</body>
</html>

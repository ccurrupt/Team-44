<?php

require_once 'dbconfig.php';   


$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';


if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EveryWear - Reviews</title>

    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">

    <!-- Icons (for cart icon) -->
    <link
        href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css"
        rel="stylesheet"
    />

    <style>
        .reviews-hero {
            text-align: center;
            max-width: 800px;
        }
        .reviews-hero h1 {
            font-size: 36px;
            color: #999;
            margin-bottom: 10px;
        }
        .reviews-hero p {
            color: #666;
            font-size: 15px;
        }
        .welcome-msg {
            font-size: 14px;
            margin-right: 8px;
        }
    </style>
</head>

<body>

<!-- TOP NAVIGATION (matches the rest of the site) -->
<div class="navbar">

    <!-- LOGO -->
    <div class="logo-section">
        <a href="index.php" class="logo-link" aria-label="Go to homepage">
            <img src="logo.png" loading="eager" alt="EveryWear Logo"
                 width="120" height="90" class="site-logo">
        </a>
    </div>

    <!-- NAV BUTTONS -->
    <div class="nav-buttons">
        <a href="about.php"    class="nav-button">About Us</a>
        <a href="products.php" class="nav-button">Products</a>
        <a href="reviews.php"  class="nav-button active">Reviews</a>
        <a href="orders.php"   class="nav-button">Orders</a>
    </div>

    <!-- RIGHTSIDE: login / account / cart -->
    <div class="right-controls">

        <?php if ($isLoggedIn): ?>
            <span class="welcome-msg">Hi <?php echo htmlspecialchars($userName); ?>!</span>
            <a href="logout.php" class="login-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Log in</a>
            <a href="create-account.php" class="create-btn">Create Account</a>
        <?php endif; ?>

        <!-- Cart icon with item count -->
        <a href="cart.php" class="icon-link">
            <i class="ri-shopping-cart-line" style="font-size: 22px;"></i>
            <span class="cart-count-badge"><?php echo count($_SESSION['cart']); ?></span>
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="hero">
    <div class="reviews-hero">
        <h1>Reviews Page</h1>
        <p>
            Customer reviews will live here – for now this page is a placeholder
            while we hook the review system into the database.
        </p>
        <?php
        
        ?>
    </div>
</div>

</body>
</html>

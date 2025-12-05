<?php

session_start();
require_once 'dbconfig.php';

$cartItems = [];
$total = 0;
$cartCount = 0;
$isLoggedIn = false;
$userName = 'Guest';

if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $isLoggedIn = true;
    
    if (isset($_SESSION['first_name'])) {
        $userName = $_SESSION['first_name'];
        if (isset($_SESSION['last_name'])) {
            $userName .= ' ' . $_SESSION['last_name'];
        }
    }
    
    try {
        $cartStmt = $pdo->prepare("
            SELECT cart_id
            FROM Cart
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $cartStmt->execute([':uid' => $userId]);
        $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);
        
        $cartId = null;
        
        if ($cart) {
            $cartId = (int)$cart['cart_id'];
            
            $itemsStmt = $pdo->prepare("
                SELECT 
                    ci.cart_item_id,
                    ci.quantity,
                    ci.total_price,
                    ci.size,
                    p.product_id,
                    p.name,
                    p.price,
                    p.image_main
                FROM Cart_Items ci
                JOIN Products p ON ci.product_id = p.product_id
                WHERE ci.cart_id = :cid
            ");
            $itemsStmt->execute([':cid' => $cartId]);
            $cartItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($cartItems as $item) {
                $total += $item['total_price'];
                $cartCount += $item['quantity'];
            }
        }
        
    } catch (PDOException $e) {
        error_log("Cart error: " . $e->getMessage());
    }
    
} else {
    if (isset($_SESSION['cart'])) {
        $cartItems = $_SESSION['cart'];
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
            $cartCount += $item['quantity'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - EveryWear</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f8fa;
            margin: 0;
            padding: 0;
        }
        
        .top-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            height: 50px;
            cursor: pointer;
        }
        
        .right-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .welcome-msg {
            color: #333;
        }
        
        .create-btn {
            padding: 8px 20px;
            border-radius: 20px;
            background: #0066cc;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .create-btn:hover {
            background: #0052a3;
        }
        
        /* CART ICON - Using basket.png image */
        #cart-icon {
            position: relative;
            display: flex;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        #cart-icon:hover {
            transform: scale(1.05);
        }
        
        .basket-icon {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        #cart-icon .cart-item-count {
            position: absolute;
            top: -5px;
            right: -8px;
            width: 18px;
            height: 18px;
            background: #e35f26;
            border-radius: 50%;
            font-size: 11px;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
        }
        
        .nav-row {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .nav-row a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }
        
        .nav-row a:hover {
            color: #0066cc;
        }
        
        .cart-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .cart-page h2 {
            font-size: 32px;
            margin-bottom: 30px;
            color: #333;
        }
        
        .cart-content {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cart-box {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-box:last-child {
            border-bottom: none;
        }
        
        .cart-box img {
            width: 100px;
            height: 100px;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 20px;
        }
        
        .cart-detail {
            flex: 1;
        }
        
        .cart-product-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .cart-price {
            font-weight: 500;
            color: #666;
            margin-bottom: 8px;
        }
        
        .cart-quantity {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            background: #f0f0f0;
            font-size: 14px;
            color: #333;
        }
        
        .total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 20px;
        }
        
        .total-title {
            font-weight: 600;
        }
        
        .total-price {
            font-weight: 700;
            color: #000;
        }
        
        .btn-buy {
            display: block;
            width: 100%;
            padding: 15px;
            background: black;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-buy:hover {
            background: #333;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
        }
        
        .continue-shopping {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<header class="top-header">
    <div class="top-row">
        <img src="../logo.png" alt="Logo" class="logo" onclick="window.location.href='../index.php'">
        
        <div class="nav-row">
            <a href="../index.php">Home</a>
            <a href="../products.php">Products</a>
            <a href="../about.php">About Us</a>
            <a href="../reviews.php">Reviews</a>
            <a href="../orders.php">Orders</a>
        </div>
        
        <div class="right-section">
            <?php if($isLoggedIn): ?>
                <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
                <button class="create-btn" onclick="window.location.href='logout.php'">
                    Log Out
                </button>
            <?php else: ?>
                <button class="create-btn" onclick="window.location.href='login.php'">
                    Log In
                </button>
                <button class="create-btn" onclick="window.location.href='create-account.php'" style="background: black;">
                    Sign Up
                </button>
            <?php endif; ?>
            
            <!-- Cart icon -->
            <div id="cart-icon" onclick="window.location.href='cart.php'">
                <img src="../basket.png" alt="Cart" class="basket-icon">
                <?php if($cartCount > 0): ?>
                    <span class="cart-item-count"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<section class="cart-page">
    <h2>Your Shopping Cart</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <p>Your cart is currently empty.</p>
            <a href="../products.php" class="continue-shopping">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-content">
            <?php foreach ($cartItems as $item): ?>
                <div class="cart-box">
                    <img src="<?php echo htmlspecialchars($item['image_main'] ?? $item['image']); ?>"
                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                    
                    <div class="cart-detail">
                        <h2 class="cart-product-title">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </h2>
                        
                        <span class="cart-price">
                            Unit Price: £<?php echo number_format($item['price'], 2); ?>
                        </span>
                        
                        <div class="cart-quantity">
                            Quantity: <?php echo (int)$item['quantity']; ?>
                            <?php if(!empty($item['size'])): ?>
                                | Size: <?php echo htmlspecialchars($item['size']); ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(isset($item['total_price'])): ?>
                        <div style="margin-top: 10px; font-weight: 600;">
                            Item Total: £<?php echo number_format($item['total_price'], 2); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total">
            <div class="total-title">Order Total</div>
            <div class="total-price">
                £<?php echo number_format($total, 2); ?>
            </div>
        </div>

        <button class="btn-buy" onclick="window.location.href='checkout.php'">Proceed to Checkout</button>
    <?php endif; ?>
</section>

</body>
</html>
<?php
session_start();

// Check login status (for display only)
$isLoggedIn = isset($_SESSION['user_id']);
$userName = 'Guest';
if ($isLoggedIn && isset($_SESSION['first_name'])) {
    $userName = $_SESSION['first_name'];
}

// ALWAYS use session cart (simple for demo)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartItems = $_SESSION['cart'];
$total = 0;
$cartCount = 0;

// Calculates total and count
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
    $cartCount += $item['quantity'];
}

// Will handle cart actions on this page
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'update':
            if (isset($_GET['index'], $_GET['change'])) {
                $index = intval($_GET['index']);
                $change = intval($_GET['change']);
                
                if (isset($_SESSION['cart'][$index])) {
                    $_SESSION['cart'][$index]['quantity'] += $change;
                    
                    if ($_SESSION['cart'][$index]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$index]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                    }
                }
                header('Location: cart.php');
                exit();
            }
            break;
            
        case 'remove':
            if (isset($_GET['index'])) {
                $index = intval($_GET['index']);
                if (isset($_SESSION['cart'][$index])) {
                    unset($_SESSION['cart'][$index]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
                header('Location: cart.php');
                exit();
            }
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            header('Location: cart.php');
            exit();
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - EveryWear</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
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
        
        #cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 22px;
        }
        
        .cart-item-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
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
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: white;
        }
        
        .qty-btn:hover {
            background: #f5f5f5;
        }
        
        .remove-btn {
            color: #ff4757;
            cursor: pointer;
            margin-left: 20px;
        }
        
        .item-total {
            font-weight: bold;
            font-size: 18px;
            margin-left: 20px;
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
        
        .clear-cart {
            display: inline-block;
            margin-top: 15px;
            color: #ff4757;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>
<body>

<header class="top-header">
    <div class="top-row">
        <img src="logo.png" alt="Logo" class="logo" onclick="window.location.href='index.php'">
        
        <div class="nav-row">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="about.php">About Us</a>
            <a href="reviews.php">Reviews</a>
            <a href="orders.php">Orders</a>
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
            
            <div id="cart-icon" onclick="window.location.href='cart.php'">
                <i class="ri-shopping-cart-2-line"></i>
                <span class="cart-item-count">
                    <?php echo $cartCount; ?>
                </span>
            </div>
        </div>
    </div>
</header>

<section class="cart-page">
    <h2>Your Shopping Cart</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <p>Your cart is currently empty.</p>
            <a href="products.php" class="continue-shopping">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-content">
            <?php foreach ($cartItems as $index => $item): 
                $item_total = $item['price'] * $item['quantity'];
            ?>
                <div class="cart-box">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
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
                        
                        <div class="qty-controls">
                            <span class="qty-btn" onclick="updateQty(<?php echo $index; ?>, -1)">-</span>
                            <span><?php echo $item['quantity']; ?></span>
                            <span class="qty-btn" onclick="updateQty(<?php echo $index; ?>, 1)">+</span>
                        </div>
                    </div>
                    
                    <div class="item-total">
                        £<?php echo number_format($item_total, 2); ?>
                    </div>
                    
                    <div class="remove-btn" onclick="removeItem(<?php echo $index; ?>)">
                        <i class="ri-delete-bin-line"></i>
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
        
        <a href="cart.php?action=clear" class="clear-cart" onclick="return confirm('Clear entire cart?')">
            Clear Cart
        </a>
    <?php endif; ?>
</section>

<script>
function updateQty(index, change) {
    window.location.href = 'cart.php?action=update&index=' + index + '&change=' + change;
}

function removeItem(index) {
    if (confirm('Remove this item from cart?')) {
        window.location.href = 'cart.php?action=remove&index=' + index;
    }
}
</script>

</body>
</html>

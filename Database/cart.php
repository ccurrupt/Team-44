<?php
require_once 'dbconfig.php'; // includes session_start() and $pdo

// ── Auth ──
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = '';
if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] ?? 'User';
    if (!empty($_SESSION['last_name'])) {
        $userName .= ' ' . $_SESSION['last_name'];
    }
}

// ── Ensure cart exists ──
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── Handle cart actions via GET (update qty / remove / clear) ──
if (isset($_GET['action'])) {
    switch ($_GET['action']) {

        case 'update':
            if (isset($_GET['index'], $_GET['change'])) {
                $index  = intval($_GET['index']);
                $change = intval($_GET['change']);
                if (isset($_SESSION['cart'][$index])) {
                    $_SESSION['cart'][$index]['quantity'] += $change;
                    if ($_SESSION['cart'][$index]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$index]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                    }
                }
            }
            header('Location: cart.php');
            exit();

        case 'remove':
            if (isset($_GET['index'])) {
                $index = intval($_GET['index']);
                if (isset($_SESSION['cart'][$index])) {
                    unset($_SESSION['cart'][$index]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
            }
            header('Location: cart.php');
            exit();

        case 'clear':
            $_SESSION['cart'] = [];
            header('Location: cart.php');
            exit();
    }
}

// ── Build display-ready cart (fetch names/prices from DB if missing) ──
$cartItems = [];
$total     = 0;
$cartCount = 0;

foreach ($_SESSION['cart'] as $raw) {
    if (!is_array($raw)) continue;

    $productId = $raw['product_id'] ?? $raw['id'] ?? null;
    if (!$productId) continue;

    $name  = $raw['name']  ?? '';
    $price = isset($raw['price']) ? (float)$raw['price'] : null;
    $image = $raw['image'] ?? $raw['image_main'] ?? 'images/placeholder.jpg';
    $size  = $raw['size']  ?? '';
    $qty   = max(1, (int)($raw['quantity'] ?? 1));

    // Fill in missing name/price from the database
    if ($price === null || $name === '') {
        try {
            $stmt = $pdo->prepare("SELECT name, price, image_main FROM Products WHERE product_id = :pid");
            $stmt->execute([':pid' => $productId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($name  === '')   $name  = $row['name'];
                if ($price === null) $price = (float)$row['price'];
                if ($image === 'images/placeholder.jpg' && !empty($row['image_main'])) {
                    $image = $row['image_main'];
                }
            }
        } catch (PDOException $e) {
            error_log("Cart price lookup: " . $e->getMessage());
        }
    }

    if ($price === null) continue;

    $itemTotal = $price * $qty;
    $total    += $itemTotal;
    $cartCount += $qty;

    $cartItems[] = [
        'product_id' => (int)$productId,
        'name'       => $name,
        'price'      => $price,
        'size'       => $size,
        'image'      => $image,
        'quantity'   => $qty,
        'item_total' => $itemTotal,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - EveryWear</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f8fa;
            margin: 0; padding: 0;
        }

        /* ── Cart page ── */
        .cart-page {
            max-width: 1200px; margin: 40px auto; padding: 0 20px;
        }
        .cart-page h2 {
            font-size: 32px; margin-bottom: 30px; color: #333;
        }
        .cart-content {
            background: white; border-radius: 10px;
            padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cart-box {
            display: flex; align-items: center;
            padding: 20px; border-bottom: 1px solid #eee;
        }
        .cart-box:last-child { border-bottom: none; }
        .cart-box img {
            width: 100px; height: 100px; border-radius: 6px;
            object-fit: cover; margin-right: 20px;
        }
        .cart-detail { flex: 1; }
        .cart-product-title {
            font-size: 18px; font-weight: 600;
            margin: 0 0 8px; color: #333;
        }
        .cart-price { font-weight: 500; color: #666; margin-bottom: 8px; }
        .cart-meta {
            display: inline-block; padding: 4px 12px; border-radius: 4px;
            background: #f0f0f0; font-size: 14px; color: #333;
        }
        .cart-item-total { margin-top: 10px; font-weight: 600; }
        .cart-actions { display: flex; align-items: center; gap: 12px; margin-left: 20px; }
        .qty-btn {
            width: 30px; height: 30px; border: 1px solid #ccc; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; background: white; font-size: 16px;
            text-decoration: none; color: #333;
        }
        .qty-btn:hover { background: #f0f0f0; }
        .remove-btn {
            color: #ff4757; cursor: pointer; font-size: 20px;
            text-decoration: none; margin-left: 10px;
        }
        .remove-btn:hover { color: #c0392b; }

        .total {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 30px; padding-top: 20px; border-top: 2px solid #333;
            font-size: 20px;
        }
        .total-title { font-weight: 600; }
        .total-price { font-weight: 700; color: #000; }

        .btn-buy {
            display: block; width: 100%; padding: 15px;
            background: black; color: white; border: none;
            border-radius: 5px; font-size: 16px; font-weight: 600;
            cursor: pointer; margin-top: 20px;
            text-decoration: none; text-align: center;
        }
        .btn-buy:hover { background: #333; }
        .btn-clear {
            display: block; width: 100%; padding: 12px;
            background: white; color: #ff4757; border: 2px solid #ff4757;
            border-radius: 5px; font-size: 14px; font-weight: 600;
            cursor: pointer; margin-top: 10px; text-align: center;
            text-decoration: none;
        }
        .btn-clear:hover { background: #fff5f5; }

        .empty-cart {
            text-align: center; padding: 60px 20px; color: #666; font-size: 18px;
        }
        .continue-shopping {
            display: inline-block; margin-top: 20px; padding: 10px 20px;
            background: #0066cc; color: white; text-decoration: none;
            border-radius: 5px;
        }
        .welcome-msg { font-size: 14px; margin-right: 8px; }
    </style>
</head>
<body>

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

        <!-- RIGHT SIDE: Login / Create Account / Basket / Search -->
        <div class="right-controls" id="rightControls">

            <!-- Default icons (visible normally) -->
            <div class="right-default" id="rightDefault">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="welcome-msg">Hi <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</span>
                    <a href="logout.php" class="login-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Log in</a>
                    <a href="create-account.php" class="create-btn">
                        <img src="images/account.png" alt="" class="btn-icon">
                        Create Account
                    </a>
                <?php endif; ?>

                <a href="cart.php" class="icon-link">
                    <img src="images/basket.png" alt="Basket" class="nav-icon">
                </a>

                <div class="icon-link search-icon" id="searchToggle">
                    <img src="images/search.png" alt="Search" class="nav-icon">
                </div>

                <!-- DARK MODE TOGGLE -->
                <button id="themeToggle" class="icon-link" aria-label="Toggle theme"><i id="themeIcon" class="ri-moon-line"></i></button>
            </div>

            <!-- Search bar overlay (hidden until search opens) -->
            <div id="searchBar" class="search-bar-overlay">
                <input type="text" placeholder="Search...">
            </div>

            <!-- Close icon (only visible when search is open) -->
            <div class="icon-link search-close" id="searchClose">
                <img src="images/search.png" alt="Close Search" class="nav-icon">
            </div>
        </div>
    </div> <!-- End of NAVBAR -->

<section class="cart-page">
    <h2>Your Shopping Cart</h2>

    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <p>Your cart is currently empty.</p>
            <a href="products.php" class="continue-shopping">Continue Shopping</a>
        </div>

    <?php else: ?>
        <div class="cart-content">
            <?php foreach ($cartItems as $index => $item): ?>
                <div class="cart-box">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                         alt="<?php echo htmlspecialchars($item['name']); ?>">

                    <div class="cart-detail">
                        <h2 class="cart-product-title">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </h2>
                        <span class="cart-price">
                            £<?php echo number_format($item['price'], 2); ?> each
                        </span>
                        <div class="cart-meta">
                            Qty: <?php echo (int)$item['quantity']; ?>
                            <?php if (!empty($item['size'])): ?>
                                &nbsp;|&nbsp; Size: <?php echo htmlspecialchars($item['size']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-total">
                            Item Total: £<?php echo number_format($item['item_total'], 2); ?>
                        </div>
                    </div>
                    
                   

                    <div class="cart-actions">
                        <a class="qty-btn" href="cart.php?action=update&index=<?php echo $index; ?>&change=-1" title="Decrease">−</a>
                        <span><?php echo (int)$item['quantity']; ?></span>
                        <a class="qty-btn" href="cart.php?action=update&index=<?php echo $index; ?>&change=1" title="Increase">+</a>
                        <a class="remove-btn" href="cart.php?action=remove&index=<?php echo $index; ?>" title="Remove">
                            <i class="ri-delete-bin-line"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total">
            <div class="total-title">Order Total</div>
            <div class="total-price">£<?php echo number_format($total, 2); ?></div>
        </div>

        <a href="checkout.php" class="btn-buy">Proceed to Checkout</a>
        <a href="cart.php?action=clear" class="btn-clear"
           onclick="return confirm('Clear entire cart?');">Clear Cart</a>

    <?php endif; ?>
</section>


<script>
/* DARK MODE - uses RemixIcon classes */
document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon   = document.getElementById("themeIcon");
    if (themeToggle && themeIcon) {
        if (localStorage.getItem("theme") === "dark") {
            document.body.classList.add("dark");
            themeIcon.className = "ri-sun-line";
        }
        themeToggle.addEventListener("click", () => {
            document.body.classList.toggle("dark");
            if (document.body.classList.contains("dark")) {
                localStorage.setItem("theme", "dark");
                themeIcon.className = "ri-sun-line";
            } else {
                localStorage.setItem("theme", "light");
                themeIcon.className = "ri-moon-line";
            }
        });
    }
});
</script>
</body>
</html>

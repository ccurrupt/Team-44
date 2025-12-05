<?php
// public_html/checkout.php

require_once 'dbconfig.php'; // includes $pdo and session_start()

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=login_required");
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? '';


function loadCartItems(PDO $pdo): array
{
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }

    $items = [];

    foreach ($_SESSION['cart'] as $key => $raw) {
        if (!is_array($raw)) continue;

        $productId = $raw['product_id'] ?? $raw['id'] ?? null;
        if (!$productId) continue;

        $size     = $raw['size'] ?? ($raw['variant'] ?? '');
        $qty      = isset($raw['quantity']) ? (int)$raw['quantity'] : 1;
        if ($qty < 1) $qty = 1;

        $name  = $raw['name']  ?? '';
        $price = isset($raw['price']) ? (float)$raw['price'] : null;

        // If we don't have name/price, fetch from Products table
        if ($price === null || $name === '') {
            try {
                $stmt = $pdo->prepare("SELECT name, price FROM Products WHERE product_id = :pid");
                $stmt->execute([':pid' => $productId]);
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($name  === '')   $name  = $row['name'];
                    if ($price === null) $price = (float) $row['price'];
                }
            } catch (PDOException $e) {
                error_log("Checkout price lookup error: " . $e->getMessage());
            }
        }

        if ($price === null) {
            // If we still don't have a price, skip this item to avoid weird totals
            continue;
        }

        $subtotal = $price * $qty;

        $items[] = [
            'product_id' => (int) $productId,
            'name'       => $name,
            'size'       => $size,
            'quantity'   => $qty,
            'price'      => $price,
            'subtotal'   => $subtotal,
        ];
    }

    return $items;
}

// ---------- Load cart ----------
$cartItems = loadCartItems($pdo);
$cartTotal = 0.0;
foreach ($cartItems as $item) {
    $cartTotal += $item['subtotal'];
}

$orderCreated = false;
$newOrderId   = null;
$errorMessage = '';

// If the user clicks "Place Order"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($cartItems)) {
        $errorMessage = "Your cart is empty. Add something before checking out.";
    } else {
        try {
            $pdo->beginTransaction();

           
            $stmt = $pdo->prepare("INSERT INTO Orders (user_id) VALUES (:uid)");
            $stmt->execute([':uid' => $userId]);

            $newOrderId = (int)$pdo->lastInsertId();

            $pdo->commit();

            // Clear the cart (session-based)
            unset($_SESSION['cart']);

            $orderCreated = true;
            $cartItems    = [];
            $cartTotal    = 0.0;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Checkout / place order error: " . $e->getMessage());
            $errorMessage = "Something went wrong while placing your order. Please try again.";
        }
    }
}

function formatPrice(float $v): string {
    return '£' . number_format($v, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - EveryWear</title>

    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">

    <link
        href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css"
        rel="stylesheet"
    />

    <style>
        .checkout-page {
            padding: 30px 9% 40px;
            display: flex;
            gap: 32px;
            align-items: flex-start;
        }
        .checkout-main, .checkout-summary {
            background: #fff;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        .checkout-main {
            flex: 2;
        }
        .checkout-summary {
            flex: 1;
            max-width: 340px;
        }
        .checkout-main h1 {
            margin-top: 0;
            margin-bottom: 8px;
        }
        .checkout-main p.subtitle {
            margin-top: 0;
            color: #666;
            font-size: 14px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-details {
            max-width: 70%;
        }
        .cart-item-name {
            font-weight: 600;
        }
        .cart-item-meta {
            color: #777;
            margin-top: 4px;
        }
        .cart-item-price {
            text-align: right;
            white-space: nowrap;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
            font-size: 14px;
        }
        .summary-row.total {
            font-weight: 600;
            font-size: 15px;
            margin-top: 10px;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
        .place-order-btn {
            width: 100%;
            margin-top: 16px;
            padding: 12px 18px;
            border-radius: 30px;
            border: none;
            background: black;
            color: white;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.25s ease, transform 0.25s ease;
        }
        .place-order-btn:hover {
            background: linear-gradient(to right, #30CDF5, #00FAA0);
            color: #000;
            transform: translateY(-1px) scale(1.03);
        }
        .alert {
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            margin-bottom: 14px;
        }
        .alert-success {
            background: #e6fbef;
            color: #176944;
            border: 1px solid #b7eed2;
        }
        .alert-error {
            background: #ffecec;
            color: #a30000;
            border: 1px solid #ffb3b3;
        }
        .welcome-msg {
            font-size: 14px;
            margin-right: 8px;
        }
        @media (max-width: 900px) {
            .checkout-page {
                flex-direction: column;
            }
            .checkout-summary {
                max-width: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR (same pattern as orders.php) -->
<div class="navbar">
    <div class="logo-section">
        <a href="index.php" class="logo-link">
            <img src="logo.png" alt="EveryWear logo" class="site-logo">
        </a>
        
    </div>

    <div class="nav-buttons">
        <a href="index.php"     class="nav-button">Home Page</a>
        <a href="products.php"  class="nav-button">Outfits</a>
        <a href="about.php"     class="nav-button">About Us</a>
        <a href="wishlist.php"  class="nav-button">Wishlist</a>
        <a href="orders.php"    class="nav-button">Orders</a>
    </div>

    <div class="right-controls">
        <div class="right-default">
            <span class="welcome-msg">
                Welcome, <?php echo htmlspecialchars($userName); ?>!
            </span>

            <button class="create-btn" onclick="window.location.href='logout.php'">
                Log Out
            </button>

            <a id="cart-icon" class="icon-link" href="cart.php">
                <i class="ri-shopping-cart-2-line"></i>
                <span class="cart-item-count"></span>
            </a>
        </div>
    </div>
</div>

<main class="checkout-page">
    <section class="checkout-main">
        <h1>Checkout</h1>
        <p class="subtitle">
            Review your items and place your order. You’ll be able to see it later on the
            <a href="orders.php">Orders</a> page.
        </p>

        <?php if ($orderCreated && $newOrderId): ?>
            <div class="alert alert-success">
                Thank you, your order has been placed.
                You can view it any time on your <a href="orders.php">Orders</a> page.
            </div>
        <?php elseif ($errorMessage): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <p>Your cart is empty right now. <a href="products.php">Browse outfits</a> to add something.</p>
        <?php else: ?>
            <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-details">
                        <div class="cart-item-name">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <div class="cart-item-meta">
                            <?php if ($item['size'] !== ''): ?>
                                Size: <?php echo htmlspecialchars($item['size']); ?> ·
                            <?php endif; ?>
                            Qty: <?php echo (int)$item['quantity']; ?>
                        </div>
                    </div>
                    <div class="cart-item-price">
                        <div><?php echo formatPrice($item['price']); ?> each</div>
                        <div><strong><?php echo formatPrice($item['subtotal']); ?></strong></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <aside class="checkout-summary">
        <h2>Order Summary</h2>

        <?php if (!empty($cartItems)): ?>
            <div class="summary-row">
                <span>Items</span>
                <span><?php echo count($cartItems); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span><?php echo formatPrice($cartTotal); ?></span>
            </div>

            <form method="post" style="margin-top: 10px;">
                <button type="submit" class="place-order-btn">
                    Place Order
                </button>
            </form>
        <?php else: ?>
            <p style="font-size: 14px; color: #666;">
                Add items to your cart to place an order.
            </p>
        <?php endif; ?>
    </aside>
</main>

</body>
</html>

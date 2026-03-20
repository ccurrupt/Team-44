<?php
// checkout-payment.php — Step 3: Payment

require_once 'dbconfig.php';

if (!isset($_SESSION['user_id']))          { header("Location: login.php"); exit(); }
if (empty($_SESSION['checkout']))          { header("Location: checkout.php"); exit(); }
if (!isset($_SESSION['checkout']['shipping_method'])) { header("Location: checkout-shipping.php"); exit(); }
if (empty($_SESSION['cart']))              { header("Location: cart.php"); exit(); }

$userId   = (int) $_SESSION['user_id'];
$checkout = $_SESSION['checkout'];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function loadCartItems3(PDO $pdo): array {
    if (empty($_SESSION['cart'])) return [];
    $items = [];
    foreach ($_SESSION['cart'] as $raw) {
        if (!is_array($raw)) continue;
        $pid = $raw['product_id'] ?? $raw['id'] ?? null;
        if (!$pid) continue;
        $name  = $raw['name'] ?? '';
        $price = isset($raw['price']) ? (float)$raw['price'] : null;
        $image = $raw['image'] ?? 'images/placeholder.jpg';
        $size  = $raw['size'] ?? '';
        $qty   = max(1, (int)($raw['quantity'] ?? 1));
        if ($price === null || $name === '') {
            try {
                $stmt = $pdo->prepare("SELECT name, price FROM Products WHERE product_id = ?");
                $stmt->execute([$pid]);
                if ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!$name)          $name  = $r['name'];
                    if ($price === null) $price = (float)$r['price'];
                }
            } catch (PDOException $e) {}
        }
        if ($price === null) continue;
        $items[] = ['product_id'=>(int)$pid,'name'=>$name,'size'=>$size,'quantity'=>$qty,'price'=>$price,'subtotal'=>$price*$qty,'image'=>$image];
    }
    return $items;
}

$cartItems    = loadCartItems3($pdo);
$cartSubtotal = array_sum(array_column($cartItems, 'subtotal'));
$shippingCost = (float)($checkout['shipping_cost'] ?? 3.99);
$shippingName = ucfirst($checkout['shipping_method'] ?? 'standard');
$orderTotal   = $cartSubtotal + $shippingCost;

$orderCreated = false;
$newOrderId   = null;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errorMessage = "Invalid request. Please refresh and try again.";
    } elseif (empty($cartItems)) {
        $errorMessage = "Your cart is empty.";
    } else {
        try {
            $pdo->beginTransaction();

			// Build the full shipping address string from the session checkout data
		$shippingAddress = implode(', ', array_filter([
    		$_SESSION['checkout']['address']  ?? '',
    		$_SESSION['checkout']['address2'] ?? '',
    		$_SESSION['checkout']['city']     ?? '',
    		$_SESSION['checkout']['postcode'] ?? '',
    		$_SESSION['checkout']['country']  ?? '',
		]));

			$stmt = $pdo->prepare("INSERT INTO Orders (user_id, total_price, status, shipping_address) VALUES (?, ?, 'placed', ?)");
			$stmt->execute([$userId, $orderTotal, $shippingAddress]);
			$newOrderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO Orders_Items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $itemStmt->execute([$newOrderId, $item['product_id'], $item['quantity'], $item['price']]);
            }

            $pdo->commit();

            unset($_SESSION['cart']);
            unset($_SESSION['checkout']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $orderCreated = true;
            $cartItems = [];
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Payment error: " . $e->getMessage());
            $errorMessage = "Something went wrong placing your order. Please try again.";
        }
    }
}

function fp3(float $v): string { return '£' . number_format($v, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout – Payment – EveryWear</title>
<link rel="icon" type="image/png" href="logo.png">
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: "Inter", Arial, sans-serif; background: #f7f8fa; color: #333; min-height: 100vh; }

/* Progress */
.checkout-progress { max-width: 700px; margin: 30px auto 0; display: flex; align-items: center; justify-content: center; padding: 0 20px; }
.progress-step { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #bbb; white-space: nowrap; }
.progress-step.active { color: #111; } .progress-step.done { color: #22c55e; }
.progress-line { flex: 1; height: 2px; background: #ddd; margin: 0 16px; min-width: 40px; }
.progress-line.done { background: #22c55e; }
.progress-dot { width: 10px; height: 10px; border-radius: 50%; border: 2px solid #bbb; background: white; flex-shrink: 0; }
.progress-step.active .progress-dot { border-color: #111; background: #111; }
.progress-step.done .progress-dot { border-color: #22c55e; background: #22c55e; }

/* Layout */
.checkout-layout { max-width: 1100px; margin: 30px auto; padding: 0 20px; display: flex; gap: 40px; align-items: flex-start; }
.checkout-main { flex: 1; min-width: 0; }
.checkout-sidebar { width: 340px; flex-shrink: 0; position: sticky; top: 100px; }

.box { background: #fff; padding: 28px; border-radius: 10px; margin-bottom: 24px; border: 1px solid #e5e7eb; }
.box h2 { font-size: 20px; margin-bottom: 20px; color: #111; }

.back-link { display: inline-flex; align-items: center; gap: 6px; color: #111; text-decoration: none; font-size: 22px; margin-bottom: 10px; transition: opacity 0.2s; }
.back-link:hover { opacity: 0.6; }

/* Form */
.form-row { display: flex; gap: 16px; }
.form-group { flex: 1; margin-bottom: 18px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
.form-group input { width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; font-family: inherit; }
.form-group input:focus { outline: none; border-color: #111; }

/* Buttons */
.btn-row { display: flex; gap: 16px; margin-top: 10px; }
.btn-pay { flex: 1; padding: 16px; background: #111; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
.btn-pay:hover { background: #333; }
.btn-return { flex: 1; padding: 16px; background: white; color: #111; border: 2px solid #d1d5db; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; }
.btn-return:hover { border-color: #111; }

/* Shipping summary line */
.shipping-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 24px;
}

.shipping-summary-label { color: #555; }
.shipping-summary-value { font-weight: 600; color: #111; }
.shipping-summary a { color: #0066cc; font-size: 13px; margin-left: 10px; }

/* Alerts */
.alert { border-radius: 8px; padding: 14px 18px; font-size: 14px; margin-bottom: 20px; }
.alert-success { background: #e6fbef; color: #176944; border: 1px solid #b7eed2; }
.alert-error { background: #ffecec; color: #a30000; border: 1px solid #ffb3b3; }
.alert a { color: inherit; font-weight: 600; }

/* Sidebar */
.summary-card { background: #fff; border-radius: 10px; border: 1px solid #e5e7eb; padding: 24px; }
.summary-card h3 { font-size: 18px; margin-bottom: 20px; color: #111; }
.summary-product { display: flex; gap: 14px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid #f0f0f0; }
.summary-product:last-of-type { border-bottom: none; margin-bottom: 14px; }
.summary-product img { width: 80px; height: 100px; object-fit: cover; border-radius: 8px; }
.summary-product-info { flex: 1; }
.summary-product-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #111; }
.summary-product-meta { font-size: 13px; color: #888; line-height: 1.5; }
.summary-product-price { font-weight: 700; font-size: 14px; color: #111; white-space: nowrap; }
.summary-line { display: flex; justify-content: space-between; font-size: 14px; color: #555; margin-bottom: 8px; }
.summary-total { display: flex; justify-content: space-between; padding-top: 16px; border-top: 2px solid #f0f0f0; margin-top: 8px; }
.summary-total-label { font-size: 16px; font-weight: 700; color: #111; }
.summary-total-price { font-size: 18px; font-weight: 700; color: #111; }

/* Order confirmation */
.order-confirmed { text-align: center; padding: 60px 20px; }
.order-confirmed i { font-size: 64px; color: #22c55e; }
.order-confirmed h1 { font-size: 28px; margin: 20px 0 10px; }
.order-confirmed p { color: #666; font-size: 16px; margin-bottom: 30px; }
.order-confirmed a { display: inline-block; padding: 14px 40px; background: #111; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; }
.order-confirmed a:hover { background: #333; }

@media (max-width: 860px) { .checkout-layout { flex-direction: column; } .checkout-sidebar { width: 100%; position: static; } }

/* Footer */
footer { background: #111; color: white; padding: 50px 20px 30px; margin-top: 80px; }
.footer-grid { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; }
.footer-col h4 { margin-bottom: 20px; font-size: 16px; font-weight: 600; }
.footer-col ul { list-style: none; padding: 0; margin: 0; }
.footer-col li { margin-bottom: 10px; }
.footer-col a { color: #aaa; text-decoration: none; font-size: 14px; transition: color 0.2s; }
.footer-col a:hover { color: white; }
.social-icons { display: flex; gap: 15px; margin-top: 5px; }
.social-icons i { font-size: 20px; color: #aaa; cursor: pointer; }
.social-icons i:hover { color: white; transform: translateY(-2px); }
.copyright { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #333; color: #888; font-size: 14px; }
@media (max-width: 900px) { .footer-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .footer-grid { grid-template-columns: 1fr; } .footer-col { text-align: center; } .social-icons { justify-content: center; } }
</style>
</head>
<body>

<?php if ($orderCreated): ?>

<!-- ── ORDER CONFIRMED ── -->
<div class="order-confirmed">
    <i class="ri-checkbox-circle-fill"></i>
    <h1>Thank you for your order!</h1>
    <p>Order <strong>#<?php echo $newOrderId; ?></strong> has been placed successfully.</p>
    <a href="orders.php">View your orders</a>
</div>

<?php else: ?>

<div style="max-width: 1100px; margin: 20px auto 0; padding: 0 20px;">
    <a href="checkout-shipping.php" class="back-link"><i class="ri-arrow-left-line"></i></a>
</div>

<!-- PROGRESS �� Step 3 active -->
<div class="checkout-progress">
    <div class="progress-step done"><span class="progress-dot"></span> Details</div>
    <div class="progress-line done"></div>
    <div class="progress-step done"><span class="progress-dot"></span> Delivery</div>
    <div class="progress-line done"></div>
    <div class="progress-step active"><span class="progress-dot"></span> Payment</div>
</div>

<div class="checkout-layout">
    <div class="checkout-main">

        <?php if ($errorMessage): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <!-- Shipping choice summary -->
        <div class="shipping-summary">
            <span class="shipping-summary-label">
                Delivery: <strong><?php echo htmlspecialchars($shippingName); ?></strong>
                — <?php echo $shippingCost > 0 ? fp3($shippingCost) : 'FREE'; ?>
            </span>
            <a href="checkout-shipping.php">Change</a>
        </div>

        <!-- Payment form -->
        <div class="box">
            <h2>Payment Details</h2>

            <form method="POST" id="payment-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="card-number">Card number</label>
                    <input id="card-number" type="text" placeholder="1234 5678 9012 3456" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="card-expiry">Expiry</label>
                        <input id="card-expiry" type="text" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label for="card-cvv">CVV</label>
                        <input id="card-cvv" type="text" placeholder="123" required>
                    </div>
                </div>

                <div class="btn-row">
                    <a href="checkout-shipping.php" class="btn-return">Return</a>
                    <button type="submit" class="btn-pay">Pay Now — <?php echo fp3($orderTotal); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- SIDEBAR (with shipping line) -->
    <div class="checkout-sidebar">
        <div class="summary-card">
            <h3>Purchase summary</h3>
            <?php foreach ($cartItems as $item): ?>
            <div class="summary-product">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                <div class="summary-product-info">
                    <div class="summary-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="summary-product-meta">
                        <?php echo fp3($item['price']); ?><br>
                        <?php echo (int)$item['quantity']; ?> item
                        <?php if ($item['size']): ?> · <?php echo htmlspecialchars($item['size']); ?><?php endif; ?>
                    </div>
                </div>
                <div class="summary-product-price"><?php echo fp3($item['subtotal']); ?></div>
            </div>
            <?php endforeach; ?>

            <div class="summary-line">
                <span>Subtotal</span>
                <span><?php echo fp3($cartSubtotal); ?></span>
            </div>
            <div class="summary-line">
                <span>Shipping</span>
                <span><?php echo $shippingCost > 0 ? fp3($shippingCost) : 'FREE'; ?></span>
            </div>

            <div class="summary-total">
                <span class="summary-total-label">Total</span>
                <span class="summary-total-price"><?php echo fp3($orderTotal); ?></span>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

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

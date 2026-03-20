<?php
// checkout-shipping.php — Step 2: Delivery address + method + map

require_once 'dbconfig.php';

if (!isset($_SESSION['user_id']))  { header("Location: login.php"); exit(); }
if (empty($_SESSION['checkout'])) { header("Location: checkout.php"); exit(); }
if (empty($_SESSION['cart']))     { header("Location: cart.php"); exit(); }

$userId   = (int) $_SESSION['user_id'];
$checkout = $_SESSION['checkout'];

function loadCartItems2(PDO $pdo): array {
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
        $items[] = ['name'=>$name,'size'=>$size,'quantity'=>$qty,'price'=>$price,'subtotal'=>$price*$qty,'image'=>$image];
    }
    return $items;
}

$cartItems = loadCartItems2($pdo);
$cartTotal = array_sum(array_column($cartItems, 'subtotal'));
$freeEligible = $cartTotal >= 50;

// Handle POST — save address + shipping, go to payment
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address  = trim($_POST['address']  ?? '');
    $address2 = trim($_POST['address2'] ?? '');
    $city     = trim($_POST['city']     ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $country  = trim($_POST['country']  ?? 'United Kingdom');
    $method   = $_POST['shipping'] ?? 'standard';

    if (empty($address))  $errors[] = "Address is required";
    if (empty($city))     $errors[] = "City is required";
    if (empty($postcode)) $errors[] = "Postcode is required";

    if (empty($errors)) {
        $costs = ['standard' => 3.99, 'express' => 7.99, 'free' => 0.00];
        $cost  = $costs[$method] ?? 3.99;

        if ($method === 'free' && !$freeEligible) {
            $method = 'standard';
            $cost = 3.99;
        }

        $_SESSION['checkout']['address']         = $address;
        $_SESSION['checkout']['address2']        = $address2;
        $_SESSION['checkout']['city']            = $city;
        $_SESSION['checkout']['postcode']        = $postcode;
        $_SESSION['checkout']['country']         = $country;
        $_SESSION['checkout']['shipping_method'] = $method;
        $_SESSION['checkout']['shipping_cost']   = $cost;

        header("Location: checkout-payment.php");
        exit();
    }
}

$c = $_SESSION['checkout'] ?? [];

function fp2(float $v): string { return '£' . number_format($v, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout – Delivery – EveryWear</title>
<link rel="icon" type="image/png" href="logo.png">
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: "Inter", Arial, sans-serif; background: #f7f8fa; color: #333; min-height: 100vh; }

/* Progress */
.checkout-progress { max-width: 700px; margin: 30px auto 0; display: flex; align-items: center; justify-content: center; padding: 0 20px; }
.progress-step { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #bbb; white-space: nowrap; }
.progress-step.active { color: #111; }
.progress-step.done { color: #22c55e; }
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
.form-group input { width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; font-family: inherit; transition: border-color 0.2s; }
.form-group input:focus { outline: none; border-color: #111; }

/* Shipping cards */
.shipping-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 10px; }

.shipping-card {
    position: relative;
    padding: 24px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
}
.shipping-card:hover { border-color: #999; }
.shipping-card.selected { border-color: #111; background: #fafafa; box-shadow: 0 0 0 1px #111; }
.shipping-card.disabled { opacity: 0.45; cursor: not-allowed; }
.shipping-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.shipping-card-icon { font-size: 24px; margin-bottom: 14px; color: #555; }
.shipping-card-title { font-weight: 700; font-size: 16px; margin-bottom: 6px; color: #111; }
.shipping-card-time { font-size: 13px; color: #888; margin-bottom: 10px; }
.shipping-card-price { font-weight: 700; font-size: 16px; color: #111; }
.shipping-card-note { font-size: 12px; color: #888; margin-top: 10px; line-height: 1.5; }

/* Map */
.map-frame {
    width: 100%;
    height: 280px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    margin-top: 10px;
}

.map-note {
    font-size: 13px;
    color: #888;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Buttons */
.btn-row { display: flex; gap: 16px; margin-top: 10px; }
.btn-continue { flex: 1; padding: 16px; background: #111; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
.btn-continue:hover { background: #333; }
.btn-return { flex: 1; padding: 16px; background: white; color: #111; border: 2px solid #d1d5db; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; }
.btn-return:hover { border-color: #111; }

/* Sidebar */
.summary-card { background: #fff; border-radius: 10px; border: 1px solid #e5e7eb; padding: 24px; }
.summary-card h3 { font-size: 18px; margin-bottom: 20px; color: #111; }
.summary-product { display: flex; gap: 14px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid #f0f0f0; }
.summary-product:last-of-type { border-bottom: none; margin-bottom: 14px; }
.summary-product img { width: 80px; height: 100px; object-fit: cover; border-radius: 8px; background: #f3f3f3; }
.summary-product-info { flex: 1; }
.summary-product-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #111; }
.summary-product-meta { font-size: 13px; color: #888; line-height: 1.5; }
.summary-product-price { font-weight: 700; font-size: 14px; color: #111; white-space: nowrap; }
.summary-total { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 2px solid #f0f0f0; margin-top: 8px; }
.summary-total-label { font-size: 16px; font-weight: 700; color: #111; }
.summary-total-price { font-size: 18px; font-weight: 700; color: #111; }

.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.alert-error ul { margin: 6px 0 0 18px; padding: 0; }

@media (max-width: 860px) { .checkout-layout { flex-direction: column; } .checkout-sidebar { width: 100%; position: static; } }
@media (max-width: 560px) { .shipping-cards { grid-template-columns: 1fr; } }

/* Footer */
footer { background: #111; color: white; padding: 50px 20px 30px; margin-top: 80px; }
.footer-grid { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; }
.footer-col h4 { margin-bottom: 20px; font-size: 16px; font-weight: 600; }
.footer-col ul { list-style: none; padding: 0; margin: 0; }
.footer-col li { margin-bottom: 10px; }
.footer-col a { color: #aaa; text-decoration: none; font-size: 14px; transition: color 0.2s; }
.footer-col a:hover { color: white; }
.social-icons { display: flex; gap: 15px; margin-top: 5px; }
.social-icons i { font-size: 20px; color: #aaa; cursor: pointer; transition: color 0.2s, transform 0.2s; }
.social-icons i:hover { color: white; transform: translateY(-2px); }
.copyright { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #333; color: #888; font-size: 14px; }
@media (max-width: 900px) { .footer-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .footer-grid { grid-template-columns: 1fr; } .footer-col { text-align: center; } .social-icons { justify-content: center; } }
</style>
</head>
<body>

<div style="max-width: 1100px; margin: 20px auto 0; padding: 0 20px;">
    <a href="checkout.php" class="back-link"><i class="ri-arrow-left-line"></i></a>
</div>

<!-- PROGRESS — Step 2 -->
<div class="checkout-progress">
    <div class="progress-step done"><span class="progress-dot"></span> Details</div>
    <div class="progress-line done"></div>
    <div class="progress-step active"><span class="progress-dot"></span> Delivery</div>
    <div class="progress-line"></div>
    <div class="progress-step"><span class="progress-dot"></span> Payment</div>
</div>

<div class="checkout-layout">
    <div class="checkout-main">

        <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <strong>Please fix the following:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" id="delivery-form">

            <!-- ── DELIVERY ADDRESS ── -->
            <div class="box">
                <h2>Delivery details</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <input type="text" id="address" name="address" placeholder="e.g. 12 High Street"
                               value="<?php echo htmlspecialchars($c['address'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="address2">More information (Optional)</label>
                        <input type="text" id="address2" name="address2" placeholder="Flat, floor, etc."
                               value="<?php echo htmlspecialchars($c['address2'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="postcode">Postcode *</label>
                        <input type="text" id="postcode" name="postcode" placeholder="e.g. B4 6FL"
                               value="<?php echo htmlspecialchars($c['postcode'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" placeholder="e.g. Birmingham"
                               value="<?php echo htmlspecialchars($c['city'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country"
                               value="<?php echo htmlspecialchars($c['country'] ?? 'United Kingdom'); ?>" readonly
                               style="background: #f9fafb; color: #888;">
                    </div>
                    <div class="form-group" style="flex: 0;"></div>
                </div>
            </div>

            <!-- ── YOUR LOCATION (MAP) ── -->
            <div class="box">
                <h2><i class="ri-map-pin-line" style="margin-right: 6px;"></i>Your Location</h2>
                <iframe id="locationMap"
                        src="https://maps.google.com/maps?q=<?php echo urlencode(($c['city'] ?? 'Birmingham') . ' ' . ($c['postcode'] ?? '') . ' UK'); ?>&output=embed"
                        class="map-frame"
                        allowfullscreen
                        loading="lazy"></iframe>
                <p class="map-note">
                    <i class="ri-information-line"></i>
                    Map updates automatically when you change your city or postcode
                </p>
            </div>

            <!-- ── DELIVERY METHOD ── -->
            <div class="box">
                <h2>How should we send it?</h2>

                <div class="shipping-cards">
                    <label class="shipping-card selected" id="card-standard">
                        <input type="radio" name="shipping" value="standard" checked>
                        <div class="shipping-card-icon"><i class="ri-truck-line"></i></div>
                        <div class="shipping-card-title">Standard Delivery</div>
                        <div class="shipping-card-time">3–5 business days</div>
                        <div class="shipping-card-price">£3.99</div>
                    </label>

                    <label class="shipping-card" id="card-express">
                        <input type="radio" name="shipping" value="express">
                        <div class="shipping-card-icon"><i class="ri-flashlight-line"></i></div>
                        <div class="shipping-card-title">Express Delivery</div>
                        <div class="shipping-card-time">1–2 business days</div>
                        <div class="shipping-card-price">£7.99</div>
                    </label>

                    <label class="shipping-card <?php echo $freeEligible ? '' : 'disabled'; ?>" id="card-free">
                        <input type="radio" name="shipping" value="free" <?php echo $freeEligible ? '' : 'disabled'; ?>>
                        <div class="shipping-card-icon"><i class="ri-gift-line"></i></div>
                        <div class="shipping-card-title">Free Delivery</div>
                        <div class="shipping-card-time">5–7 business days</div>
                        <div class="shipping-card-price">FREE</div>
                        <div class="shipping-card-note">
                            <?php echo $freeEligible
                                ? '✓ Your order qualifies!'
                                : 'Available on orders over £50'; ?>
                        </div>
                    </label>
                </div>
            </div>

            <p style="font-size: 13px; color: #888; margin-bottom: 16px;">Fields marked with an asterisk are mandatory.</p>

            <div class="btn-row">
                <a href="checkout.php" class="btn-return">Return</a>
                <button type="submit" class="btn-continue">Continue</button>
            </div>
        </form>
    </div>

    <!-- SIDEBAR -->
    <div class="checkout-sidebar">
        <div class="summary-card">
            <h3>Purchase summary</h3>
            <?php foreach ($cartItems as $item): ?>
            <div class="summary-product">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                <div class="summary-product-info">
                    <div class="summary-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="summary-product-meta">
                        <?php echo fp2($item['price']); ?><br>
                        <?php echo (int)$item['quantity']; ?> item
                        <?php if ($item['size']): ?> · <?php echo htmlspecialchars($item['size']); ?><?php endif; ?>
                    </div>
                </div>
                <div class="summary-product-price"><?php echo fp2($item['subtotal']); ?></div>
            </div>
            <?php endforeach; ?>
            <div class="summary-total">
                <span class="summary-total-label">Total</span>
                <span class="summary-total-price"><?php echo fp2($cartTotal); ?></span>
            </div>
        </div>
    </div>
</div>

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

<script>
document.addEventListener("DOMContentLoaded", () => {

    // ── Card selection ──
    document.querySelectorAll('.shipping-card:not(.disabled)').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.shipping-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
        });
    });

    // ── Auto-update map when city/postcode changes ──
    const cityInput    = document.getElementById("city");
    const postcodeInput = document.getElementById("postcode");
    const mapFrame     = document.getElementById("locationMap");
    let mapTimeout;

    function updateMap() {
        const city     = cityInput.value.trim();
        const postcode = postcodeInput.value.trim();
        const query    = (city + " " + postcode + " UK").trim();

        if (query.length > 3) {
            mapFrame.src = "https://maps.google.com/maps?q=" + encodeURIComponent(query) + "&output=embed";
        }
    }

    // Debounce — update 800ms after user stops typing
    function debouncedUpdate() {
        clearTimeout(mapTimeout);
        mapTimeout = setTimeout(updateMap, 800);
    }

    if (cityInput)     cityInput.addEventListener("input", debouncedUpdate);
    if (postcodeInput) postcodeInput.addEventListener("input", debouncedUpdate);
});
</script>

</body>
</html>

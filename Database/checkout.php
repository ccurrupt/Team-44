<?php
// checkout.php — Step 1: Personal Data only

require_once 'dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=login_required");
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? '';
$lastName = $_SESSION['last_name']  ?? '';
$email    = $_SESSION['email']      ?? '';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cartCount = count($_SESSION['cart']);

// Cart loader for sidebar
function loadCartItems(PDO $pdo): array {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) return [];
    $items = [];
    foreach ($_SESSION['cart'] as $raw) {
        if (!is_array($raw)) continue;
        $pid = $raw['product_id'] ?? $raw['id'] ?? null;
        if (!$pid) continue;
        $size  = $raw['size'] ?? ($raw['variant'] ?? '');
        $qty   = max(1, (int)($raw['quantity'] ?? 1));
        $name  = $raw['name']  ?? '';
        $price = isset($raw['price']) ? (float)$raw['price'] : null;
        $image = $raw['image'] ?? 'images/placeholder.jpg';
        if ($price === null || $name === '') {
            try {
                $stmt = $pdo->prepare("SELECT name, price FROM Products WHERE product_id = ?");
                $stmt->execute([$pid]);
                if ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($name === '')    $name  = $r['name'];
                    if ($price === null) $price = (float)$r['price'];
                }
            } catch (PDOException $e) {}
        }
        if ($price === null) continue;
        $items[] = ['product_id'=>(int)$pid,'name'=>$name,'size'=>$size,'quantity'=>$qty,'price'=>$price,'subtotal'=>$price*$qty,'image'=>$image];
    }
    return $items;
}

$cartItems = loadCartItems($pdo);
$cartTotal = array_sum(array_column($cartItems, 'subtotal'));

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// Handle POST — save personal data, go to step 2
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkout = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name'  => trim($_POST['last_name']  ?? ''),
        'email'      => trim($_POST['email']       ?? ''),
        'phone'      => trim($_POST['phone']       ?? ''),
    ];

    if (empty($checkout['first_name'])) $errors[] = "First name is required";
    if (empty($checkout['last_name']))  $errors[] = "Last name is required";
    if (empty($checkout['email']) || !filter_var($checkout['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = "Valid email is required";
    if (empty($checkout['phone'])) $errors[] = "Phone number is required";

    if (empty($errors)) {
        // Keep existing checkout data (address etc.) if set, merge personal data
        $_SESSION['checkout'] = array_merge($_SESSION['checkout'] ?? [], $checkout);
        header("Location: checkout-shipping.php");
        exit();
    }
}

$c = $_SESSION['checkout'] ?? [];

function fp(float $v): string { return '£' . number_format($v, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout – Details – EveryWear</title>
<link rel="icon" type="image/png" href="logo.png">
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: "Inter", Arial, sans-serif; background: #f7f8fa; color: #333; min-height: 100vh; }

/* ── PROGRESS BAR ── */
.checkout-progress { max-width: 700px; margin: 30px auto 0; display: flex; align-items: center; justify-content: center; gap: 0; padding: 0 20px; }
.progress-step { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #bbb; white-space: nowrap; }
.progress-step.active { color: #111; }
.progress-step.done { color: #22c55e; }
.progress-line { flex: 1; height: 2px; background: #ddd; margin: 0 16px; min-width: 40px; }
.progress-line.done { background: #22c55e; }
.progress-dot { width: 10px; height: 10px; border-radius: 50%; border: 2px solid #bbb; background: white; flex-shrink: 0; }
.progress-step.active .progress-dot { border-color: #111; background: #111; }
.progress-step.done .progress-dot { border-color: #22c55e; background: #22c55e; }

/* ── LAYOUT ── */
.checkout-layout { max-width: 1100px; margin: 30px auto; padding: 0 20px; display: flex; gap: 40px; align-items: flex-start; }
.checkout-main { flex: 1; min-width: 0; }
.checkout-sidebar { width: 340px; flex-shrink: 0; position: sticky; top: 100px; }

.box { background: #fff; padding: 28px; border-radius: 10px; margin-bottom: 24px; border: 1px solid #e5e7eb; }
.box h2 { font-size: 20px; margin-bottom: 20px; color: #111; }

.form-row { display: flex; gap: 16px; }
.form-group { flex: 1; margin-bottom: 18px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
.form-group input { width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; font-family: inherit; transition: border-color 0.2s; }
.form-group input:focus { outline: none; border-color: #111; }

.btn-row { display: flex; gap: 16px; margin-top: 10px; }
.btn-continue { flex: 1; padding: 16px; background: #111; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
.btn-continue:hover { background: #333; }
.btn-return { flex: 1; padding: 16px; background: white; color: #111; border: 2px solid #d1d5db; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; }
.btn-return:hover { border-color: #111; }

.back-link { display: inline-flex; align-items: center; gap: 6px; color: #111; text-decoration: none; font-size: 22px; margin-bottom: 10px; transition: opacity 0.2s; }
.back-link:hover { opacity: 0.6; }

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
    <a href="cart.php" class="back-link"><i class="ri-arrow-left-line"></i></a>
</div>

<!-- PROGRESS BAR — Step 1 -->
<div class="checkout-progress">
    <div class="progress-step active"><span class="progress-dot"></span> Details</div>
    <div class="progress-line"></div>
    <div class="progress-step"><span class="progress-dot"></span> Delivery</div>
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

        <form method="POST">
            <div class="box">
                <h2>Personal data</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($c['first_name'] ?? $userName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last name *</label>
                        <input type="text" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($c['last_name'] ?? $lastName); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($c['email'] ?? $email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone number *</label>
                        <input type="text" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($c['phone'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>

            <p style="font-size: 13px; color: #888; margin-bottom: 16px;">Fields marked with an asterisk are mandatory.</p>

            <div class="btn-row">
                <a href="cart.php" class="btn-return">Return</a>
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
                        <?php echo fp($item['price']); ?><br>
                        <?php echo (int)$item['quantity']; ?> item
                        <?php if ($item['size']): ?> · <?php echo htmlspecialchars($item['size']); ?><?php endif; ?>
                    </div>
                </div>
                <div class="summary-product-price"><?php echo fp($item['subtotal']); ?></div>
            </div>
            <?php endforeach; ?>
            <div class="summary-total">
                <span class="summary-total-label">Total</span>
                <span class="summary-total-price"><?php echo fp($cartTotal); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-grid">
        <div class="footer-col"><h4>Shop</h4><ul><li><a href="products.php">Men</a></li><li><a href="products.php">Women</a></li><li><a href="products.php">Accessories</a></li><li><a href="products.php">New Arrivals</a></li></ul></div>
        <div class="footer-col"><h4>Help</h4><ul><li><a href="contact.php">Contact Us</a></li><li><a href="checkout-shipping.php">Shipping Info</a></li><li><a href="#">Returns</a></li><li><a href="#">FAQ</a></li></ul></div>
        <div class="footer-col"><h4>About</h4><ul><li><a href="about.php">Our Story</a></li><li><a href="#">Sustainability</a></li><li><a href="#">Careers</a></li><li><a href="#">Press</a></li></ul></div>
        <div class="footer-col"><h4>Connect</h4><div class="social-icons"><i class="ri-instagram-line"></i><i class="ri-facebook-circle-line"></i><i class="ri-twitter-line"></i><i class="ri-tiktok-line"></i></div><p style="margin-top:20px;color:#aaa;font-size:14px;">Student project for university</p></div>
    </div>
    <div class="copyright">&copy; 2025 EveryWear. This is a university project.</div>
</footer>
</body>
</html>

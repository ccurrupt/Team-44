<?php
session_start();

// Check login
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

// Must be logged in to checkout
if (!$isLoggedIn) {
    header("Location: login.php?error=login_required");
    exit();
}

// Cart setup
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartCount = count($_SESSION['cart']);

// Calculate subtotal from session cart
$subtotal = 0.0;
foreach ($_SESSION['cart'] as $item) {
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $qty   = isset($item['quantity']) ? (int)$item['quantity'] : 1;
    $subtotal += $price * $qty;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EveryWear - Checkout</title>
<link rel="icon" type="image/png" href="logo.png">
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>

<style>
/* LAYOUT */
.checkout-wrapper {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
}

.checkout-left {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.checkout-right {
    position: sticky;
    top: 100px;
}

/* BOX */
.box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #e3e3e3;
}

.box h2 { margin-bottom: 15px; }

/* INPUT */
.box input[type="text"] {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.two-cols {
    display: flex;
    gap: 10px;
}

/* DELIVERY */
.delivery-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    margin-top: 12px;
    cursor: pointer;
}

.delivery-card input { display: none; }

.delivery-card:has(input:checked) {
    border: 2px solid black;
    background: #f9f9f9;
}

/* ORDER ITEMS */
.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.item-left {
    display: flex;
    gap: 12px;
    align-items: center;
}

.item-img {
    width: 55px;
    height: 55px;
    object-fit: cover;
    border-radius: 8px;
}

.item-name {
    font-weight: 600;
    font-size: 14px;
}

.item-meta {
    font-size: 12px;
    color: #777;
}

.item-price {
    font-weight: 600;
}

/* TOTALS */
.totals p, .totals h3 {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

/* BUTTON */
.btn {
    width: 100%;
    padding: 16px;
    margin-top: 20px;
    background: black;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
}

.btn:hover {
    background: #333;
}

/* MAP */
.map {
    width: 100%;
    height: 250px;
    border-radius: 10px;
    border: none;
}

/* FOOTER */
footer {
    background: #111 !important;
    color: white !important;
    padding: 50px 20px 30px !important;
    margin-top: 40px !important;
    display: block !important;
    width: 100% !important;
}

.footer-grid {
    max-width: 1200px !important;
    margin: 0 auto !important;
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 40px !important;
    padding: 0 1.5rem !important;
}

.footer-col h4 { margin-bottom: 20px; font-size: 16px; }
.footer-col ul { list-style: none !important; padding: 0 !important; margin: 0 !important; }
.footer-col li { margin-bottom: 10px; }
.footer-col a { color: #d1d5db !important; text-decoration: none !important; }
.footer-col a:hover { color: white !important; }

.social-icons { display: flex; gap: 15px; margin-top: 15px; }
.social-icons i { font-size: 20px; cursor: pointer; color: #f3f4f6; }

.copyright {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #333;
    color: #888;
    font-size: 14px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .checkout-wrapper {
        grid-template-columns: 1fr;
    }
    .checkout-right {
        position: static;
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
<!-- CHECKOUT -->
<div class="checkout-wrapper">

    <!-- LEFT -->
    <div class="checkout-left">

        <div class="box">
            <h2>Shipping Information</h2>
            <input type="text" placeholder="Full Name">
            <input type="text" placeholder="Address">
            <input type="text" placeholder="City">
            <div class="two-cols">
                <input type="text" placeholder="Postcode">
                <input type="text" placeholder="Country">
            </div>
        </div>

        <div class="box">
            <h2>Delivery Method</h2>
            <label class="delivery-card">
                <input type="radio" name="delivery" value="3.99" checked>
                <div>
                    <strong>Standard Delivery</strong>
                    <p>3–5 days</p>
                </div>
                <span>£3.99</span>
            </label>
            <label class="delivery-card">
                <input type="radio" name="delivery" value="7.99">
                <div>
                    <strong>Express Delivery</strong>
                    <p>1–2 days</p>
                </div>
                <span>£7.99</span>
            </label>
        </div>

        <div class="box">
            <h2>Your Location</h2>
            <iframe src="https://maps.google.com/maps?q=Birmingham%20UK&output=embed" class="map"></iframe>
        </div>

    </div>

    <!-- RIGHT -->
    <div class="checkout-right">
        <div class="box">

            <h2>Order Summary</h2>

            <?php if (empty($_SESSION['cart'])): ?>
                <p style="color: #777; margin-top: 10px;">Your cart is empty.</p>
            <?php else: ?>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <?php
                        $name  = $item['name']  ?? 'Item';
                        $size  = $item['size']  ?? 'N/A';
                        $img   = $item['image'] ?? 'images/placeholder.jpg';
                        $price = isset($item['price']) ? (float)$item['price'] : 0;
                        $qty   = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                        $itemTotal = $price * $qty;
                    ?>
                    <div class="summary-item">
                        <div class="item-left">
                            <img src="<?php echo htmlspecialchars($img); ?>" class="item-img">
                            <div>
                                <p class="item-name"><?php echo htmlspecialchars($name); ?></p>
                                <p class="item-meta">Size: <?php echo htmlspecialchars($size); ?> • Qty: <?php echo $qty; ?></p>
                            </div>
                        </div>
                        <span class="item-price">£<?php echo number_format($itemTotal, 2); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <hr style="margin-top: 15px;">

            <div class="totals">
                <p><span>Subtotal</span><span id="subtotal">£<?php echo number_format($subtotal, 2); ?></span></p>
                <p><span>Shipping</span><span id="shipping">£3.99</span></p>
                <h3><span>Total</span><span id="total">£<?php echo number_format($subtotal + 3.99, 2); ?></span></h3>
            </div>

            <button class="btn" onclick="window.location.href='payment.php'">Continue to Payment</button>

        </div>
    </div>

</div>

<!-- FOOTER -->
<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4>Shop</h4>
            <ul>
                <li><a href="products.php">Men</a></li>
                <li><a href="products.php">Women</a></li>
                <li><a href="products.php">Accessories</a></li>
                <li><a href="products.php">New Arrivals</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Help</h4>
            <ul>
                <li><a href="#">Contact Us</a></li>
                <li><a href="#">Shipping Info</a></li>
                <li><a href="#">Returns</a></li>
                <li><a href="#">FAQ</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>About</h4>
            <ul>
                <li><a href="about.php">Our Story</a></li>
                <li><a href="#">Sustainability</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Press</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Connect</h4>
            <div class="social-icons">
                <i class="ri-instagram-line"></i>
                <i class="ri-facebook-circle-line"></i>
                <i class="ri-twitter-line"></i>
                <i class="ri-tiktok-line"></i>
            </div>
            <p style="margin-top: 20px; color: #aaa; font-size: 14px;">
                Student project for university
            </p>
        </div>
    </div>
    <div class="copyright">
        &copy; 2025 EveryWear. This is a university project.
    </div>
</footer>

<script>
// Delivery toggle — updates shipping & total dynamically
document.addEventListener("DOMContentLoaded", () => {
    const subtotal = <?php echo $subtotal; ?>;
    const shippingText = document.getElementById("shipping");
    const totalText = document.getElementById("total");
    let shipping = 3.99;

    document.querySelectorAll('input[name="delivery"]').forEach(option => {
        option.addEventListener("change", () => {
            shipping = parseFloat(option.value) || 0;
            shippingText.textContent = `£${shipping.toFixed(2)}`;
            totalText.textContent = `£${(subtotal + shipping).toFixed(2)}`;
        });
    });
});
</script>


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

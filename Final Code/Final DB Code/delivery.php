<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartCount = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EveryWear - Delivery & Returns</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f6f8fb;
            margin: 0;
            color: #2d2d2d;
        }

        /* HERO */
        .page-hero {
            max-width: 900px;
            margin: 10px auto 10px;
            text-align: center;
            padding: 10px 5px;
            background: linear-gradient(135deg, #eef3ff, #ffffff);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .page-hero h1 {
            font-size: 40px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 5px;
        }

        .page-hero p {
            font-size: 18px;
            color: #6b7280;
        }

        /* CONTENT */
        .content-container {
            max-width: 900px;
            margin: 20px auto 40px;
            padding: 0 20px;
        }

        .info-card {
            background: #ffffff;
            border-radius: 14px;
            margin-bottom: 18px;
            padding: 24px 28px;
            box-shadow: 0 8px 10px rgba(0,0,0,0.05);
            border: 1px solid #f1f1f1;
            animation: fadeIn 0.4s ease;
        }

        .info-card h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .info-card p {
            line-height: 1.7;
            color: #555;
        }

        .info-card ul {
            padding-left: 18px;
        }

        .info-card li {
            margin-bottom: 8px;
            color: #555;
            line-height: 1.7;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f3f6ff;
            font-weight: 600;
            color: #1f2937;
        }

        /* FOOTER SPACING */
        footer {
            margin-top: 60px;
            padding-top: 40px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- TOP NAVIGATION -->
<div class="navbar">
    <div class="logo-section">
        <a href="index.php" class="logo-link" aria-label="Go to homepage">
            <img src="images/logo.png" loading="eager" alt="EveryWear Logo" width="120" height="90" class="site-logo">
        </a>
    </div>

    <div class="nav-buttons">
        <a href="about.php" class="nav-button">About Us</a>
        <a href="productline.php" class="nav-button">Products</a>
        <a href="reviews.php" class="nav-button">Reviews</a>
        <a href="contact.php" class="nav-button">Contact Us</a>
    </div>

    <div class="right-controls" id="rightControls">
        <div class="right-default" id="rightDefault">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-msg">Hi <?= htmlspecialchars($userName) ?>!</span>
                <?php if (!empty($_SESSION['is_admin'])): ?>
                    <a href="admin-orders.php" class="login-btn" style="background:#111;color:white;">Admin</a>
                <?php endif; ?>
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

            <button id="themeToggle" class="icon-link" aria-label="Toggle theme">
                <span id="themeIcon">🌙</span>
            </button>
        </div>

        <div id="searchBar" class="search-bar-overlay">
            <input type="text" placeholder="Search...">
        </div>

        <div class="icon-link search-close" id="searchClose">
            <img src="images/search.png" alt="Close Search" class="nav-icon">
        </div>
    </div>
</div>

<!-- HERO -->
<div class="page-hero">
    <h1>Delivery &amp; Returns</h1>
    <p>Everything you need to know about shipping, delivery times, and returns.</p>
</div>

<!-- CONTENT -->
<div class="content-container">

    <div class="info-card">
        <h2>Delivery Information</h2>
        <p>We aim to deliver your order as quickly and efficiently as possible. All orders are processed within 1–2 business days.</p>

        <table>
            <tr>
                <th>Shipping Option</th>
                <th>Estimated Time</th>
                <th>Cost</th>
            </tr>
            <tr>
                <td>Standard Delivery</td>
                <td>3–5 Business Days</td>
                <td>£3.99</td>
            </tr>
            <tr>
                <td>Express Delivery</td>
                <td>1–2 Business Days</td>
                <td>£6.99</td>
            </tr>
            <tr>
                <td>Free Delivery</td>
                <td>3–5 Business Days</td>
                <td>Free on orders over £50</td>
            </tr>
        </table>
    </div>

    <div class="info-card">
        <h2>Order Tracking</h2>
        <p>Once your order has been dispatched, you will receive an email with tracking information. You can use this to monitor your delivery in real time.</p>
    </div>

    <div class="info-card">
        <h2>Returns Policy</h2>
        <p>If you are not completely satisfied with your purchase, you can return your items within 30 days of receiving your order.</p>
        <ul>
            <li>Items must be unworn and in original condition</li>
            <li>All tags must be attached</li>
            <li>Proof of purchase is required</li>
        </ul>
    </div>

    <div class="info-card">
        <h2>How to Return an Item</h2>
        <ul>
            <li>Go to your account and select your order</li>
            <li>Request a return and print your label</li>
            <li>Package your item securely</li>
            <li>Drop it off at your nearest post office</li>
        </ul>
    </div>

    <div class="info-card">
        <h2>Refunds</h2>
        <p>Once we receive your returned item, your refund will be processed within 5–7 business days. Refunds will be issued to your original payment method.</p>
    </div>

</div>

<!-- FOOTER -->
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
                <li><a href="delivery.php">Delivery &amp; Returns</a></li>
                <li><a href="login.php">10% Student Discount</a></li>
                <li><a href="FAQ.php">FAQs</a></li>
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
                    <a href="#">
                        <img src="images/image1.png" alt="Get it on Google Play">
                    </a>
                    <a href="#">
                        <img src="images/image2.png" alt="Download on the App Store">
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        © 2025 EveryWear. All rights reserved.
    </div>
</footer>

<!-- SCRIPTS -->
<script>
// Search toggle
const searchToggle = document.getElementById("searchToggle");
const searchClose = document.getElementById("searchClose");
const searchBar = document.getElementById("searchBar");
const rightDefault = document.getElementById("rightDefault");

function openSearch() {
    rightDefault.classList.add("hidden");
    searchBar.classList.add("active");
}
function closeSearch() {
    searchBar.classList.remove("active");
    rightDefault.classList.remove("hidden");
}
searchToggle.addEventListener("click", () => {
    searchBar.classList.contains("active") ? closeSearch() : openSearch();
});
searchClose.addEventListener("click", closeSearch);

// Dark mode
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");

if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark");
    themeIcon.textContent = "☀️";
}

themeToggle.addEventListener("click", () => {
    document.body.classList.toggle("dark");
    if (document.body.classList.contains("dark")) {
        localStorage.setItem("theme", "dark");
        themeIcon.textContent = "☀️";
    } else {
        localStorage.setItem("theme", "light");
        themeIcon.textContent = "🌙";
    }
});
</script>
<?php include 'chatbot-widget.php'; ?>
</body>
</html>

<?php
// Start session at the very beginning
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] ?? '') : '';

// Initialize cart for cart count
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
<title>EveryWear - Home</title>
<link
    href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css"
    rel="stylesheet"
/>
<style>
/* RESET AND BASE STYLES */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f7f8fa;
    color: #333;
    line-height: 1.6;
}

/* NAVBAR STYLES - Matching about.php */
.navbar {
    background: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.logo-section img {
    height: 50px;
}

.nav-buttons {
    display: flex;
    gap: 20px;
}

.nav-button {
    text-decoration: none;
    color: #333;
    padding: 8px 15px;
    border-radius: 5px;
    transition: 0.3s;
    font-weight: 500;
}

.nav-button:hover {
    background: #e0e0e0;
}

.nav-button.active {
    background: #0066cc;
    color: white;
}

.right-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

/* USER AUTH BUTTONS */
.login-btn, .create-btn {
    padding: 8px 20px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: 0.3s;
}

.login-btn {
    background: #0066cc;
    color: white;
    border: none;
    cursor: pointer;
}

.login-btn:hover {
    background: #0052a3;
}

.create-btn {
    background: black;
    color: white;
    border: none;
    cursor: pointer;
}

.create-btn:hover {
    background: #333;
}

.logout-btn {
    padding: 8px 20px;
    background: #e35f26;
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
}

.logout-btn:hover {
    background: #d44a15;
}

.welcome-msg {
    color: #0066cc;
    font-weight: bold;
    margin-right: 10px;
}

/* CART AND ICONS */
.icon-link {
    position: relative;
    text-decoration: none;
    color: #333;
}

.nav-icon {
    width: 24px;
    height: 24px;
}

.cart-count-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e35f26;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* HERO SECTION */
.hero {
    padding: 100px 20px;
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    margin-bottom: 40px;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.hero h1 {
    font-size: 48px;
    margin-bottom: 20px;
}

.hero h2 {
    font-size: 24px;
    margin-bottom: 30px;
    font-weight: normal;
    opacity: 0.9;
}

/* SEARCH BAR */
.search-container {
    max-width: 600px;
    margin: 40px auto;
    padding: 0 20px;
}

.search-bar {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 30px;
    padding: 10px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-bar input {
    flex: 1;
    border: none;
    outline: none;
    padding: 10px;
    font-size: 16px;
}

.search-bar button {
    background: #0066cc;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 500;
}

.search-bar button:hover {
    background: #0052a3;
}

/* FEATURED PRODUCTS */
.featured-products {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.featured-products h2 {
    text-align: center;
    margin-bottom: 40px;
    font-size: 32px;
    color: #333;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

.product-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-card img {
    width: 100%;
    height: 300px;
    object-fit: cover;
}

.product-info {
    padding: 20px;
}

.product-info h3 {
    margin-bottom: 10px;
    font-size: 18px;
}

.product-info .price {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
}

.product-info button {
    width: 100%;
    padding: 12px;
    background: black;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
}

.product-info button:hover {
    background: #333;
}

/* FOOTER */
footer {
    background: #111;
    color: white;
    padding: 50px 20px 30px;
    margin-top: 80px;
}

.footer-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 40px;
}

.footer-col h4 {
    margin-bottom: 20px;
    font-size: 16px;
}

.footer-col ul {
    list-style: none;
}

.footer-col li {
    margin-bottom: 10px;
}

.footer-col a {
    color: #aaa;
    text-decoration: none;
}

.footer-col a:hover {
    color: white;
}

.social-icons {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.social-icons i {
    font-size: 20px;
    cursor: pointer;
}

.copyright {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #333;
    color: #888;
    font-size: 14px;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .navbar {
        flex-direction: column;
        gap: 15px;
        padding: 15px;
    }
    
    .nav-buttons {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .right-controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hero h1 {
        font-size: 36px;
    }
    
    .hero h2 {
        font-size: 20px;
    }
}

@media (max-width: 600px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<!-- NAVBAR - Same as about.php -->
<div class="navbar">
    <!-- LOGO -->
    <div class="logo-section">
        <a href="index.php">
            <img src="logo.png" alt="EveryWear">
        </a>
    </div>
    
    <!-- NAVIGATION BUTTONS -->
    <div class="nav-buttons">
        <a href="index.php" class="nav-button active">Home</a>
        <a href="about.php" class="nav-button">About</a>
        <a href="products.php" class="nav-button">Products</a>
        <a href="reviews.php" class="nav-button">Reviews</a>
        <a href="orders.php" class="nav-button">Orders</a>
    </div>
    
    <!-- RIGHTSIDE CONTROLS -->
    <div class="right-controls">
        <?php if($isLoggedIn): ?>
            <!-- User is logged in -->
            <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <!-- User is NOT logged in -->
            <a href="login.php" class="login-btn">Login</a>
            <a href="register.php" class="create-btn">Sign Up</a>
        <?php endif; ?>
        
        <!-- Cart icon with count -->
        <a href="cart.php" class="icon-link">
             <img src="basket.png" alt="Cart" class="nav-icon">
        	<?php if($cartCount > 0): ?>
            	<span class="cart-count-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- HERO SECTION -->
<div class="hero">
    <div class="hero-content">
        <h1>Welcome to EveryWear</h1>
        <h2>Style that fits your everyday life</h2>
        <p>Discover comfortable, fashionable clothing for every occasion</p>
    </div>
</div>

<!-- SEARCH BAR -->
<div class="search-container">
    <div class="search-bar">
        <input type="text" placeholder="Search for products..." id="searchInput">
        <button onclick="performSearch()">Search</button>
    </div>
</div>

<!-- FEATURED PRODUCTS -->
<div class="featured-products">
    <h2>Featured Products</h2>
    <div class="products-grid">
        <!-- Product 1 -->
        <div class="product-card">
            <img src="images/tshirts1_main.jpg" alt="T-Shirt">
            <div class="product-info">
                <h3>Basic Cotton T-Shirt</h3>
                <p>Comfortable everyday essential</p>
                <div class="price">£14.99</div>
                <button onclick="window.location.href='product-detail.php?id=1'">View Details</button>
            </div>
        </div>
        
        <!-- Product 2 -->
        <div class="product-card">
            <img src="images/hoodies1_main.jpg" alt="Hoodies">
            <div class="product-info">
                <h3>Heavyweight Logo Hoodie</h3>
                <p>Slim fit with premium fabric</p>
                <div class="price">£24.99</div>
                <button onclick="window.location.href='product-detail.php?id=2'">View Details</button>
            </div>
        </div>
        
        <!-- Product 3 -->
        <div class="product-card">
            <img src="images/jeans1_main.jpg" alt="Jeans">
            <div class="product-info">
                <h3>Two-Toned Panel Jeans</h3>
                <p>Warm and cozy for cooler days</p>
                <div class="price">£39.99</div>
                <button onclick="window.location.href='product-detail.php?id=3'">View Details</button>
            </div>
        </div>
        
       
    </div>
</div>

<!-- WELCOME MESSAGE -->
<div style="max-width: 800px; margin: 60px auto; padding: 0 20px; text-align: center;">
    <?php if(!$isLoggedIn): ?>
        <div style="background: #f0f8ff; padding: 30px; border-radius: 10px; margin: 20px 0;">
            <h2>Welcome Guest!</h2>
            <p style="margin: 15px 0;">Please <a href="login.php" style="color: #0066cc; font-weight: bold;">log in</a> or <a href="register.php" style="color: #0066cc; font-weight: bold;">create an account</a> to access all features.</p>
            <p>Registered users can save items to wishlist, track orders, and get personalized recommendations.</p>
        </div>
    <?php else: ?>
        <div style="background: #f0fff0; padding: 30px; border-radius: 10px; margin: 20px 0;">
            <h2>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p style="margin: 15px 0;">You're logged in and can now shop, view orders, and manage your wishlist.</p>
            <p>Check out our <a href="products.php" style="color: #0066cc; font-weight: bold;">new arrivals</a> or continue shopping!</p>
        </div>
    <?php endif; ?>
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
// Search function
function performSearch() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    if (searchTerm) {
        window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
    }
}

// Search on Enter key
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        performSearch();
    }
});

// Auto-open cart if we just added an item (for when coming from product page)
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('action') && urlParams.get('action') === 'add') {
        // Show success message
        alert('Item added to cart!');
    }
});
</script>

</body>
</html>

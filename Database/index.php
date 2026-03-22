<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EveryWear</title>
<link rel="icon" type="image/png" href="images/logo.png">

    <!-- Inline styles removed; all styles are now centrally defined in style.css -->
    <link rel="stylesheet" href="style.css">
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

                <!-- DARK MODE TOGGLE -->
                <button id="themeToggle" class="icon-link" aria-label="Toggle theme">
    					<span id="themeIcon">🌙</span>
				</button>
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

    <!-- SCROLLING BANNER -->
    <a href="productline.php" class="banner-link">
        <div class="scroll-banner">
            <div class="scroll-track">
                <span>New Collection Just Dropped — Get Yours Now! •</span>
                <span>New Collection Just Dropped — Get Yours Now! •</span>
                <span>New Collection Just Dropped — Get Yours Now! •</span>
                <span>New Collection Just Dropped — Get Yours Now! •</span>
                <span>New Collection Just Dropped — Get Yours Now! •</span>
                <span>New Collection Just Dropped — Get Yours Now! •</span>
            </div>
        </div>
    </a>

    <!-- MAIN CONTENT -->
    <div class="hero">
        <div>
            <h1>Welcome to EveryWear.</h1>
            <p class="search-heading">What are you looking for?</p>
        </div>
        <div class="hero-buttons">
    <button class="category-btn" data-category="Outerwear">Outerwear</button>
    <button class="category-btn" data-category="Tops">Tops</button>
    <button class="category-btn" data-category="Bottoms">Bottoms</button>
    <button class="category-btn" data-category="Footwear">Footwear</button>
    <button class="category-btn" data-category="Accessories">Accessories</button>
</div>
        <div>
            <h2>Why YOU should shop with us:</h2>
        </div>
        <section class="brand-values">
            <div class="value">
                <h3>✨ Premium Quality</h3>
                <p>Crafted with comfort and durability in mind for everyday wear.</p>
            </div>
            <div class="value">
                <h3>🚚 Fast Delivery</h3>
                <p>Tracked shipping on all orders, right to your door.</p>
            </div>
            <div class="value">
                <h3>♻️ Eco-Friendly</h3>
                <p>We prioritize sustainable materials and ethical production.</p>
            </div>
        </section>
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
                    <li><a href="contact.php">Delivery & Returns</a></li>
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
    if (searchBar.classList.contains("active")) {
        closeSearch();
    } else {
        openSearch();
    }
});

searchClose.addEventListener("click", closeSearch);

document.querySelectorAll('.category-btn[data-category]').forEach(btn => {
    btn.addEventListener('click', function() {
        // Redirect to productline.php with a category param that matches the menu code
        const category = btn.getAttribute('data-category');
        window.location.href = 'productline.php?category=' + encodeURIComponent(category);
    });
});

/* DARK MODE */
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

</body>
</html>

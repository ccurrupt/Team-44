<?php
// checks login status
session_start();

//checks if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['first_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EveryWear - About Us</title>
    <link rel="stylesheet" href="style.css">
    <style>
        
        .right-default .welcome-msg {
            padding: 6px 12px;
            background: #4a78ff;
            color: white;
            border-radius: 4px;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .right-default .logout-btn {
            padding: 6px 12px;
            background: #e35f26;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .right-default .logout-btn:hover {
            background: #d44a15;
        }
        
        .hidden {
            display: none !important;
        }
        
        .search-bar-overlay {
            display: none;
        }
        
        .search-bar-overlay.active {
            display: flex;
        }
        
        .search-close {
            display: none;
        }
        
        /* Hero section for About Us */
        .about-hero {
            padding: 80px 20px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .about-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .about-content h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .about-content h2 {
            font-size: 24px;
            margin-bottom: 30px;
            font-weight: normal;
        }
        
        .about-content p {
            font-size: 18px;
            line-height: 1.6;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .about-hero {
                padding: 40px 15px;
            }
            
            .about-content h1 {
                font-size: 36px;
            }
            
            .about-content h2 {
                font-size: 20px;
            }
            
            .right-default .welcome-msg,
            .right-default .logout-btn {
                padding: 4px 8px;
                font-size: 14px;
                margin-right: 5px;
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
                <img src="logo.png" loading="eager" alt="EveryWear Logo" width="120" height="90" class="site-logo">
            </a>
        </div>

        <!-- NAV BUTTONS -->
        <div class="nav-buttons">
            <a href="about.php" class="nav-button active">About Us</a>
            <a href="products.php" class="nav-button">Products</a>
            <a href="reviews.php" class="nav-button">Reviews</a>
            <a href="orders.php" class="nav-button">Orders</a>
        </div>

        <!-- RIGHT SIDE: Login / Create Account / Basket / Search -->
        <div class="right-controls" id="rightControls">

            <!-- Default icons (visible normally) -->
            <div class="right-default" id="rightDefault">
                <?php if ($isLoggedIn): ?>
                    <!-- Show when user is logged in -->
                    <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
                    <a href="logout.php" class="logout-btn">Log Out</a>
                <?php else: ?>
                    <!-- Show when user is NOT logged in -->
                    <a href="login.php" class="login-btn">Log in</a>
                    <a href="create-account.php" class="create-btn">Create Account</a>
                <?php endif; ?>

                <a href="cart.php" class="icon-link">
                    <img src="basket.png" alt="Basket" class="nav-icon">
                </a>

                <div class="icon-link search-icon" id="searchToggle">
                    <img src="search.png" alt="Search" class="nav-icon">
                </div>
            </div>

            <!-- Search bar overlay (hidden until search opens) -->
            <div id="searchBar" class="search-bar-overlay">
                <input type="text" placeholder="Search..." id="searchInput">
            </div>

            <!-- Close icon (only visible when search is open) -->
            <div class="icon-link search-close" id="searchClose">
                <img src="images/search.png" alt="Close Search" class="nav-icon">
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT FOR ABOUT US PAGE -->
    <div class="hero about-hero">
        <div class="about-content">
            <h1>About EveryWear</h1>
            <h2>Welcome to the home of EveryWear, your go-to destination for stylish and comfortable clothing.</h2>
            <p>Where style meets ease. Modern essentials designed to feel as good as they look, for every day.</p>
        </div>
    </div>

    <!-- Optional: Add more content sections -->
    <div style="padding: 60px 20px; max-width: 1000px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
            <div>
                <h3>Our Mission</h3>
                <p>To provide high-quality, fashionable clothing that combines style with comfort, making EveryWear the preferred choice for everyday fashion.</p>
            </div>
            <div>
                <h3>Our Values</h3>
                <p>Quality, customer satisfaction, innovation, and sustainability are at the core of everything we do.</p>
            </div>
            <div>
                <h3>Our Promise</h3>
                <p>We guarantee the quality of our products and offer excellent customer service with easy returns and exchanges.</p>
            </div>
        </div>
    </div>

    <script>
    const searchToggle = document.getElementById("searchToggle");
    const searchClose = document.getElementById("searchClose");
    const searchBar = document.getElementById("searchBar");
    const rightDefault = document.getElementById("rightDefault");
    const searchInput = document.getElementById("searchInput");

    function openSearch() {
        rightDefault.classList.add("hidden");
        searchBar.classList.add("active");
        searchInput.focus();
    }

    function closeSearch() {
        searchBar.classList.remove("active");
        rightDefault.classList.remove("hidden");
        searchInput.value = "";
    }

    searchToggle.addEventListener("click", () => {
        if (searchBar.classList.contains("active")) {
            closeSearch();
        } else {
            openSearch();
        }
    });

    searchClose.addEventListener("click", closeSearch);

    // Search function
    searchInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            const searchTerm = this.value.trim();
            if (searchTerm) {
                // Redirect to search page with query
                window.location.href = "search.php?q=" + encodeURIComponent(searchTerm);
            }
        }
    });

    // Close search when clicking outside
    document.addEventListener("click", function(e) {
        if (searchBar.classList.contains("active") && 
            !searchBar.contains(e.target) && 
            e.target !== searchToggle) {
            closeSearch();
        }
    });

    // Escape key to close search
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape" && searchBar.classList.contains("active")) {
            closeSearch();
        }
    });
    </script>
</body>
</html>
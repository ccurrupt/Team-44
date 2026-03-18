<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EveryWear - About Us</title>
	<link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        /* Main content styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: white;
            color: black;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

       

        /* Main content */
        .hero.about-hero {
            flex: 1;
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .about-content {
            max-width: 1000px;
            margin: 0 auto;
        }

        .about-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #333;
        }

        .about-content h2 {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 40px;
            font-weight: normal;
        }

        .about-section {
            margin-bottom: 40px;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .about-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .about-section p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .about-section ul {
            padding-left: 20px;
            margin: 15px 0;
        }

        .about-section li {
            margin-bottom: 10px;
            color: #555;
        }

        .team-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 30px;
        }

        .team-column {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .team-column h4 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.2rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .team-list {
            list-style: none;
            padding: 0;
        }

        .team-list li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            color: #333;
            font-weight: 500;
        }

        .team-list li:last-child {
            border-bottom: none;
        }

        .student-id {
            float: right;
            color: #666;
            font-size: 0.9em;
            font-style: italic;
            font-weight: normal;
        }

        /* FOOTER STYLES - Matching products(1).php DARK THEME */
        footer {
            background: #111827;
            color: #ffffff;
            padding: 3rem 1.5rem 2rem;
            margin-top: 3rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2.5rem;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            font-size: 1rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #ffffff;
        }

        .footer-col p {
            font-size: 0.9rem;
            color: #d1d5db;
            margin-bottom: 15px;
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
            line-height: 1.9;
        }

        .footer-col ul li a {
            color: #d1d5db;
            font-size: 0.9rem;
            text-decoration: none;
            transition: 0.2s;
        }

        .footer-col ul li a:hover {
            color: #ffffff;
        }

        .footer-socials {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.8rem;
        }

        .footer-socials i {
            font-size: 1.35rem;
            color: #f3f4f6;
            background: #1f2937;
            padding: 0.6rem;
            border-radius: 50%;
            transition: 0.2s ease;
            cursor: pointer;
        }

        .footer-socials i:hover {
            background: #f3f4f6;
            color: #111827;
            transform: translateY(-2px);
        }

        .footer-col-right {
            text-align: right;
        }

        .footer-col-right .footer-socials {
            justify-content: flex-end;
        }

        .footer-app {
            margin-top: 1.4rem;
        }

        .footer-app h5 {
            font-size: 0.95rem;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #ffffff;
        }

        .store-badges {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .store-badges a {
            display: block;
        }

        .store-badges img {
            width: 150px;
            height: auto;
            display: block;
            border-radius: 0.35rem;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            margin-top: 2.4rem;
            padding-top: 1.6rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.85rem;
            color: #d1d5db;
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
            
            .footer-col {
                text-align: left;
            }
            
            .footer-col-right {
                text-align: left;
            }
        }

        @media (max-width: 768px) {
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
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .hero.about-hero {
                padding: 30px 15px;
            }
            
            .about-section {
                padding: 20px;
            }
            
            .team-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }
            
            .footer-col-right {
                text-align: center;
            }
            
            .store-badges {
                align-items: center;
            }
            
            .store-badges img {
                width: 160px;
            }
            
            .footer-socials {
                justify-content: center;
            }
            
            .footer-col-right .footer-socials {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .about-content h1 {
                font-size: 2rem;
            }
            
            .about-content h2 {
                font-size: 1rem;
            }
            
            .about-section h3 {
                font-size: 1.3rem;
            }
            
            .footer-container {
                gap: 25px;
            }
            
            .store-badges img {
                width: 140px;
            }
        }

        /* Back to top button */
        #backToTop {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: none;
            background: black;
            color: white;
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.35);
            opacity: 0;
            transform: translateY(8px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
            z-index: 40;
        }

        #backToTop.is-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        #backToTop:hover {
            background: #111827;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- TOP NAVIGATION -->
    <div class="navbar">

        <!-- LOGO -->
        <div class="logo-section">
            <a href="EveryWear Homepage.php" class="logo-link" aria-label="Go to homepage">
                <img src="images/logo.png" loading="eager" alt="EveryWear Logo" width="120" height="90" class="site-logo">
            </a>
        </div>

        <!-- NAV BUTTONS -->
        <div class="nav-buttons">
   			 <a href="index.php" class="nav-button">Home</a>
   			 <a href="about.php" class="nav-button active">About Us</a>
  			 <a href="products.php" class="nav-button">Products</a>
  			 <a href="reviews.php" class="nav-button">Reviews</a>
  			 <a href="orders.php" class="nav-button">Orders</a>
		</div>

        <!-- RIGHT SIDE: Login / Create Account / Basket / Search -->
        <div class="right-controls" id="rightControls">


            <!-- Default icons (visible normally) -->
            <div class="right-default" id="rightDefault">
                <a href="login.php" class="login-btn">Log in</a>
                <a href="create-account.php" class="create-btn">Create Account</a>

                <a href="product-detail.php#open-cart.php" class="icon-link">
                    <img src="basket.png" alt="Basket" class="nav-icon">
                </a>

                <div class="icon-link search-icon" id="searchToggle">
                    <img src="search.png" alt="Search" class="nav-icon">
                </div>
            </div>

            <!-- Search bar overlay (hidden until search opens) -->
            <div id="searchBar" class="search-bar-overlay">
                <input type="text" placeholder="Search...">
            </div>

            <!-- Close icon (only visible when search is open) -->
            <div class="icon-link search-close" id="searchClose">
                <img src="search.png" alt="Close Search" class="nav-icon">
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT FOR ABOUT US PAGE -->
    <div class="hero about-hero">
        <div class="about-content">
            <h1>About EveryWear</h1>
            <h2>Welcome to the home of EveryWear, your go-to destination for stylish and comfortable clothing.</h2>
            
            <div class="about-section">
                <h3>Our Mission</h3>
                <p>To provide high-quality, affordable fashion that fits every body and every lifestyle. We believe great style should be accessible to everyone.</p>
            </div>
    
            <div class="about-section">
                <h3>What We Stand For</h3>
                <ul>
                <li><strong>Quality:</strong><small> Durable materials and thoughtful craftsmanship</small></li>
                <li><strong>Affordability:</strong><small> Premium style without the premium price tag</small></li>
                <li><strong>Sustainability:</strong><small> Ethically sourced materials and responsible production</small></li>
                <li><strong>Inclusivity:</strong><small> Styles for all genders, sizes, and ages</small></li>
                </ul>
            </div>
    
            <div class="about-section">
                <h3>Our Story</h3>
                <p>Founded in 2025, EveryWear started with a simple idea: create versatile clothing that works for any occasion. From casual weekends to professional settings, our collections are designed to mix, match, and maximize your wardrobe.</p>
            </div>
    
            <div class="about-section">
                <h3>Why Choose EveryWear?</h3>
                <p>With free shipping on all orders in the UK, easy returns, and dedicated customer support, shopping with us is as comfortable as wearing our clothes. Join thousands of satisfied customers who make EveryWear their fashion destination.</p>
            </div>

            <div class="about-section">
                <h3>Join the EveryWear Community</h3>
                <p>Follow us on social media to stay updated on the latest collections, exclusive offers, and style tips.</p> 
            </div>

            <div class="about-section">
                <h3>Meet Our Team</h3>
                <p>Behind EveryWear is a passionate team of designers, marketers, and customer service professionals dedicated
                to bringing you the best shopping experience possible.</p>
                
                <div class="team-container">
                    <div class="team-column">
                        <h4>Frontend Designers</h4>
                        <ul class="team-list">
                            <li>Shihad Hussain<span class="student-id"> 240133588</span></li>
                            <li>Jaimin Nish<span class="student-id"> 240389923</span></li>
                            <li>Marayam Khan Yaqoob<span class="student-id"> 240153760</span></li>
                            <li>Sukanya Badoghu<span class="student-id"> 240324810</span></li>
                        </ul>
                    </div>
            
                    <div class="team-column">
                        <h4>Backend Designers</h4>
                        <ul class="team-list">
                            <li>Omarion Cohen<span class="student-id"> 230112438</span></li>
                
                            <li>Ammar Salem<span class="student-id"> 230145090</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="about-section">
                <h3>Contact Us</h3>
                <p>If you have any questions, feedback or issues to report, feel free to reach out via our <a href="Contact Us.php">Contact Us</a> page. We'd love to hear from you!</p>
            </div>
        </div>
    </div>

    <!-- FOOTER - DARK THEME matching products(1).php -->
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h4>Shop</h4>
                <ul>
                    <li><a href="products.php?category=Tops">Tops</a></li>
                    <li><a href="products.php?category=Bottoms">Bottoms</a></li>
                    <li><a href="products.php?category=Outerwear">Outerwear</a></li>
                    <li><a href="products.php?category=Footwear">Footwear</a></li>
                    <li><a href="products.php?category=Accessories">Accessories</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Customer Service</h4>
                <ul>
                    <li><a href="products.php">Delivery &amp; Returns</a></li>
                    <li><a href="login.php">10% Student Discount</a></li>
                    <li><a href="Contact Us.php">FAQs</a></li>
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
            © 2025 EveryWear. All rights reserved.
        </div>
    </footer>

    <!-- Back to top button -->
    <button id="backToTop" aria-label="Back to top">↑</button>

    <script>
    const searchToggle = document.getElementById("searchToggle");
    const searchClose = document.getElementById("searchClose");
    const searchBar = document.getElementById("searchBar");
    const rightDefault = document.getElementById("rightDefault");
    const backToTopBtn = document.getElementById("backToTop");

    function openSearch() {
        rightDefault.classList.add("hidden");
        searchBar.classList.add("active");
    }

    function closeSearch() {
        searchBar.classList.remove("active");
        rightDefault.classList.remove("hidden");
    }

    if (searchToggle) {
        searchToggle.addEventListener("click", () => {
            if (searchBar.classList.contains("active")) {
                closeSearch();
            } else {
                openSearch();
            }
        });
    }

    if (searchClose) {
        searchClose.addEventListener("click", closeSearch);
    }

    // Back to top functionality
    window.addEventListener("scroll", function () {
        if (window.scrollY > 350) {
            backToTopBtn.classList.add("is-visible");
        } else {
            backToTopBtn.classList.remove("is-visible");
        }
    });

    backToTopBtn.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
    </script>
</body>
</html>

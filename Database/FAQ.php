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
    <title>EveryWear - FAQ</title>
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
        .faq-hero {
            max-width: 900px;
            margin: 10px auto 10px;
            text-align: center;
            padding: 10px 5px;
            background: linear-gradient(135deg, #eef3ff, #ffffff);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .faq-hero h1 {
            font-size: 40px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 5px;
        }

        .faq-hero p {
            font-size: 18px;
            color: #6b7280;
        }

        /* SEARCH */
        #faq-search {
            width: 100%;
            max-width: 600px;
            padding: 10px 18px;
            margin: 10px auto 15px;
            display: block;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            font-size: 16px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }

        #faq-search:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        /* CONTAINER */
        .faq-container {
            max-width: 900px;
            margin: 0 auto 20px;
            padding: 0 20px;
        }

        /* FAQ CARD */
        .faq-item {
            background: #ffffff;
            border-radius: 14px;
            margin-bottom: 14px;
            box-shadow: 0 8px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.25s ease;
            border: 1px solid #f1f1f1;
        }

        .faq-item:hover {
            transform: translateY(-3px);
        }

        /* QUESTION */
        .faq-question {
            padding: 10px 10px;
            font-weight: 600;
            font-size: 17px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question::after {
            content: '+';
            font-size: 20px;
            color: #3b82f6;
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-question::after {
            content: '−';
        }

        /* ANSWER */
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            padding: 0 10px;
            font-size: 15px;
            line-height: 1.7;
            color: #555;
            transition: all 0.3s ease;
        }

        .faq-item.active .faq-answer {
            padding-bottom: 90px;
        }

        .faq-answer p {
            margin: 15px 0;
        }

        /* LISTS */
        .faq-answer ul {
            padding-left: 18px;
        }

        .faq-answer li {
            margin-bottom: 8px;
        }

        .faq-item {
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(15px);}
            to {opacity: 1; transform: translateY(0);}
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

<!-- FAQ HERO SECTION -->
<div class="faq-hero">
    <h1>Frequently Asked Questions</h1>
    <p>Find answers to our most common questions below.</p>
</div>

<!-- SEARCH BAR -->
<input type="text" id="faq-search" placeholder="Search FAQs...">

<!-- FAQ ITEMS -->
<div class="faq-container">
    <div class="faq-item">
        <div class="faq-question">What types of products do you offer?</div>
        <div class="faq-answer">
            <p>We offer a wide range of unisex clothing, footwear, and accessories designed for style, comfort, and sustainability:</p>
            <ul>
                <li><strong>Tops:</strong> Boxy Logo T-Shirt, Oversized Poplin Shirt, Ribbed Knit Jumper, Racer Rib Vest</li>
                <li><strong>Outerwear:</strong> Heavyweight Logo Hoodie, Short Puffer Jacket, Cropped Denim Jacket, Waterproof Shell Jacket, Soft Knit Cardigan</li>
                <li><strong>Bottoms:</strong> Two-Toned Unisex Panel Jeans, Sand Fleece Joggers, Light Wash Baggy Shorts, Light-wash Denim Jorts</li>
                <li><strong>Footwear:</strong> Chunky Platform Crocs, Suede Ankle Boots, Minimal Strappy Sandals</li>
                <li><strong>Accessories:</strong> Simple Black Cap, Brushed Scarf, Minimal Cord Bracelet Set, Winter Gloves</li>
            </ul>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">How do your products fit?</div>
        <div class="faq-answer">
            <p>Our products are designed with both comfort and versatility in mind, making them easy to wear across different styles and occasions. Most pieces are unisex and true to size, while some feature relaxed or oversized fits that are perfect for layering. The Boxy Logo T-Shirt is soft and lightweight, ideal for all-day wear, while the Sand Fleece Joggers offer a cozy, relaxed feel that works perfectly for lounging or casual outings. For a more styled look, the Cropped Denim Jacket and Two-Toned Jeans provide modern, flattering fits that transition effortlessly between streetwear and everyday wear.</p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">Are your products sustainable?</div>
        <div class="faq-answer">
            <p>Yes! Sustainability is a core part of our brand. Many of our products are crafted using recycled fibers, organic cotton, or eco-conscious materials without compromising quality or style.</p>
            <ul>
                <li><strong>Boxy Logo T-Shirt:</strong> Made from high-quality cotton and recycled components</li>
                <li><strong>Heavyweight Logo Hoodie:</strong> 80% cotton, 20% recycled polyester</li>
                <li><strong>Cropped Denim Jacket:</strong> 85% organic cotton, 15% recycled polyester</li>
                <li><strong>Winter Gloves:</strong> Blend of recycled polyester, organic cotton, and recycled wool</li>
            </ul>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">How do I contact customer service?</div>
        <div class="faq-answer">
            <p>You can easily get in touch with us through our <a href="contact.php">Contact Us</a> page, where you can submit any questions, concerns, or feedback directly to our team. Alternatively, you can email us at support@everywear.com for assistance, and a member of our support team will respond as soon as possible to help resolve your inquiry and ensure you have the best experience with us.</p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">How do I care for my clothes?</div>
        <div class="faq-answer">
            <p>We recommend following the care instructions:</p>
            <ul>
                <li>Wash similar colors together</li>
                <li>Machine wash cold</li>
                <li>Avoid harsh chemicals</li>
                <li>Air dry or tumble dry low</li>
            </ul>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">Can I track my order?</div>
        <div class="faq-answer">
            <p>Yes, every order includes tracking information sent via email once your order is shipped.</p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">How long does delivery take?</div>
        <div class="faq-answer">
            <p>Standard delivery usually takes between 3 and 5 business days, depending on your location and the shipping option selected at checkout. All orders come with full tracking, so you can monitor your package from the moment it leaves our warehouse until it arrives at your door.</p>
        </div>
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
                <li><a href="contact.php">Delivery &amp; Returns</a></li>
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
// FAQ accordion
const faqItems = document.querySelectorAll('.faq-item');

faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');

    question.addEventListener('click', () => {
        faqItems.forEach(i => {
            if (i !== item) {
                i.classList.remove('active');
                i.querySelector('.faq-answer').style.maxHeight = '0';
                i.querySelector('.faq-answer').style.paddingTop = '0';
                i.querySelector('.faq-answer').style.paddingBottom = '0';
            }
        });

        item.classList.toggle('active');

        if (item.classList.contains('active')) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
        } else {
            answer.style.maxHeight = '0';
            answer.style.paddingTop = '0';
            answer.style.paddingBottom = '0';
        }
    });
});

// FAQ search
const searchInput = document.getElementById('faq-search');
searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase();
    faqItems.forEach(item => {
        const questionText = item.querySelector('.faq-question').textContent.toLowerCase();
        const answerText = item.querySelector('.faq-answer').textContent.toLowerCase();
        item.style.display = (questionText.includes(query) || answerText.includes(query)) ? 'block' : 'none';
    });
});

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

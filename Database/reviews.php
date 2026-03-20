<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartCount = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EveryWear - Reviews</title>

    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>

    <style>
/* REVIEW FORM SECTION */
.review-form-container {
    width: 100%;
    max-width: 800px;
    text-align: left;
    margin-top: 10px;
}

.review-form-container h2 {
    text-align: center;
    font-size: 26px;
    color: #333;
    margin-bottom: 20px;
}

.review-form-container label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    font-size: 14px;
    color: #333;
}

.review-form-container input[type="text"],
.review-form-container input[type="number"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-top: 5px;
    font-size: 14px;
}

.review-form-container textarea {
    width: 100%;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-top: 5px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
}

.btn {
    margin-top: 20px;
    padding: 14px;
    width: 100%;
    background: black;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
}

.btn:hover {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: #000;
    transform: translateY(-1px) scale(1.02);
}

/* REVIEWS LIST */
.reviews-list-container {
    width: 100%;
    max-width: 800px;
    margin-top: 40px;
    text-align: left;
}

.reviews-list-container h2 {
    text-align: center;
    font-size: 22px;
    color: #333;
    margin-bottom: 16px;
}

.review-card {
    border-top: 1px solid #eee;
    padding: 16px 0;
}

.review-card:first-child {
    border-top: none;
}

.review-header {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.review-author {
    font-weight: 700;
    font-size: 15px;
    color: #222;
}

.review-stars {
    color: #f5b50a;
    font-size: 16px;
    letter-spacing: 1px;
}

.review-text {
    margin-top: 6px;
    font-size: 14px;
    line-height: 1.6;
    color: #444;
}

.helpful-btn {
    margin-top: 10px;
    padding: 8px 18px;
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.2s ease, transform 0.2s ease;
    width: auto;
}

.helpful-btn:hover {
    background: #e0e0e0;
    transform: scale(1.03);
}

/* DARK MODE TOGGLE BUTTON */
.theme-toggle-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  padding: 4px 6px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.25s ease;
}
.theme-toggle-btn:hover { transform: scale(1.15); }
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
            <a href="reviews.php" class="nav-button active">Reviews</a>
            <a href="contact.php" class="nav-button">Contact Us</a>
        </div>

        <!-- RIGHT SIDE (matches productline.php) -->
        <div class="right-controls" id="rightControls">
            <div class="right-default" id="rightDefault">
                <?php if ($isLoggedIn): ?>
                    <span class="welcome-msg">Hi <?php echo htmlspecialchars($userName); ?>!</span>
                    <a href="logout.php" class="login-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Log in</a>
                    <a href="create-account.php" class="create-btn">
                        <img src="images/account.png" alt="" class="btn-icon">
                        Create Account
                    </a>
                <?php endif; ?>

                <a href="cart.php" class="icon-link" aria-label="Open basket">
                    <img src="images/basket.png" alt="Basket" class="nav-icon">
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                <!-- DARK MODE TOGGLE -->
                <button type="button" id="themeToggle" class="theme-toggle-btn" aria-label="Toggle dark mode">
                    <span id="themeIcon">&#127769;</span>
                </button>
            </div>
        </div>
    </div> <!-- End of NAVBAR -->

    <!-- MAIN CONTENT -->
    <div class="hero">

        <!-- REVIEW FORM -->
        <div class="review-form-container">
            <h2>Leave a Review</h2>
            <form id="reviewForm">
                <label for="name">Your Name</label>
                <input type="text" id="name"
                       placeholder="<?php echo $isLoggedIn ? htmlspecialchars($userName) : 'John Doe'; ?>"
                       value="<?php echo $isLoggedIn ? htmlspecialchars($userName) : ''; ?>"
                       required>

                <label for="rating">Rating (1-5)</label>
                <input type="number" id="rating" min="1" max="5" placeholder="5" required>

                <label for="review">Your Review</label>
                <textarea id="review" rows="4" placeholder="Write your review here..." required></textarea>

                <button type="submit" class="btn">Submit Review</button>
            </form>
        </div>

        <!-- REVIEWS LIST -->
        <div class="reviews-list-container">
            <h2>Customer Reviews</h2>
            <div id="reviewsList">
                <!-- Reviews rendered by JS -->
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
    /* DARK MODE (single instance - matches productline.php) */
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon   = document.getElementById("themeIcon");

    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
        themeIcon.innerHTML = "&#9728;&#65039;";
    }

    themeToggle.addEventListener("click", function(e) {
        e.stopPropagation();
        document.body.classList.toggle("dark");
        if (document.body.classList.contains("dark")) {
            localStorage.setItem("theme", "dark");
            themeIcon.innerHTML = "&#9728;&#65039;";
        } else {
            localStorage.setItem("theme", "light");
            themeIcon.innerHTML = "&#127769;";
        }
    });

    /* REVIEWS */
    let reviews = [
        { name: "klbc1997", rating: 5, text: "I recently got the Sand Fleece Joggers and I am so impressed! They're super soft, cozy, and the fit is just right. Perfect for lounging at home or casual outings. The color is exactly as shown and they've held up well after multiple washes. Highly recommend!", helpful: 22 },
        { name: "Sara1234", rating: 3, text: "I bought the Lightwash Denim Jorts hoping they would fit perfectly, but the sizing was a bit off and the fabric felt stiff at first. After a few washes they softened up nicely, and they do look great with casual outfits. Not perfect, but still wearable.", helpful: 5 },
        { name: "kristinat153_6573", rating: 5, text: "Absolutely love the Boxy Logo T-Shirt! The fit is perfect, the fabric is soft, and it holds up well after multiple washes. Stylish and comfortable - definitely one of my favorite pieces!", helpful: 25 },
        { name: "NITA1111", rating: 5, text: "These Suede Ankle Boots are amazing! They're stylish, comfortable, and easy to pair with multiple outfits. The suede feels soft, and they fit true to size. I've received so many compliments already!", helpful: 0 },
        { name: "marlaaesthetics_3502", rating: 4, text: "The Brushed Scarf is cozy, soft, and perfect for chilly days. It pairs well with multiple outfits and adds a stylish touch to any look. The color is true to the pictures and the quality is great for the price.", helpful: 17 }
    ];

    function renderReviews() {
        const reviewsList = document.getElementById("reviewsList");
        reviewsList.innerHTML = "";

        if (reviews.length === 0) {
            reviewsList.innerHTML = '<p style="color: #999; text-align:center;">No reviews yet. Be the first to leave a review!</p>';
            return;
        }

        reviews.forEach((r, index) => {
            const card = document.createElement("div");
            card.className = "review-card";

            const safeName = document.createElement("span");
            safeName.textContent = r.name;

            const safeText = document.createElement("span");
            safeText.textContent = r.text;

            card.innerHTML = `
                <div class="review-header">
                    <span class="review-author">${safeName.innerHTML}</span>
                    <span class="review-stars">${"★".repeat(r.rating)}${"☆".repeat(5 - r.rating)}</span>
                </div>
                <p class="review-text">${safeText.innerHTML}</p>
                <button class="helpful-btn" onclick="markHelpful(${index})">
                    👍 Helpful (${r.helpful})
                </button>
            `;
            reviewsList.appendChild(card);
        });
    }

    function markHelpful(index) {
        reviews[index].helpful++;
        renderReviews();
    }

    document.getElementById("reviewForm").addEventListener("submit", function(e) {
        e.preventDefault();

        const name   = document.getElementById("name").value.trim();
        const rating = parseInt(document.getElementById("rating").value);
        const text   = document.getElementById("review").value.trim();

        if (!name || !rating || !text) return;
        if (rating < 1 || rating > 5) {
            alert("Rating must be between 1 and 5.");
            return;
        }

        reviews.unshift({ name, rating, text, helpful: 0 });
        renderReviews();
        this.reset();

        document.getElementById("reviewsList").scrollIntoView({ behavior: "smooth" });
    });

    renderReviews();
    </script>

</body>
</html>

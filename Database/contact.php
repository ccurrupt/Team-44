<?php
// contact.php

require_once 'dbconfig.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartCount = count($_SESSION['cart']);

// ── Handle form submission ──
$formSuccess = false;
$formErrors  = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit'])) {
    // Validate first, encode for output only — store clean data in DB
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name))    $formErrors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                         $formErrors[] = "A valid email is required";
    if (empty($subject)) $formErrors[] = "Subject is required";
    if (empty($message)) $formErrors[] = "Message is required";

    if (empty($formErrors)) {
        $stmt = $pdo->prepare("
            INSERT INTO contact_messages (name, email, subject, message)
            VALUES (:name, :email, :subject, :message)
        ");
        $stmt->execute([
            ':name'    => $name,
            ':email'   => $email,
            ':subject' => $subject,
            ':message' => $message,
        ]);
        $formSuccess = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us – EveryWear</title>

    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>

    <style>
/* ─── CONTACT FORM ─── */
.contact-wrapper {
    max-width: 520px;
    margin: 40px auto;
    padding: 36px 32px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.contact-wrapper h1 {
    text-align: center;
    font-size: 28px;
    color: #333;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.contact-wrapper .subtitle {
    text-align: center;
    color: #888;
    font-size: 14px;
    margin-bottom: 30px;
}

.contact-wrapper label {
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    color: #333;
}

.contact-wrapper input,
.contact-wrapper textarea {
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    border: 1px solid #d9d9d9;
    margin-bottom: 20px;
    font-size: 15px;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.2s ease;
}

.contact-wrapper input:focus,
.contact-wrapper textarea:focus {
    border-color: #4b74ff;
    outline: none;
}

.contact-wrapper textarea {
    height: 140px;
    resize: vertical;
}

.submit-btn {
    width: 100%;
    background: black;
    color: white;
    padding: 15px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    margin-top: 6px;
    transition: background 0.25s ease, transform 0.25s ease;
}

.submit-btn:hover {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: #000;
    transform: translateY(-1px) scale(1.02);
}

/* ─── ALERTS ─── */
.alert {
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.5;
}

.alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert-error ul {
    margin: 6px 0 0 18px;
    padding: 0;
}

/* ─── WELCOME MSG / CART BADGE ─── */
.welcome-msg {
    color: #0066cc;
    font-weight: bold;
    margin-right: 10px;
    font-size: 14px;
}

.icon-link {
    position: relative;
    text-decoration: none;
    color: #333;
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

/* ─── BACK TO TOP ─── */
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

#backToTop:hover { background: #111827; }

/* ─── DARK MODE TOGGLE BUTTON ─── */
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

@media (max-width: 520px) {
    .contact-wrapper { margin: 20px 12px; padding: 24px 18px; }
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
            <a href="contact.php" class="nav-button active">Contact Us</a>
        </div>

        <!-- RIGHT SIDE -->
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

                <a href="cart.php" class="icon-link">
                    <img src="images/basket.png" alt="Basket" class="nav-icon">
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                <!-- DARK MODE TOGGLE (emoji-based, matching productline.php) -->
                <button type="button" id="themeToggle" class="theme-toggle-btn" aria-label="Toggle dark mode">
                    <span id="themeIcon">&#127769;</span>
                </button>
            </div>
        </div>
    </div> <!-- End of NAVBAR -->

    <!-- ── CONTACT FORM ── -->
    <div class="contact-wrapper">

        <h1>Contact Us</h1>
        <p class="subtitle">Have a question or feedback? We'd love to hear from you.</p>

        <?php if ($formSuccess): ?>
            <div class="alert alert-success">
                ✅ <strong>Message sent!</strong> We'll get back to you as soon as possible.
            </div>
        <?php endif; ?>

        <?php if (!empty($formErrors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($formErrors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="contact.php">
            <label for="contact-name">Your Name</label>
            <input type="text" id="contact-name" name="name"
                   placeholder="Enter your full name"
                   value="<?php echo $isLoggedIn ? htmlspecialchars($userName) : ''; ?>"
                   required>

            <label for="contact-email">Email Address</label>
            <input type="email" id="contact-email" name="email"
                   placeholder="Enter your email address" required>

            <label for="contact-subject">Subject</label>
            <input type="text" id="contact-subject" name="subject"
                   placeholder="Enter the subject" required>

            <label for="contact-message">Message</label>
            <textarea id="contact-message" name="message"
                      placeholder="Type your message here..." required></textarea>

            <button type="submit" name="submit" class="submit-btn">Send Message</button>
        </form>
    </div>

    <!-- ── FOOTER (matching the rest of the site) ── -->
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

    <!-- Back to top -->
    <button id="backToTop" aria-label="Back to top">↑</button>

    <script>
    // ── Back to top ──
    const backToTopBtn = document.getElementById("backToTop");

    window.addEventListener("scroll", function() {
        if (window.scrollY > 350) {
            backToTopBtn.classList.add("is-visible");
        } else {
            backToTopBtn.classList.remove("is-visible");
        }
    });

    backToTopBtn.addEventListener("click", function() {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    // ── Auto-hide success message after 5 seconds ──
    const successAlert = document.querySelector(".alert-success");
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = "opacity 0.5s ease";
            successAlert.style.opacity = "0";
            setTimeout(() => successAlert.remove(), 500);
        }, 5000);
    }

    // ── DARK MODE (emoji-based, matching productline.php) ──
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
    </script>

</body>
</html>

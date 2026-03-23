<?php
require_once 'dbconfig.php'; // includes session_start() and $pdo

// ── Auth ──
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = '';
if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] ?? 'User';
    if (!empty($_SESSION['last_name'])) {
        $userName .= ' ' . $_SESSION['last_name'];
    }
}

// ── Ensure cart exists ──
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── Handle Add to Cart via POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = intval($_POST['product_id']);
    $size       = $_POST['size'] ?? 'M';

    // Look up product in DB
    $stmt = $pdo->prepare("SELECT product_id, name, price, image_main FROM Products WHERE product_id = :pid");
    $stmt->execute([':pid' => $product_id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        $found = false;
        foreach ($_SESSION['cart'] as $index => $item) {
            $itemId = $item['product_id'] ?? $item['id'] ?? null;
            if ($itemId == $product_id && ($item['size'] ?? '') === $size) {
                $_SESSION['cart'][$index]['quantity']++;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => (int)$prod['product_id'],
                'name'       => $prod['name'],
                'price'      => (float)$prod['price'],
                'image'      => $prod['image_main'] ?? 'images/placeholder.jpg',
                'size'       => $size,
                'quantity'   => 1,
            ];
        }
    }

    // Redirect back to same product page
    header('Location: productdescrip.php?id=' . $product_id . '&added=1');
    exit();
}

// ── Fetch product by ID ──
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM Products WHERE product_id = :pid");
    $stmt->execute([':pid' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (empty($product)) {
    echo "<p style='text-align:center;margin-top:60px;font-size:18px;'>Product not found!</p>";
    echo "<p style='text-align:center;'><a href='productline.php'>← Back to Products</a></p>";
    exit;
}

// Cart count for badge
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += (int)($item['quantity'] ?? 1);
}

$justAdded = isset($_GET['added']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - EveryWear</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>

    <style>
        body {
            margin: 0; padding: 0;
            font-family: "Inter", Arial, sans-serif;
            background-color: #f7f8fa;
            color: #333;
            min-height: 100vh;
        }
        *, *::before, *::after { box-sizing: border-box; }

        .product-container {
            max-width: 1300px;
            margin: 60px auto;
            padding: 0 20px;
            display: flex;
            gap: 60px;
            align-items: flex-start;
        }

        .image-container {
            width: 50%;
            position: relative;
        }

        #mainImage {
            width: 100%;
            height: 520px;
            border-radius: 12px;
            object-fit: cover;
            background: #f3f3f3;
        }

        .thumb-row {
            margin-top: 20px;
            display: flex;
            gap: 16px;
        }

        .thumb-row img {
            width: 110px;
            height: 110px;
            border-radius: 10px;
            object-fit: cover;
            cursor: pointer;
            transition: 0.2s;
        }

        .thumb-row img:hover {
            transform: scale(1.05);
            border: 2px solid #111;
        }

        #zoomLens {
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 2px solid #aaa;
            background-size: 220%;
            background-repeat: no-repeat;
            display: none;
            pointer-events: none;
        }

        .right-info { width: 48%; }

        .product-title { font-size: 34px; font-weight: 600; }
        .rating { margin: 10px 0; color: gold; font-size: 16px; }
        .price { font-size: 28px; margin: 15px 0; font-weight: 600; }
        .sizes { margin: 20px 0; }

        .size-btn {
            padding: 10px 22px;
            border: 1px solid #999;
            border-radius: 6px;
            margin-right: 10px;
            cursor: pointer;
            transition: 0.25s;
            background: white;
            display: inline-block;
        }

        .size-btn.active {
            background: #111;
            color: white;
            border-color: #111;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            border: none;
            background: #111;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
        }
        .add-to-cart-btn:hover { background: #333; }

        .added-msg {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 16px;
            text-align: center;
            font-weight: 500;
        }

        .description { margin-top: 24px; }
        .description h3 { margin-bottom: 8px; }
        .materials, .sustainability { margin-top: 10px; font-size: 14px; color: #555; }

        .welcome-msg { font-size: 14px; margin-right: 8px; }

        .cart-count-badge {
            position: absolute;
            top: -4px; right: -4px;
            background: #e35f26;
            color: white;
            border-radius: 50%;
            width: 18px; height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        @media(max-width: 900px) {
            .product-container { flex-direction: column; }
            .image-container, .right-info { width: 100%; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
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
                <span class="welcome-msg">Hi <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</span>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="login-btn">Log in</a>
                <a href="create-account.php" class="create-btn">
                    <img src="images/account.png" alt="" class="btn-icon">
                    Create Account
                </a>
            <?php endif; ?>

            <a href="cart.php" class="icon-link" style="position:relative;">
                <img src="images/basket.png" alt="Basket" class="nav-icon">
                <?php if ($cartCount > 0): ?>
                    <span class="cart-count-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>

            <div class="icon-link search-icon" id="searchToggle">
                <img src="images/search.png" alt="Search" class="nav-icon">
            </div>

            <button id="themeToggle" class="icon-link" aria-label="Toggle theme">
                <i id="themeIcon" class="ri-moon-line"></i>
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

<!-- PRODUCT DETAIL -->
<div class="product-container">
    <div class="image-container">
        <img id="mainImage" src="<?php echo htmlspecialchars($product['image_main']); ?>">
        <div id="zoomLens"></div>

        <div class="thumb-row">
            <?php
            $thumbs = ['image_1', 'image_2', 'image_3', 'image_4'];
            foreach ($thumbs as $t) {
                if (!empty($product[$t])) {
                    echo '<img src="' . htmlspecialchars($product[$t]) . '" onclick="changeImage(this)">';
                }
            }
            ?>
        </div>
    </div>

    <div class="right-info">
        <?php if ($justAdded): ?>
            <div class="added-msg">✓ Added to cart! <a href="cart.php">View Cart</a></div>
        <?php endif; ?>

        <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
        <div class="rating">★★★★★ 4.8 (112 reviews)</div>
        <div class="price">£<?php echo number_format($product['price'], 2); ?></div>

        <form method="POST" action="productdescrip.php">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">

            <div class="sizes">
                <div>Select Size:</div><br>
                <?php foreach (['S','M','L','XL'] as $s): ?>
                    <label class="size-btn" style="cursor:pointer;">
                        <input type="radio" name="size" value="<?php echo $s; ?>" style="display:none;" onchange="this.closest('.sizes').querySelectorAll('.size-btn').forEach(b=>b.classList.remove('active'));this.closest('.size-btn').classList.add('active');">
                        <?php echo $s; ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="add-to-cart-btn" id="addToCartBtn">Add to Cart</button>
        </form>

        <div class="description">
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>
        </div>

        <?php if (!empty($product['materials'])): ?>
            <div class="materials">
                <strong>Materials:</strong> <?php echo htmlspecialchars($product['materials']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($product['is_sustainable'])): ?>
            <div class="sustainability">
                <strong>Sustainable:</strong> <?php echo $product['is_sustainable'] ? 'Yes' : 'No'; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-container">
        <div class="footer-col">
            <h4>Shop</h4>
            <ul>
                <li><a href="productline.php">Tops</a></li>
                <li><a href="productline.php">Bottoms</a></li>
                <li><a href="productline.php">Outerwear</a></li>
                <li><a href="productline.php">Footwear</a></li>
                <li><a href="productline.php">Accessories</a></li>
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
                    <a href="#"><img src="images/image1.png" alt="Google Play"></a>
                    <a href="#"><img src="images/image2.png" alt="App Store"></a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">© 2025 EveryWear. All rights reserved.</div>
</footer>

<script>
/* Image zoom */
const mainImage = document.getElementById("mainImage");
const zoomLens = document.getElementById("zoomLens");

function changeImage(img) {
    mainImage.src = img.src;
    zoomLens.style.backgroundImage = `url(${img.src})`;
}

mainImage.addEventListener("mousemove", zoom);
mainImage.addEventListener("mouseenter", () => {
    zoomLens.style.display = "block";
    zoomLens.style.backgroundImage = `url(${mainImage.src})`;
});
mainImage.addEventListener("mouseleave", () => zoomLens.style.display = "none");

function zoom(e) {
    const rect = mainImage.getBoundingClientRect();
    let x = e.clientX - rect.left - zoomLens.offsetWidth / 2;
    let y = e.clientY - rect.top - zoomLens.offsetHeight / 2;
    x = Math.max(0, Math.min(x, rect.width - zoomLens.offsetWidth));
    y = Math.max(0, Math.min(y, rect.height - zoomLens.offsetHeight));
    zoomLens.style.left = x + "px";
    zoomLens.style.top = y + "px";
    zoomLens.style.backgroundPosition = `${(x / rect.width) * 100}% ${(y / rect.height) * 100}%`;
}

/* Require size selection before submit */
document.getElementById("addToCartBtn").addEventListener("click", function(e) {
    const selected = document.querySelector('input[name="size"]:checked');
    if (!selected) {
        e.preventDefault();
        alert("Please select a size.");
    }
});

/* DARK MODE */
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

<?php include 'chatbot-widget.php'; ?>
</body>
</html>

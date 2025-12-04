<?php
include 'db.php';

// Fetch first product as example
$result = $conn->query("SELECT * FROM users");
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    echo "Product not found!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Page</title>

    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
    <link href="checkout.html"/>

    <style>
    body {
        margin: 0;
        padding: 0;
        font-family: "Inter", Arial, sans-serif;
        background-color: #f7f8fa;
        color: #333;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        overflow-y: scroll; 

    }

    *, *::before, *::after { box-sizing: border-box; }

    html, body { overflow-x: hidden; }

    .navbar {
        width: 100%;
        background: white;
        height: auto;
        padding: 4px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 8px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 50;
        overflow: visible;
    }

    .navbar::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(to right, #30CDF5, #00FAA0);
    }

    .logo-section {
        display: flex;
        align-items: center;
        gap: 12px;
        height: 100%; /* vertical centering inside the fixed navbar */
        min-width: 250px; /* ensure logo has enough space but not too wide */
        max-width: 300px;
    }

    .logo-section img {
        height: 90px; /* size of everywear logo */
        width: 240px;
    }

    .brand {
        font-size: 24px;
        font-weight: 700;
        background: linear-gradient(90deg, #28d5d5, #4a99ff);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
        display: inline-block;
    }

/* NAV BUTTONS */
.nav-buttons {
    display: flex;
    gap: 20px; /* match homepage spacing */
    margin-left: 0; /* ensure nav buttons stay aligned next to logo */
    flex: 1 1 auto; /* allow nav buttons to shrink/grow */
    min-width: 0; /* allow children to shrink */
}

/* NAV BUTTONS — gradient hover + scale */
.nav-buttons a.nav-button {
    display: inline-flex;
    align-items: center;
    padding: 10px 25px;
    background: #e7e9eb;
    border-radius: 7px;
    text-decoration: none;
    color: inherit;
    font-size: 14px;
    transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
    transform: translateY(-2px);
}

.nav-buttons a.nav-button:hover,
.nav-buttons a.nav-button:focus {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: #000;
    transform: translateY(-2px) scale(1.07); /* small enlargement */
    outline: none;
}


/* Active nav link styling */
.nav-buttons a.nav-button.active {
    background-color: #31F8B3;
    color: #000; /* black text for contrast on light green */
}

/* Push the right-side controls to the far right, keeping nav buttons lefted-aligned */
.right-controls { margin-left: auto; }

/* main button style block appears below with the improved transitions */

.nav-buttons button {
    padding: 10px 25px;
    background: #e7e9eb;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
    /* keep buttons visually lifted even when not hovered */
    transform: translateY(-2px);
}

.nav-buttons a.nav-button.active {
    background-color: #4b74ff;   /* blue colour when active */
    color: #ffffff;              /* white text for contrast */
}

/* Hover state ALWAYS overrides, even on active buttons */
.nav-buttons a.nav-button:hover,
.nav-buttons a.nav-button:focus {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: #000 !important;        /* force black text on hover */
    transform: translateY(-2px) scale(1.07);
    outline: none;
}

/* RIGHT CONTROL BUTTONS */
.right-controls {
    display: flex;
    align-items: center;
    gap: 12px; /* slightly tighter spacing to bring icons closer together */
    min-width: 0; /* allow shrink */
}

/* Container holds both states (default icons + search bar) */
.right-controls {
    display: flex;
    align-items: center;
    position: relative;
}

/* DEFAULT RIGHT SIDE (login, create, basket, search) */
.right-default {
    display: flex;
    align-items: center;
    gap: 12px;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

/* SEARCH BAR INLINE VERSION */
.search-bar-inline {
    display: none;          /* hidden initially */
    align-items: center;
    gap: 10px;
    width: 260px;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.search-bar-inline.active {
    display: flex;
}

/* Replaced old .search-input (bottom of CSS) with this version */
.search-bar-inline .search-input {
    flex: 1;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}

/* Animated hide */
.hidden {
    opacity: 0;
    transform: scale(0.95);
    pointer-events: none;
}

/* ensure the nav button sections are centered with the fixed navbar */
.nav-buttons, .right-controls {
    height: 100%;
    display: flex;
    align-items: center;
}

/* LOGIN BUTTON */
.login-btn {
    background: #4b74ff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
    transform: translateY(-2px);
}

.login-btn:hover {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: #000;
    transform: translateY(-2px) scale(1.07);
}
    .login-btn:active { transform: translateY(0); }

/* CREATE ACCOUNT BUTTON */
.create-btn {
    background: black;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
    transform: translateY(-2px);
}

.create-btn:hover {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: #000;
    transform: translateY(-2px) scale(1.07);
}
    .create-btn:active { transform: translateY(0); }

.login-btn,
.create-btn {
    text-decoration: none;   /* remove underline */
}

/* ICONS */
/* ICON WRAPPER LINK */
.icon-link {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 36px;
    width: 36px;
    cursor: pointer;
    text-decoration: none;
    transition: transform 0.25s ease, opacity 0.25s ease;
}

/* PNG ICON IMAGE */
.nav-icon {
    width: 22px;     /* same visual size as emoji */
    height: 22px;
    object-fit: contain;
    pointer-events: none; /* keeps click on the link, not on the img */
}

/* HOVER EFFECT */
#searchToggle:hover {
    transform: scale(1.15);
}

.icon-link:hover {
    transform: scale(1.15);
    opacity: 0.9;
}

.search-close:hover {
    transform: translateY(-50%) scale(1.15) !important;
}

/* HERO SECTION */
.hero {
    margin: 16px auto;
    width: 95%;
    height: 500px;
    background: white;
    border-radius: 20px;
    display: flex;
    flex-direction: column;      /* stack content vertically */
    justify-content: flex-start; /* push content to the top */
    align-items: center;         /* keep buttons centered horizontally */
    padding-top: 40px;           /* adjust how far from the top */
    font-size: 26px;
    color: #999;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
    animation: fadeIn 0.6s ease;
}

/* SMOOTH FADE-IN ANIMATION */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.site-logo {
    height: 90px;
    width: auto;
    margin-left: 18px;  /* shift the logo slightly to the right */
    margin-right: 0; /* reset right margin */
}

.logo-link { display: inline-flex; align-items: center; cursor: pointer; text-decoration: none; -webkit-tap-highlight-color: transparent; }
/* Don't show a visible outline on mouse click */
.logo-link:focus { outline: none; }
/* Keep a visible outline for keyboard users (a11y) */
.logo-link:focus-visible { outline: 3px solid rgba(75,116,255,0.25); border-radius: 6px; }
.logo-link:active { outline: none; }

/* AV: Prevent collision of big logo with the nav buttons - margin handled earlier */

/* Responsive adjustments so the logo doesn't dominate small screens */
@media (max-width: 768px) {
    .site-logo, .logo-section img {
        height: 64px; /* scale down on tablets/phones */
    }
    .navbar {
        height: 64px; /* slightly smaller navbar on mobile */
    }
    .logo-section { min-width: 140px; }
}

.search-bar-inline .search-input {
    flex: 1;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.search-input:focus {
    border-color: #4b74ff;
}

/* SEARCH BAR OVERLAY */
.search-bar-overlay {
    position: absolute;
    right: 36px;  /* this guarantees the close icon stays in place and the search bar is to the left of it */
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    opacity: 0;
    pointer-events: none;
    overflow: hidden;
    transition: width 0.3s ease, opacity 0.2s ease;
}


.search-bar-overlay input {
    width: 320px;
    height: 36px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}

/* When shown */
.search-bar-overlay.active {
    width: 320px;
    opacity: 1;
    pointer-events: auto;
}

/* Close icon sits exactly where the normal search icon was */
.search-close {
    position: absolute;
    height: 36px;
    width: 36px;
    right: 0;         /* SAME horizontal anchor as the original search icon */
    top: 50%;
    transform: translateY(-50%);
    display: none;
    z-index: 60;
    align-items: center;
    justify-content: center;
}

.search-bar-overlay.active + .search-close {
    display: flex;    /* show close icon when search is active */
}

/* ABOUT US PAGE CUSTOM LAYOUT */
.about-hero {
    justify-content: flex-start;   /* content starts at the top */
    align-items: flex-start;       /* left align content */
    padding: 40px;                 /* spacing around text */
    text-align: left;              /* left-aligned text */
    height: auto;                  /* allow content to grow naturally */
}

.about-content {
    max-width: 900px;
    color: #000000;
}

.about-content h1 {
    margin-top: 0;
    text-align: left;
    font-size: 32px;
}

.about-content h2 {
    text-align: left;
    font-size: 24px;
}

.about-content p {
    text-align: left;
    margin-top: 16px;
    font-size: 17px;
    line-height: 1.6;
}

/* HERO BUTTONS */
.hero-buttons {
    display: flex;
    gap: 20px;
    justify-content: center; /* ensure perfect centering */
    width: 100%;
}

.category-btn {
    padding: 10px 40px;
    border: 3px solid black; /* thick outline */
    background: white;
    color: black;
    text-decoration: none;
    font-size: 20px;
    font-weight: 600;
    border-radius: 0px;
    transition: background 0.25s ease, color 0.25s ease;
}

/* Hover effect: invert colors */
.category-btn:hover {
    background: black;
    color: white;
}

body {
    font-family: Arial, sans-serif;
    background: #f7f7f7;
    padding: 20px;
}

.checkout-container { max-width: 800px; margin: auto; }

.box { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e3e3e3; }

.box h2 { margin-bottom: 15px; }

label { display: block; margin-top: 15px; font-weight: bold; }

input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px; }

.btn { margin-top: 20px; padding: 14px; width: 100%; background: black; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }

.summary-item, .totals p, .totals h3 { display: flex; justify-content: space-between; margin-top: 12px; }

.two-cols { display: flex; gap: 15px; }

.checkout-container {
    max-width: 800px;
    margin: auto;
}

.box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid #e3e3e3;
}

.box h2 {
    margin-bottom: 15px;
}

label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-top: 5px;
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
}

.summary-item, .totals p, .totals h3 {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
}

.two-cols {
    display: flex;
    gap: 15px;
}

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

        /* Zoom Lens */
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

        /* RIGHT INFO */
        .right-info {
            width: 48%;
        }

        .product-title {
            font-size: 34px;
            font-weight: 600;
        }

        .rating {
            margin: 10px 0;
            color: gold;
            font-size: 16px;
        }

        .price {
            font-size: 28px;
            margin: 15px 0;
            font-weight: 600;
        }

        .sizes {
            margin: 20px 0;
        }

        .size-btn {
            padding: 10px 22px;
            border: 1px solid #999;
            border-radius: 6px;
            margin-right: 10px;
            cursor: pointer;
            transition: 0.25s;
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
        }

        .add-to-cart-btn:hover {
            background: #333;
        }

        /* CART SIDEBAR */
        .cart {
        position: fixed;
        top: 0;
        right: -420px;
        width: 380px;
        height: 100%;
        background: #fff;
        padding: 20px;
        transition: 0.4s ease;
        z-index: 10000;
        display: flex;
        flex-direction: column;  /* stack items vertically */
        justify-content: space-between; /* pushes checkout button to bottom */
        box-shadow: -4px 0 12px rgba(0,0,0,0.15);
    }

        .cart.active {
            right: 0;
        }

        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between; /* makes left content and delete icon spread */
            gap: 16px;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        .cart-item img {
            width: 70px;
            border-radius: 8px;
        }

        .cart-remove {
            cursor: pointer;
            font-size: 22px;
            margin-left: auto;
            color: #444;
            transition: 0.2s;
        }
        .cart-remove:hover {
            color: red;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
        }

        .qty-btn {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 1px solid #999;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .qty-btn:hover {
            background: #ddd;
        }

        .cart-total {
            font-size: 20px;
            margin-top: 20px;
            font-weight: 600;
        }

        .btn-buy {
            width: 100%;
            padding: 14px;
            background: #111;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 16px;  /* spacing from total price */
            flex-shrink: 0; 
            text-decoration: none;
        }

        #cart-close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 32px;
    cursor: pointer;
    z-index: 1001;
    margin-top: 0;
}


        .hidden {
            display: none !important;
        }

        @media(max-width: 900px) {
            .product-container {
                flex-direction: column;
            }
            .image-container, .right-info {
                width: 100%;
            }
        }

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
    }

    .footer-col h4 {
      font-size: 1rem;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    .footer-col p {
      font-size: 0.9rem;
      color: #d1d5db;
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
    }

    .store-badges {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      align-items: flex-end;
    }

    .store-badges img {
      width: 150px;
      height: auto;
      display: block;
      border-radius: 0.35rem;
    }

    .footer-bottom {
      text-align: center;
      margin-top: 2.4rem;
      padding-top: 1.6rem;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
      font-size: 0.85rem;
      color: #d1d5db;
    }

    @media (max-width: 760px) {
      .footer-container {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 520px) {
      .footer-container {
        grid-template-columns: 1fr;
      }
      .footer-col {
        text-align: center;
      }
      .footer-socials {
        justify-content: center;
      }
      .footer-col-right {
        text-align: center;
      }
      .footer-col-right .footer-socials {
        justify-content: center;
      }
      .store-badges {
        align-items: center;
      }
    }
    
    </style>

</head>
<body>

<div class="navbar">
    <div class="logo-section">
        <a href="EveryWear Homepage.html">
            <img src="images/Logo.png" alt="Logo" width="120">
        </a>
    </div>

    <div class="nav-buttons">
        <a href="About Us.html" class="nav-button">About Us</a>
        <a href="Products.html" class="nav-button">Products</a>
        <a href="Reviews.html" class="nav-button">Reviews</a>
        <a href="Orders.html" class="nav-button">Orders</a>
    </div>

    <div class="right-controls" id="rightControls">
        <div class="right-default" id="rightDefault">
            <a href="Login.html" class="login-btn">Log in</a>

            <a href="create-account.html" class="create-btn">
                Create Account
            </a>

            <a id="cartToggle" class="icon-link">
                <img src="images/basket.png" class="nav-icon">
                <span class="cart-count-badge">0</span>
            </a>

            <div id="searchToggle" class="icon-link">
                <img src="images/search.png" class="nav-icon">
            </div>
        </div>

        <div id="searchBar" class="search-bar-overlay">
            <input type="text" placeholder="Search...">
        </div>

        <div id="searchClose" class="icon-link search-close">
            <img src="images/search.png" class="nav-icon">
        </div>
    </div>
</div>

<div id="cart" class="cart">
    <h2>Your Cart</h2>
    <div class="cart-items"></div>

    <div class="cart-total">Total: £<span id="total-price">0.00</span></div>
    <a href="checkout.html" class="btn-buy">Checkout</a>

    <i id="cart-close" class="ri-close-circle-fill"></i>
</div>


<div class="product-container">

    <div class="image-container">
        <img id="mainImage" src="<?= htmlspecialchars($product['image_main']) ?>">
        <div id="zoomLens"></div>

        <div class="thumb-row">
            <img src="<?= htmlspecialchars($product['image_1']) ?>" onclick="changeImage(this)">
            <img src="<?= htmlspecialchars($product['image_2']) ?>" onclick="changeImage(this)">
            <img src="<?= htmlspecialchars($product['image_3']) ?>" onclick="changeImage(this)">
            <img src="<?= htmlspecialchars($product['image_4']) ?>" onclick="changeImage(this)">
        </div>
    </div>

    <div class="right-info">
        <div id="productName" class="product-title"><?= htmlspecialchars($product['name']) ?></div>

        <div class="rating">★★★★★ 4.8 (112 reviews)</div>

        <div class="price">£<span id="productPrice"><?= number_format($product['price'], 2) ?></span></div>

        <div class="sizes">
            <div>Select Size:</div> <br> 
            <span class="size-btn">S</span>
            <span class="size-btn">M</span>
            <span class="size-btn">L</span>
            <span class="size-btn">XL</span>
        </div>

        <button class="add-to-cart-btn" onclick="addToCart()">Add to Cart</button>

        <div class="description">
            <h3>Description</h3>
            <p>
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>
        </div>

        <div class="materials">
            <strong>Materials:</strong> <?= htmlspecialchars($product['materials']) ?>
        </div>
        <div class="sustainability">
            <strong>Sustainable:</strong> <?= $product['is_sustainable'] ? 'Yes' : 'No' ?>
        </div>
    </div>
</div>

<footer>
    <div class="footer-container">
      <div class="footer-col">
        <h4>Shop</h4>
        <ul>
          <li><a href="#">Tops</a></li>
          <li><a href="#">Bottoms</a></li>
          <li><a href="#">Outerwear</a></li>
          <li><a href="#">Footwear</a></li>
          <li><a href="#">Accessories</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Customer Service</h4>
        <ul>
          <li><a href="#">Delivery & Returns</a></li>
          <li><a href="#">10% Student Discount</a></li>
          <li><a href="#">FAQs</a></li>
          <li><a href="#">My Account</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Join Now</h4>
        <ul>
          <li><a href="#">Become a member today and get exclusive benefits!</a></li>
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


<script>

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
    let x = e.clientX - rect.left - zoomLens.offsetWidth/2;
    let y = e.clientY - rect.top - zoomLens.offsetHeight/2;

    x = Math.max(0, Math.min(x, rect.width - zoomLens.offsetWidth));
    y = Math.max(0, Math.min(y, rect.height - zoomLens.offsetHeight));

    zoomLens.style.left = x + "px";
    zoomLens.style.top = y + "px";

    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;

    zoomLens.style.backgroundPosition = `${xPercent}% ${yPercent}%`;
}

/* ============================================================
   CART SYSTEM (from your uploaded working index.html version)
============================================================ */

const CART_KEY = "everywear_cart_v1";

function loadCart() {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
}
function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
}
function updateBadge() {
    const cart = loadCart();
    document.querySelector(".cart-count-badge").textContent = cart.length;
}

function renderCart() {
    const cart = loadCart();
    const container = document.querySelector(".cart-items");
    const totalPrice = document.getElementById("total-price");

    container.innerHTML = "";
    let total = 0;

    cart.forEach((item, index) => {
        total += item.price * item.quantity;

        container.innerHTML += `
            <div class="cart-item">
                <img src="${item.image}">
                <div>
                    <strong>${item.name}</strong><br>
                    Size: ${item.size}<br>
                    £${item.price.toFixed(2)}
                    <div class="quantity-controls">
                        <div class="qty-btn" onclick="decreaseQty(${index})">-</div>
                        <span>${item.quantity}</span>
                        <div class="qty-btn" onclick="increaseQty(${index})">+</div>
                    </div>
                </div>
                <div class="cart-remove" onclick="removeItem(${index})">
                    <i class="ri-delete-bin-line"></i>
                </div>
            </div>
        `;
    });

    totalPrice.textContent = total.toFixed(2);
    updateBadge();
}

function increaseQty(i) {
    const cart = loadCart();
    cart[i].quantity++;
    saveCart(cart);
    renderCart();
}
function decreaseQty(i) {
    const cart = loadCart();
    if (cart[i].quantity > 1) cart[i].quantity--;
    saveCart(cart);
    renderCart();
}
function removeItem(i) {
    let cart = loadCart();
    cart.splice(i, 1);
    saveCart(cart);
    renderCart();
}

/* OPEN / CLOSE CART */
document.getElementById("cartToggle").addEventListener("click", () => {
    document.getElementById("cart").classList.add("active");
    renderCart();
});
document.getElementById("cart-close").addEventListener("click", () => {
    document.getElementById("cart").classList.remove("active");
});

let selectedSize = null;

document.querySelectorAll(".size-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".size-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        selectedSize = btn.textContent;
    });
});

function addToCart() {
    if (!selectedSize) return alert("Please select a size.");

    const product = {
        id: <?= $product['product_id'] ?>,
        name: document.getElementById("productName").textContent,
        price: parseFloat(document.getElementById("productPrice").textContent),
        size: selectedSize,
        image: mainImage.src,
        quantity: 1
    };

    let cart = loadCart();

    // If same item + same size exists, increase qty
    const existing = cart.find(
        item => item.name === product.name && item.size === product.size
    );

    if (existing) existing.quantity++;
    else cart.push(product);

    saveCart(cart);
    renderCart();
    alert("Added to Cart!");
}

/* Load badge on start */
updateBadge();

/* SEARCH functions */
document.getElementById("searchToggle").onclick = () => {
    document.getElementById("rightDefault").classList.add("hidden");
    document.getElementById("searchBar").classList.add("active");
    document.getElementById("searchClose").classList.remove("hidden");
};

document.getElementById("searchClose").onclick = () => {
    document.getElementById("searchBar").classList.remove("active");
    document.getElementById("rightDefault").classList.remove("hidden");
    document.getElementById("searchClose").classList.add("hidden");
};

document.querySelector(".btn-buy").addEventListener("click", () => {
    window.location.href = "checkout.html";
});

</script>

</body>
</html>

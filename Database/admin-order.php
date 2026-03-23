<?php
require_once 'dbconfig.php';

// Block non-admins
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $allowed = ['placed', 'processing', 'shipped', 'delivered'];
    if (in_array($_POST['status'], $allowed)) {
        $stmt = $pdo->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$_POST['status'], (int)$_POST['order_id']]);
    }
    header("Location: admin-orders.php");
    exit();
}

// Fetch all orders with user info
$orders = $pdo->query("
    SELECT o.order_id, o.order_date, o.status, o.total_price,
           u.first_name, u.last_name, u.email
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Orders – EveryWear</title>
<link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        .admin-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .admin-subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .orders-table th {
            background: #111;
            color: white;
            padding: 14px 16px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
        }

        .orders-table td {
            padding: 14px 16px;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .orders-table tr:hover td {
            background: #fafafa;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-placed     { background: #e0f0ff; color: #1a5fa8; }
        .badge-processing { background: #fff7e0; color: #a87a00; }
        .badge-shipped    { background: #e8e0ff; color: #5a1aa8; }
        .badge-delivered  { background: #e6fbef; color: #176944; }

        .update-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .update-form select {
            padding: 7px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
        }

        .update-form button {
            padding: 7px 16px;
            background: #111;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .update-form button:hover {
            background: linear-gradient(to right, #30CDF5, #00FAA0);
            color: #000;
        }

        .welcome-msg {
            color: #0066cc;
            font-weight: bold;
            margin-right: 10px;
            font-size: 14px;
        }

        /* Dark mode support */
        body.dark .orders-table { background: #1a1d24; }
        body.dark .orders-table td { color: #e5e7eb; border-bottom-color: #2a2f3a; }
        body.dark .orders-table tr:hover td { background: #22262f; }
        body.dark .update-form select { background: #2a2f3a; color: #e5e7eb; border-color: #444; }
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

            
            
 <!-- ADMIN SUB-NAV -->
<div style="background:#1f2937; padding:10px 0; text-align:center; position:sticky; top:70px; z-index:40;">
    <a href="admin-orders.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:linear-gradient(to right,#30CDF5,#00FAA0); color:black; font-size:14px; text-decoration:none; font-weight:500;">Orders</a>
    <a href="admin.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Dashboard</a>
    <a href="inventory.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Inventory</a>
    <a href="customermanag.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Customers</a>
    <a href="report.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Reports</a>
</div>
            
            
            
<!-- MAIN CONTENT -->
<div class="admin-page">
    <div class="admin-header">
        <h1>Manage Orders</h1>
    </div>
    <p class="admin-subtitle">
        Logged in as <strong><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></strong>
        &nbsp;·&nbsp; <?= count($orders) ?> orders total
    </p>

    <table class="orders-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Update Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong>#<?= $order['order_id'] ?></strong></td>
                <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                <td><?= htmlspecialchars($order['email']) ?></td>
                <td><?= htmlspecialchars($order['order_date']) ?></td>
                <td>£<?= number_format($order['total_price'], 2) ?></td>
                <td>
                    <span class="badge badge-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </td>
                <td>
                    <form method="POST" class="update-form">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <select name="status">
                            <?php foreach (['placed','processing','shipped','delivered'] as $s): ?>
                                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
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

</body>
</html>

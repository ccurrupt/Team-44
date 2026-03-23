<?php
require_once 'dbconfig.php'; // gives us $pdo + session_start()

// ── Admin guard ──
if (empty($_SESSION['is_admin'])) {
    header("Location: login.php?error=login_required");
    exit();
}

// ══════════════════════════════════════════════════════════════
//  QUERY 1 — Orders by status  (pie chart)
// ══════════════════════════════════════════════════════════════
try {
    $stmt = $pdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM Orders
        GROUP BY status
        ORDER BY cnt DESC
    ");
    $ordersByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ordersByStatus = [];
}

// ══════════════════════════════════════════════════════════════
//  QUERY 2 — Stock levels per product  (bar chart + low-stock list)
// ══════════════════════════════════════════════════════════════
try {
    $stmt = $pdo->query("
        SELECT product_id, name, stock, image_main
        FROM Products
        ORDER BY stock ASC
    ");
    $stockData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stockData = [];
}

$lowStock = array_filter($stockData, fn($p) => (int)$p['stock'] <= 5);

// ══════════════════════════════════════════════════════════════
//  QUERY 3 — Units sold per product  (line chart + top sellers)
// ══════════════════════════════════════════════════════════════
try {
    $stmt = $pdo->query("
        SELECT p.name,
               COALESCE(SUM(oi.quantity), 0) AS units_sold
        FROM Products p
        LEFT JOIN Order_Items oi ON oi.product_id = p.product_id
        GROUP BY p.product_id, p.name
        ORDER BY units_sold DESC
    ");
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $salesData = [];
}

$topSellers = array_slice($salesData, 0, 5);

// ══════════════════════════════════════════════════════════════
//  QUERY 4 — Revenue summary
// ══════════════════════════════════════════════════════════════
try {
    $stmt = $pdo->query("
        SELECT COUNT(*)          AS total_orders,
               COALESCE(SUM(total_price), 0) AS total_revenue
        FROM Orders
    ");
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $summary = ['total_orders' => 0, 'total_revenue' => 0];
}

// ── Encode PHP arrays for Chart.js ──
$ordersLabels  = json_encode(array_column($ordersByStatus, 'status'));
$ordersCounts  = json_encode(array_map('intval', array_column($ordersByStatus, 'cnt')));

$stockLabels   = json_encode(array_column($stockData, 'name'));
$stockValues   = json_encode(array_map('intval', array_column($stockData, 'stock')));

$salesLabels   = json_encode(array_column($salesData, 'name'));
$salesValues   = json_encode(array_map('intval', array_column($salesData, 'units_sold')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Reports</title>
<link rel="icon" type="image/png" href="images/logo.png">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="style.css">

<style>
/* ── Global ── */
body {
    margin: 0;
    background: #f6f8fb;
    font-family: "Inter", sans-serif;
    color: #1f2937;
}
h1, h2, h3 { margin: 0; }

/* ── Navbar ── */
.navbar {
    display: flex;
    justify-content: center;
    align-items: center;
    background: white;
    padding: 12px 0;
    box-shadow: 0 1px 8px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 50;
}
.nav-buttons { display: flex; gap: 20px; }
.nav-buttons a.nav-button {
    display: inline-flex;
    align-items: center;
    padding: 8px 20px;
    border-radius: 7px;
    background: #e7e9eb;
    color: black;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.25s ease;
}
.nav-buttons a.nav-button.active {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: black;
}
.nav-buttons a.nav-button:hover {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: black;
    transform: translateY(-2px) scale(1.05);
}

/* ── Page header ── */
.page-header {
    max-width: 1200px;
    margin: 30px auto 10px auto;
    text-align: center;
}
.page-header h1 { font-size: 32px; font-weight: 700; color: #111827; }

/* ── Summary stat cards ── */
.summary-row {
    width: 95%;
    max-width: 1200px;
    margin: 0 auto 20px auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}
.summary-card {
    background: white;
    padding: 22px 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.06);
}
.summary-card h3 { font-size: 13px; color: #6b7280; margin-bottom: 6px; }
.summary-card .value { font-size: 28px; font-weight: 700; }
.summary-card.highlight {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: black;
}

/* ── Reports grid ── */
.reports-container {
    width: 95%;
    max-width: 1200px;
    margin: 0 auto 60px auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

/* ── Card ── */
.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.chart-box h3 {
    text-align: center;
    margin-bottom: 15px;
    font-size: 20px;
}

/* ── Canvas ── */
canvas {
    background: #f7f8fa;
    border-radius: 12px;
    padding: 20px;
}

/* ── Tables ── */
.table-container { overflow-x: auto; }
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
    margin-top: 15px;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: center;
}
th { background: #1f2937; color: white; font-weight: 600; }
td { color: #374151; }

.in-stock     { color: #10b981; font-weight: 600; }
.low-stock    { color: #f59e0b; font-weight: 600; }
.out-of-stock { color: #ff0000; font-weight: 600; }

/* ── Info boxes ── */
.low-stock-box, .top-selling {
    margin-top: 15px;
    padding: 10px 15px;
    background: #f7f8fa;
    border-radius: 8px;
    font-size: 15px;
}
.low-stock-box h4, .top-selling h4 {
    margin-bottom: 8px;
    font-size: 16px;
    font-weight: 600;
}
.low-stock-box ul, .top-selling ol {
    padding-left: 20px;
    margin: 0;
}

@media (max-width: 768px) {
    .nav-buttons { flex-wrap: wrap; gap: 10px; }
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="welcome-msg">Hi <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</span>
                <?php if (!empty($_SESSION['is_admin'])): ?>
                    <a href="admin-orders.php" class="login-btn" style="background:#111;color:white;">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="login-btn">Log in</a>
                <a href="create-account.php" class="create-btn">
                    <img src="images/account.png" alt="" class="btn-icon"> Create Account
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
    <a href="admin-orders.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Orders</a>
    <a href="admin.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Dashboard</a>
    <a href="inventory.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Inventory</a>
    <a href="customermanag.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Customers</a>
    <a href="report.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:linear-gradient(to right,#30CDF5,#00FAA0); color:black; font-size:14px; text-decoration:none; font-weight:500;">Reports</a>
</div>

<div class="page-header">
    <h1>Admin Reports</h1>
</div>

<!-- ════════ SUMMARY STATS ════════ -->
<div class="summary-row">
    <div class="summary-card highlight">
        <h3>Total Orders</h3>
        <div class="value"><?= (int)$summary['total_orders'] ?></div>
    </div>
    <div class="summary-card">
        <h3>Total Revenue</h3>
        <div class="value">£<?= number_format((float)$summary['total_revenue'], 2) ?></div>
    </div>
    <div class="summary-card">
        <h3>Total Products</h3>
        <div class="value"><?= count($stockData) ?></div>
    </div>
    <div class="summary-card">
        <h3>Low / Out of Stock</h3>
        <div class="value" style="color:#ef4444;"><?= count($lowStock) ?></div>
    </div>
</div>

<!-- ════════ CHARTS + TABLES ════════ -->
<div class="reports-container">

    <!-- ── Orders Status Pie Chart ── -->
    <div class="card chart-box">
        <h3>Orders by Status</h3>
        <canvas id="ordersChart"></canvas>
    </div>

    <!-- ── Stock Levels Bar Chart ── -->
    <div class="card chart-box">
        <h3>Stock Levels</h3>
        <canvas id="stockChart"></canvas>
        <?php if (!empty($lowStock)): ?>
        <div class="low-stock-box">
            <h4>⚠ Low / Out of Stock</h4>
            <ul>
            <?php foreach ($lowStock as $p): ?>
                <li style="color:<?= (int)$p['stock'] === 0 ? '#ff0000' : '#f59e0b' ?>;">
                    <?= htmlspecialchars($p['name']) ?>
                    – <?= (int)$p['stock'] === 0 ? 'Out of Stock' : (int)$p['stock'] . ' left' ?>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Sales by Product Line Chart ── -->
    <div class="card chart-box">
        <h3>Sales by Product</h3>
        <canvas id="salesChart"></canvas>
        <?php if (!empty($topSellers)): ?>
        <div class="top-selling">
            <h4>🏆 Top Sellers</h4>
            <ol>
            <?php foreach ($topSellers as $s): ?>
                <li><?= htmlspecialchars($s['name']) ?> – <?= (int)$s['units_sold'] ?> units sold</li>
            <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── All Products Table ── -->
    <div class="card table-container">
        <h3>All Products Overview</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Stock</th>
                    <th>Units Sold</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Build a sold-lookup from salesData
            $soldMap = [];
            foreach ($salesData as $s) $soldMap[$s['name']] = (int)$s['units_sold'];

            foreach ($stockData as $p):
                $st = (int)$p['stock'];
                $cls = $st === 0 ? 'out-of-stock' : ($st <= 5 ? 'low-stock' : 'in-stock');
                $sold = $soldMap[$p['name']] ?? 0;
            ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td class="<?= $cls ?>"><?= $st ?></td>
                    <td><?= $sold ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════════ CHART.JS ════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ── Data from PHP ──
const ordersLabels = <?= $ordersLabels ?>;
const ordersCounts = <?= $ordersCounts ?>;
const stockLabels  = <?= $stockLabels ?>;
const stockValues  = <?= $stockValues ?>;
const salesLabels  = <?= $salesLabels ?>;
const salesValues  = <?= $salesValues ?>;

// ── Colour palettes ──
const pieColors = ['#ef4444','#f59e0b','#10b981','#6366f1','#3b82f6','#ec4899','#8b5cf6'];

function stockColor(val) {
    if (val === 0)  return '#ef4444';
    if (val <= 5)   return '#f59e0b';
    return '#10b981';
}

// ═══ Orders Status — Pie ═══
new Chart(document.getElementById('ordersChart'), {
    type: 'pie',
    data: {
        labels: ordersLabels,
        datasets: [{
            data: ordersCounts,
            backgroundColor: pieColors.slice(0, ordersLabels.length)
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// ═══ Stock Levels — Bar ═══
new Chart(document.getElementById('stockChart'), {
    type: 'bar',
    data: {
        labels: stockLabels,
        datasets: [{
            label: 'Stock Qty',
            data: stockValues,
            backgroundColor: stockValues.map(stockColor)
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// ═══ Sales by Product — Line ═══
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'Units Sold',
            data: salesValues,
            borderColor: '#4b74ff',
            backgroundColor: 'rgba(75,116,255,0.15)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
    }
});
               
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

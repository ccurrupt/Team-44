<?php
require_once 'dbconfig.php'; // gives us $pdo + session_start()

// ── Admin guard ──
if (empty($_SESSION['is_admin'])) {
    header("Location: login.php?error=login_required");
    exit();
}

// ── Allowed statuses (matches your tests.php whitelist) ──
$allowedStatuses = ['placed', 'processing', 'shipped', 'delivered'];

// ══════════════════════════════════════════════════════════════
//  AJAX: Update order status
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_status') {
        $orderId   = intval($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        if ($orderId <= 0 || !in_array($newStatus, $allowedStatuses)) {
            echo json_encode(['success' => false, 'error' => 'Invalid order ID or status.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE Orders SET status = :status WHERE order_id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $orderId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit();
}

// ══════════════════════════════════════════════════════════════
//  Dashboard stats
// ══════════════════════════════════════════════════════════════
try {
    $stats = $pdo->query("
        SELECT
            COUNT(*)                          AS total_orders,
            COALESCE(SUM(total_price), 0)     AS total_revenue,
            SUM(CASE WHEN status = 'placed' THEN 1 ELSE 0 END) AS pending_orders
        FROM Orders
    ")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total_orders' => 0, 'total_revenue' => 0, 'pending_orders' => 0];
}

// ══════════════════════════════════════════════════════════════
//  Registered users count
// ══════════════════════════════════════════════════════════════
try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
} catch (PDOException $e) {
    $userCount = 0;
}

// ══════════════════════════════════════════════════════════════
//  All orders (with customer name)
// ══════════════════════════════════════════════════════════════
try {
    $stmt = $pdo->query("
        SELECT o.order_id, o.order_date, o.status, o.total_price,
               u.first_name, u.last_name, u.email
        FROM Orders o
        LEFT JOIN Users u ON u.user_id = o.user_id
        ORDER BY o.order_date DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
}

// ══════════════════════════════════════════════════════════════
//  Order items lookup (for the detail modal)
// ���═════════════════════════════════════════════════════════════
$orderItems = [];
if (!empty($orders)) {
    try {
        $stmt = $pdo->query("
            SELECT oi.order_id, p.name, oi.quantity, oi.price
            FROM Order_Items oi
            LEFT JOIN Products p ON p.product_id = oi.product_id
            ORDER BY oi.order_id
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $orderItems[(int)$row['order_id']][] = $row;
        }
    } catch (PDOException $e) {
        $orderItems = [];
    }
}

// Encode for JS
$ordersJson = json_encode($orders);
$itemsJson  = json_encode($orderItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard & Orders</title>
<link rel="icon" type="image/png" href="images/logo.png">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="style.css">

<style>
    body {
        margin: 0;
        font-family: "Inter", sans-serif;
        background: #f6f8fb;
    }

    /* ── NAVBAR ── */
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

    /* ── WRAPPER ── */
    .wrapper {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    h1, h2 { margin-bottom: 25px; }

    /* ── STAT CARDS ── */
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.06);
    }
    .card h3 { margin: 0; color: #6b7280; font-size: 14px; }
    .card .value { font-size: 28px; font-weight: 600; margin-top: 10px; }
    .highlight {
        background: linear-gradient(to right, #30CDF5, #00FAA0);
        color: black;
    }

    /* ── ORDERS TABLE ── */
    .orders-container {
        width: 100%;
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.06);
        margin-bottom: 40px;
    }
    .orders-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .search-box {
        padding: 8px 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        width: 260px;
    }
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
    }
    table { width: 100%; border-collapse: collapse; }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
        text-align: center;
    }
    th {
        background: #1f2937;
        color: white;
        font-weight: 600;
    }
    tbody tr:hover { background: #f9fafb; }

    /* ── STATUS BADGES ── */
    .status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        display: inline-block;
        text-transform: capitalize;
    }
    .status-placed     { background: #fee2e2; color: #f80000; }
    .status-processing { background: #fef3c7; color: #f59e0b; }
    .status-shipped    { background: #d1fae5; color: #10b981; }
    .status-delivered   { background: #e0e7ff; color: #6366f1; }

    /* ── BUTTONS ── */
    button {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-family: "Inter", sans-serif;
        font-weight: 500;
        transition: all 0.25s ease;
    }
    button:hover { transform: scale(1.05); }
    .view-btn {
        background: linear-gradient(to right, #30CDF5, #00FAA0);
        color: black;
    }

    /* ── MODAL ── */
    .modal {
        display: none;
        position: fixed;
        background: rgba(0,0,0,0.5);
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        z-index: 100;
    }
    .modal-content {
        background: white;
        width: 550px;
        max-width: 92%;
        margin: 6% auto;
        padding: 28px;
        border-radius: 12px;
        position: relative;
    }
    .close {
        position: absolute;
        right: 15px;
        top: 10px;
        font-size: 22px;
        cursor: pointer;
    }
    .modal .buttons {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .btn-processing { background: linear-gradient(to right, #30CDF5, #00FAA0); color: black; }
    .btn-shipped    { background: #4b74ff; color: white; }
    .btn-delivered  { background: #6366f1; color: white; }
    .btn-processing:hover, .btn-shipped:hover, .btn-delivered:hover { opacity: 0.85; }

    .order-detail p { margin: 6px 0; font-size: 14px; }
    .order-detail strong { display: inline-block; width: 90px; }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
        font-size: 13px;
    }
    .items-table th { background: #374151; font-size: 12px; }
    .items-table td { padding: 8px; font-size: 13px; }

    .empty {
        text-align: center;
        color: #6b7280;
        padding: 20px;
    }

    /* ── TOAST ── */
    .toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 14px 22px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        font-size: 14px;
        z-index: 200;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .toast.show    { opacity: 1; }
    .toast.success { background: #10b981; }
    .toast.error   { background: #ef4444; }

    @media (max-width: 768px) {
        .nav-buttons { flex-wrap: wrap; gap: 10px; }
        .search-box  { width: 100%; }
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
    <a href="admin.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:linear-gradient(to right,#30CDF5,#00FAA0); color:black; font-size:14px; text-decoration:none; font-weight:500;">Dashboard</a>
    <a href="inventory.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Inventory</a>
    <a href="customermanag.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Customers</a>
    <a href="report.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Reports</a>
</div>

<div class="wrapper">

<h1>Dashboard Overview</h1>

<!-- ════════ STATS ════════ -->
<div class="stats">
    <div class="card highlight">
        <h3>Total Orders</h3>
        <div class="value"><?= (int)$stats['total_orders'] ?></div>
    </div>
    <div class="card">
        <h3>Total Revenue</h3>
        <div class="value">£<?= number_format((float)$stats['total_revenue'], 2) ?></div>
    </div>
    <div class="card">
        <h3>Pending Orders</h3>
        <div class="value" style="color:#ef4444;"><?= (int)$stats['pending_orders'] ?></div>
    </div>
    <div class="card">
        <h3>Registered Users</h3>
        <div class="value"><?= (int)$userCount ?></div>
    </div>
</div>

<!-- ════════ ORDER MANAGEMENT ════════ -->
<div class="orders-container">
    <div class="orders-top">
        <h2 style="margin:0;">Order Management</h2>
        <div style="display:flex;gap:10px;align-items:center;">
            <input type="text" class="search-box" id="searchBox" placeholder="Search orders...">
            <select class="filter-select" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="placed">Placed</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
            </select>
        </div>
    </div>

    <table id="ordersTable">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total (£)</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="6" class="empty">No orders yet.</td></tr>
        <?php else: ?>
            <?php foreach ($orders as $o):
                $statusCls = 'status-' . strtolower($o['status'] ?? 'placed');
                $customer  = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                if ($customer === '') $customer = $o['email'] ?? 'Unknown';
            ?>
            <tr data-id="<?= (int)$o['order_id'] ?>"
                data-status="<?= htmlspecialchars(strtolower($o['status'] ?? '')) ?>">
                <td>#<?= (int)$o['order_id'] ?></td>
                <td><?= htmlspecialchars($customer) ?></td>
                <td><?= htmlspecialchars($o['order_date'] ?? '') ?></td>
                <td>£<?= number_format((float)($o['total_price'] ?? 0), 2) ?></td>
                <td>
                    <span class="status <?= $statusCls ?>">
                        <?= htmlspecialchars($o['status'] ?? 'placed') ?>
                    </span>
                </td>
                <td>
                    <button class="view-btn" onclick="viewOrder(<?= (int)$o['order_id'] ?>)">View</button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</div><!-- /wrapper -->

<!-- ════════ ORDER DETAIL MODAL ══��═════ -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Order Details</h2>
        <div class="order-detail" id="orderInfo"></div>
        <div class="buttons" id="statusButtons"></div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ── Data from PHP ──
const allOrders  = <?= $ordersJson ?>;
const orderItems = <?= $itemsJson ?>;

const modal     = document.getElementById('orderModal');
const closeBtn  = document.getElementById('closeModal');
const orderInfo = document.getElementById('orderInfo');
const btnWrap   = document.getElementById('statusButtons');
const searchBox = document.getElementById('searchBox');
const statusFilter = document.getElementById('statusFilter');
const toast     = document.getElementById('toast');

let selectedOrderId = null;

// ── Toast ──
function showToast(msg, type = 'success') {
    toast.textContent = msg;
    toast.className = 'toast show ' + type;
    setTimeout(() => toast.className = 'toast', 3000);
}

// ── View order modal ──
function viewOrder(id) {
    selectedOrderId = id;
    const order = allOrders.find(o => parseInt(o.order_id) === id);
    if (!order) return;

    const customer = (order.first_name || '') + ' ' + (order.last_name || '') || order.email || 'Unknown';
    const items = orderItems[id] || [];

    let itemsHtml = '';
    if (items.length) {
        itemsHtml = `<table class="items-table">
            <thead><tr><th>Product</th><th>Qty</th><th>Price</th></tr></thead>
            <tbody>` +
            items.map(i =>
                `<tr>
                    <td>${escHtml(i.name || 'Unknown')}</td>
                    <td>${parseInt(i.quantity)}</td>
                    <td>£${parseFloat(i.price).toFixed(2)}</td>
                </tr>`
            ).join('') +
            `</tbody></table>`;
    } else {
        itemsHtml = '<p style="color:#999;font-size:13px;">No item details recorded.</p>';
    }

    orderInfo.innerHTML = `
        <p><strong>Order:</strong> #${id}</p>
        <p><strong>Customer:</strong> ${escHtml(customer.trim())}</p>
        <p><strong>Email:</strong> ${escHtml(order.email || '-')}</p>
        <p><strong>Date:</strong> ${escHtml(order.order_date || '-')}</p>
        <p><strong>Total:</strong> £${parseFloat(order.total_price || 0).toFixed(2)}</p>
        <p><strong>Status:</strong> <span class="status status-${(order.status||'').toLowerCase()}">${escHtml(order.status || '-')}</span></p>
        <h3 style="margin-top:16px;font-size:15px;">Items</h3>
        ${itemsHtml}
    `;

    // Build status buttons (only show statuses AFTER the current one)
    const flow = ['placed','processing','shipped','delivered'];
    const current = (order.status || 'placed').toLowerCase();
    const currentIdx = flow.indexOf(current);
    const btnClasses = { processing: 'btn-processing', shipped: 'btn-shipped', delivered: 'btn-delivered' };
    const btnLabels  = { processing: 'Process Order', shipped: 'Mark Shipped', delivered: 'Mark Delivered' };

    btnWrap.innerHTML = '';
    flow.forEach((s, i) => {
        if (i > currentIdx) {
            const b = document.createElement('button');
            b.className = btnClasses[s] || 'view-btn';
            b.textContent = btnLabels[s] || s;
            b.onclick = () => updateStatus(id, s);
            btnWrap.appendChild(b);
        }
    });

    modal.style.display = 'block';
}

// ── Update status via AJAX ──
async function updateStatus(orderId, newStatus) {
    const body = new URLSearchParams({
        action: 'update_status',
        order_id: orderId,
        status: newStatus
    });

    try {
        const res  = await fetch('admin.php', { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            showToast(`Order #${orderId} → ${newStatus}`);
            modal.style.display = 'none';
            // Update local data + DOM
            const order = allOrders.find(o => parseInt(o.order_id) === orderId);
            if (order) order.status = newStatus;
            const row = document.querySelector(`tr[data-id="${orderId}"]`);
            if (row) {
                row.dataset.status = newStatus;
                const badge = row.querySelector('.status');
                badge.className = 'status status-' + newStatus;
                badge.textContent = newStatus;
            }
            // Refresh pending count
            const pendingCount = allOrders.filter(o => (o.status||'').toLowerCase() === 'placed').length;
            document.querySelector('.stats .card:nth-child(3) .value').textContent = pendingCount;
        } else {
            showToast(data.error || 'Update failed.', 'error');
        }
    } catch (err) {
        showToast('Network error.', 'error');
    }
}

// ── Close modal ──
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };

// ── Search + filter ──
function applyFilters() {
    const term   = searchBox.value.toLowerCase();
    const status = statusFilter.value;
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
        const matchText   = row.textContent.toLowerCase().includes(term);
        const matchStatus = !status || row.dataset.status === status;
        row.style.display = (matchText && matchStatus) ? '' : 'none';
    });
}
searchBox.addEventListener('input', applyFilters);
statusFilter.addEventListener('change', applyFilters);

// ── Escape helper ──
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
                      
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

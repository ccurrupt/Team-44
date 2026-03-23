<?php
require_once 'dbconfig.php'; // gives us $pdo + session_start()

// ── Admin guard ──
if (empty($_SESSION['is_admin'])) {
    header("Location: login.php?error=login_required");
    exit();
}
$userName = $_SESSION['first_name'] ?? 'Admin';

// ── Handle AJAX CRUD actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // --- ADD ---
    if ($action === 'add') {
        $name     = trim($_POST['name']  ?? '');
        $price    = floatval($_POST['price'] ?? 0);
        $image    = trim($_POST['image'] ?? '');
        $stock    = intval($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? '');

        if ($name === '' || $price <= 0) {
            echo json_encode(['success' => false, 'error' => 'Name and a valid price are required.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO Products (name, price, image_main, stock, category)
                VALUES (:name, :price, :image, :stock, :category)
            ");
            $stmt->execute([
                ':name'     => $name,
                ':price'    => $price,
                ':image'    => $image,
                ':stock'    => $stock,
                ':category' => $category,
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    // --- UPDATE ---
    if ($action === 'update') {
        $id       = intval($_POST['id'] ?? 0);
        $name     = trim($_POST['name']  ?? '');
        $price    = floatval($_POST['price'] ?? 0);
        $image    = trim($_POST['image'] ?? '');
        $stock    = intval($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? '');

        if ($id <= 0 || $name === '' || $price <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid data.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE Products
                SET name = :name, price = :price, image_main = :image,
                    stock = :stock, category = :category
                WHERE product_id = :id
            ");
            $stmt->execute([
                ':name'     => $name,
                ':price'    => $price,
                ':image'    => $image,
                ':stock'    => $stock,
                ':category' => $category,
                ':id'       => $id,
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    // --- DELETE ---
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product ID.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM Products WHERE product_id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit();
}

// ── Fetch all products for the page ──
try {
    $stmt = $pdo->query("SELECT product_id, name, price, image_main, stock, category FROM Products ORDER BY product_id ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Inventory Management</title>
<link rel="icon" type="image/png" href="images/logo.png">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="style.css">

<style>
    body {
        margin: 0;
        background: #f6f8fb;
        font-family: "Inter", sans-serif;
    }

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

    .nav-buttons {
        display: flex;
        gap: 20px;
    }

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

    .inventory-container {
        width: 95%;
        max-width: 1200px;
        margin: 40px auto;
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.06);
    }

    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }

    .search-box {
        padding: 8px 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        width: 260px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 15px;
        margin-top: 10px;
    }

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

    button {
        padding: 8px 14px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-family: "Inter", sans-serif;
        font-weight: 500;
        transition: all 0.25s ease;
    }

    button:hover { transform: scale(1.05); }

    .add-btn {
        background: linear-gradient(to right, #30CDF5, #00FAA0);
        color: black;
    }

    .edit-btn { background: #30CDF5; color: white; }
    .delete-btn { background: #ff0000; color: white; }
    .in-stock { color: #10b981; font-weight: 600; }
    .low-stock { color: #f59e0b; font-weight: 600; }
    .out-of-stock { color: #ff0000; font-weight: 600; }

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
        width: 500px;
        max-width: 90%;
        margin: 8% auto;
        padding: 25px;
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

    .modal input, .modal select {
        width: 100%;
        padding: 10px 12px;
        margin: 8px 0;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 14px;
        box-sizing: border-box;
    }

    .modal label {
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        margin-top: 6px;
        display: block;
    }

    .modal .buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 15px;
    }

    .product-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
    }

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
    .toast.show { opacity: 1; }
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
    <a href="admin.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Dashboard</a>
    <a href="inventory.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:linear-gradient(to right,#30CDF5,#00FAA0); color:black; font-size:14px; text-decoration:none; font-weight:500;">Inventory</a>
    <a href="customermanag.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Customers</a>
    <a href="report.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Reports</a>
</div>

<div class="inventory-container">
    <div class="top-bar">
        <h2>Inventory Management</h2>
        <div style="display:flex;gap:10px;align-items:center;">
            <input type="text" class="search-box" id="searchBox" placeholder="Search products...">
            <button class="add-btn" id="addProductBtn">+ Add Product</button>
        </div>
    </div>

    <table id="inventoryTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Image</th>
                <th>Category</th>
                <th>Price (£)</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p):
            $stockClass = 'in-stock';
            if ((int)$p['stock'] === 0) $stockClass = 'out-of-stock';
            elseif ((int)$p['stock'] <= 5) $stockClass = 'low-stock';
        ?>
            <tr data-id="<?= (int)$p['product_id'] ?>">
                <td><?= (int)$p['product_id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><img class="product-img" src="<?= htmlspecialchars($p['image_main'] ?? 'images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($p['name']) ?>"></td>
                <td><?= htmlspecialchars($p['category'] ?? '') ?></td>
                <td>£<?= number_format((float)$p['price'], 2) ?></td>
                <td class="<?= $stockClass ?>"><?= (int)$p['stock'] ?></td>
                <td>
                    <button class="edit-btn" onclick="openEdit(<?= (int)$p['product_id'] ?>, this)">Edit</button>
                    <button class="delete-btn" onclick="deleteProduct(<?= (int)$p['product_id'] ?>)">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Add / Edit -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2 id="modalTitle">Add Product</h2>

        <input type="hidden" id="productId">

        <label>Product Name</label>
        <input type="text" id="productName" placeholder="e.g. Classic T-Shirt">

        <label>Price (£)</label>
        <input type="number" id="productPrice" step="0.01" min="0" placeholder="e.g. 25.00">

        <label>Image Filename / URL</label>
        <input type="text" id="productImage" placeholder="e.g. images/tshirt.png">

        <label>Stock Quantity</label>
        <input type="number" id="productStock" min="0" placeholder="e.g. 50">

        <label>Category</label>
        <input type="text" id="productCategory" placeholder="e.g. Unisex · Tops · T-shirt">

        <div class="buttons">
            <button id="saveProductBtn" class="add-btn">Save</button>
            <button class="delete-btn" id="cancelBtn">Cancel</button>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script>
const modal      = document.getElementById('productModal');
const closeBtn   = document.getElementById('closeModal');
const addBtn     = document.getElementById('addProductBtn');
const saveBtn    = document.getElementById('saveProductBtn');
const cancelBtn  = document.getElementById('cancelBtn');
const titleEl    = document.getElementById('modalTitle');
const searchBox  = document.getElementById('searchBox');
const toast      = document.getElementById('toast');

// ── Toast helper ──
function showToast(msg, type = 'success') {
    toast.textContent = msg;
    toast.className = 'toast show ' + type;
    setTimeout(() => toast.className = 'toast', 3000);
}

// ── Open modal: Add ──
addBtn.onclick = () => {
    titleEl.textContent = 'Add Product';
    document.getElementById('productId').value       = '';
    document.getElementById('productName').value      = '';
    document.getElementById('productPrice').value     = '';
    document.getElementById('productImage').value     = '';
    document.getElementById('productStock').value     = '';
    document.getElementById('productCategory').value  = '';
    modal.style.display = 'block';
};

// ── Open modal: Edit ──
function openEdit(id, btn) {
    const row = btn.closest('tr');
    const cells = row.querySelectorAll('td');

    titleEl.textContent = 'Edit Product';
    document.getElementById('productId').value       = id;
    document.getElementById('productName').value      = cells[1].textContent;
    document.getElementById('productImage').value     = cells[2].querySelector('img').getAttribute('src');
    document.getElementById('productCategory').value  = cells[3].textContent;
    document.getElementById('productPrice').value     = cells[4].textContent.replace('£', '');
    document.getElementById('productStock').value     = cells[5].textContent;
    modal.style.display = 'block';
}

// ── Close modal ──
closeBtn.onclick = cancelBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };

// ── Save (Add or Update) ──
saveBtn.onclick = async () => {
    const id       = document.getElementById('productId').value;
    const name     = document.getElementById('productName').value.trim();
    const price    = document.getElementById('productPrice').value;
    const image    = document.getElementById('productImage').value.trim();
    const stock    = document.getElementById('productStock').value;
    const category = document.getElementById('productCategory').value.trim();

    if (!name || !price) {
        showToast('Name and price are required.', 'error');
        return;
    }

    const body = new URLSearchParams({
        action:   id ? 'update' : 'add',
        id, name, price, image, stock, category
    });

    try {
        const res  = await fetch('inventory.php', { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            showToast(id ? 'Product updated!' : 'Product added!');
            modal.style.display = 'none';
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.error || 'Something went wrong.', 'error');
        }
    } catch (err) {
        showToast('Network error.', 'error');
    }
};

// ── Delete ──
async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;

    const body = new URLSearchParams({ action: 'delete', id });

    try {
        const res  = await fetch('inventory.php', { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            showToast('Product deleted.');
            document.querySelector(`tr[data-id="${id}"]`).remove();
        } else {
            showToast(data.error || 'Delete failed.', 'error');
        }
    } catch (err) {
        showToast('Network error.', 'error');
    }
}

// ── Live search filter ──
searchBox.addEventListener('input', () => {
    const term = searchBox.value.toLowerCase();
    document.querySelectorAll('#inventoryTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
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

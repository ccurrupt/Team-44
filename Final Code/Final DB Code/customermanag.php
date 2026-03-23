<?php
require_once 'dbconfig.php'; // gives us $pdo + session_start()

// ── Admin guard ──
if (empty($_SESSION['is_admin'])) {
    header("Location: login.php?error=login_required");
    exit();
}

// ══════════════════════════════════════════════════════════════
//  AJAX CRUD actions
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // ── ADD ──
    if ($action === 'add') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');
        $password  = trim($_POST['password']   ?? '');

        if ($firstName === '' || $email === '' || $password === '') {
            echo json_encode(['success' => false, 'error' => 'First name, email and password are required.']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
            exit();
        }

        // Check for duplicate email
        try {
            $chk = $pdo->prepare("SELECT user_id FROM Users WHERE email = :email");
            $chk->execute([':email' => $email]);
            if ($chk->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'That email is already registered.']);
                exit();
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO Users (first_name, last_name, email, phone, password, created_at)
                VALUES (:fn, :ln, :email, :phone, :pw, NOW())
            ");
            $stmt->execute([
                ':fn'    => $firstName,
                ':ln'    => $lastName,
                ':email' => $email,
                ':phone' => $phone,
                ':pw'    => $hashed,
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    // ── UPDATE ──
    if ($action === 'update') {
        $id        = intval($_POST['id'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');

        if ($id <= 0 || $firstName === '' || $email === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid data.']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
            exit();
        }

        // Check duplicate email (exclude current user)
        try {
            $chk = $pdo->prepare("SELECT user_id FROM Users WHERE email = :email AND user_id != :id");
            $chk->execute([':email' => $email, ':id' => $id]);
            if ($chk->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'That email belongs to another user.']);
                exit();
            }

            $stmt = $pdo->prepare("
                UPDATE Users
                SET first_name = :fn, last_name = :ln, email = :email, phone = :phone
                WHERE user_id = :id
            ");
            $stmt->execute([
                ':fn'    => $firstName,
                ':ln'    => $lastName,
                ':email' => $email,
                ':phone' => $phone,
                ':id'    => $id,
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    // ── DELETE ──
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid user ID.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM Users WHERE user_id = :id");
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

// ══════════════════════════════════════════════════════════════
//  Fetch all customers for the page
// ══════════════════════════════════════════════════════════════
try {
    $stmt = $pdo->query("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
               COUNT(o.order_id) AS total_orders,
               COALESCE(SUM(o.total_price), 0) AS total_spent
        FROM Users u
        LEFT JOIN Orders o ON o.user_id = u.user_id
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Customer Management</title>
<link rel="icon" type="image/png" href="images/logo.png">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="style.css">

<style>
body {
    margin: 0;
    background: #f6f8fb;
    font-family: "Inter", sans-serif;
}

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

/* ── Container ── */
.customer-container {
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

/* ── Table ── */
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
tbody tr:hover { background: #f9fafb; }

/* ── Buttons ── */
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
.view-btn {
    background: #6366f1;
    color: white;
    font-size: 13px;
    padding: 6px 12px;
}

/* ── Modal ── */
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
.modal label {
    font-weight: 600;
    font-size: 13px;
    color: #374151;
    margin-top: 6px;
    display: block;
}
.modal input {
    width: 100%;
    padding: 10px 12px;
    margin: 4px 0 8px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    box-sizing: border-box;
}
.modal .buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 15px;
}

/* ── Detail modal ── */
.detail p { margin: 6px 0; font-size: 14px; }
.detail strong { display: inline-block; width: 100px; }

/* ── Toast ── */
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
    <a href="admin.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Dashboard</a>
    <a href="inventory.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Inventory</a>
    <a href="customermanag.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:linear-gradient(to right,#30CDF5,#00FAA0); color:black; font-size:14px; text-decoration:none; font-weight:500;">Customers</a>
    <a href="report.php" style="display:inline-block; padding:8px 20px; margin:0 8px; border-radius:7px; background:#e7e9eb; color:black; font-size:14px; text-decoration:none; font-weight:500; transition:all 0.25s ease;" onmouseover="this.style.background='linear-gradient(to right,#30CDF5,#00FAA0)'" onmouseout="this.style.background='#e7e9eb'">Reports</a>
</div>

<div class="customer-container">
    <div class="top-bar">
        <h2>Customer Management</h2>
        <div style="display:flex;gap:10px;align-items:center;">
            <input type="text" class="search-box" id="searchBox" placeholder="Search customers...">
            <button class="add-btn" id="addCustomerBtn">+ Add Customer</button>
        </div>
    </div>

    <table id="customerTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($customers)): ?>
            <tr><td colspan="8" style="color:#6b7280;padding:20px;">No customers found.</td></tr>
        <?php else: ?>
            <?php foreach ($customers as $c):
                $name = trim(htmlspecialchars(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')));
                if ($name === '') $name = '—';
                $joined = $c['created_at'] ? date('d M Y', strtotime($c['created_at'])) : '—';
            ?>
            <tr data-id="<?= (int)$c['user_id'] ?>">
                <td><?= (int)$c['user_id'] ?></td>
                <td><?= $name ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                <td><?= (int)$c['total_orders'] ?></td>
                <td>£<?= number_format((float)$c['total_spent'], 2) ?></td>
                <td><?= $joined ?></td>
                <td>
                    <button class="edit-btn" onclick="openEdit(<?= (int)$c['user_id'] ?>, this)">Edit</button>
                    <button class="delete-btn" onclick="deleteCustomer(<?= (int)$c['user_id'] ?>)">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ════════ ADD / EDIT MODAL ════════ -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2 id="modalTitle">Add Customer</h2>

        <input type="hidden" id="customerId">

        <label>First Name *</label>
        <input type="text" id="customerFirstName" placeholder="e.g. John">

        <label>Last Name</label>
        <input type="text" id="customerLastName" placeholder="e.g. Smith">

        <label>Email *</label>
        <input type="email" id="customerEmail" placeholder="e.g. john@example.com">

        <label>Phone</label>
        <input type="text" id="customerPhone" placeholder="e.g. 07700 900000">

        <div id="passwordGroup">
            <label>Password * <small style="color:#999;">(new accounts only)</small></label>
            <input type="password" id="customerPassword" placeholder="Min 6 characters">
        </div>

        <div class="buttons">
            <button id="saveCustomerBtn" class="add-btn">Save</button>
            <button class="delete-btn" id="cancelBtn">Cancel</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
const modal      = document.getElementById('customerModal');
const closeBtn   = document.getElementById('closeModal');
const addBtn     = document.getElementById('addCustomerBtn');
const saveBtn    = document.getElementById('saveCustomerBtn');
const cancelBtn  = document.getElementById('cancelBtn');
const titleEl    = document.getElementById('modalTitle');
const searchBox  = document.getElementById('searchBox');
const toast      = document.getElementById('toast');
const pwGroup    = document.getElementById('passwordGroup');

// ── Toast ──
function showToast(msg, type = 'success') {
    toast.textContent = msg;
    toast.className = 'toast show ' + type;
    setTimeout(() => toast.className = 'toast', 3000);
}

// ── Open modal: Add ──
addBtn.onclick = () => {
    titleEl.textContent = 'Add Customer';
    document.getElementById('customerId').value        = '';
    document.getElementById('customerFirstName').value  = '';
    document.getElementById('customerLastName').value   = '';
    document.getElementById('customerEmail').value      = '';
    document.getElementById('customerPhone').value      = '';
    document.getElementById('customerPassword').value   = '';
    pwGroup.style.display = 'block';
    modal.style.display = 'block';
};

// ── Open modal: Edit ──
function openEdit(id, btn) {
    const row   = btn.closest('tr');
    const cells = row.querySelectorAll('td');

    // Name is in cells[1] as "First Last" — split it
    const parts = cells[1].textContent.trim().split(' ');
    const first = parts[0] || '';
    const last  = parts.slice(1).join(' ') || '';

    titleEl.textContent = 'Edit Customer';
    document.getElementById('customerId').value        = id;
    document.getElementById('customerFirstName').value  = first;
    document.getElementById('customerLastName').value   = last;
    document.getElementById('customerEmail').value      = cells[2].textContent.trim();
    document.getElementById('customerPhone').value      = cells[3].textContent.trim() === '—' ? '' : cells[3].textContent.trim();
    document.getElementById('customerPassword').value   = '';
    pwGroup.style.display = 'none'; // hide password on edit
    modal.style.display = 'block';
}

// ── Close modal ──
closeBtn.onclick = cancelBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };

// ── Save (Add / Update) ──
saveBtn.onclick = async () => {
    const id        = document.getElementById('customerId').value;
    const firstName = document.getElementById('customerFirstName').value.trim();
    const lastName  = document.getElementById('customerLastName').value.trim();
    const email     = document.getElementById('customerEmail').value.trim();
    const phone     = document.getElementById('customerPhone').value.trim();
    const password  = document.getElementById('customerPassword').value;

    if (!firstName || !email) {
        showToast('First name and email are required.', 'error');
        return;
    }

    if (!id && password.length < 6) {
        showToast('Password must be at least 6 characters.', 'error');
        return;
    }

    const params = new URLSearchParams({
        action:     id ? 'update' : 'add',
        id,
        first_name: firstName,
        last_name:  lastName,
        email,
        phone,
        password
    });

    try {
        const res  = await fetch('customermanag.php', { method: 'POST', body: params });
        const data = await res.json();

        if (data.success) {
            showToast(id ? 'Customer updated!' : 'Customer added!');
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
async function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer? Their orders will remain in the system.')) return;

    const body = new URLSearchParams({ action: 'delete', id });

    try {
        const res  = await fetch('customermanag.php', { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            showToast('Customer deleted.');
            document.querySelector(`tr[data-id="${id}"]`).remove();
        } else {
            showToast(data.error || 'Delete failed.', 'error');
        }
    } catch (err) {
        showToast('Network error.', 'error');
    }
}

// ── Live search ──
searchBox.addEventListener('input', () => {
    const term = searchBox.value.toLowerCase();
    document.querySelectorAll('#customerTable tbody tr').forEach(row => {
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

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

// Initialize cart for cart count
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartCount = count($_SESSION['cart']);

// Database connection
$host = 'localhost';
$dbname = 'cs2team44_db';
$username = 'cs2team44';
$password = 'wpRwMNcuA4uajOG92dzRRqbhb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get filter parameters
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'recommended';

// Build SQL query
$sql = "SELECT * FROM Products WHERE 1=1";
$params = [];

if (!empty($category)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR description LIKE :search OR category LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add sorting
switch ($sort) {
    case 'price-asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price-desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'new':
        $sql .= " ORDER BY created_at DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY rating DESC";
        break;
    case 'recommended':
    default:
        $sql .= " ORDER BY rating DESC, created_at DESC";
        break;
}

// Execute query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error loading products: " . $e->getMessage());
}

// Get distinct categories for filter menu
try {
    $categoryStmt = $pdo->query("SELECT DISTINCT category FROM Products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Parse categories into a grouped hierarchy for the browse menu
// Categories are stored as "Unisex · Tops · T-shirt" format
$groupedCategories = [];
foreach ($categories as $cat) {
    $parts = array_map('trim', explode('·', $cat));
    if (count($parts) >= 3) {
        $group = $parts[1];
        $item = $parts[2];
    } elseif (count($parts) == 2) {
        $group = $parts[1];
        $item = null;
    } else {
        $group = $parts[0];
        $item = null;
    }
    if (!isset($groupedCategories[$group])) {
        $groupedCategories[$group] = [];
    }
    $groupedCategories[$group][] = [
        'full' => $cat,
        'item' => $item
    ];
}
ksort($groupedCategories);

$breadcrumb = "All products";
if (!empty($category)) {
    $breadcrumb = htmlspecialchars($category);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>EveryWear - Products</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="icon" type="image/png" href="logo.png">
	<link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    
   
  

    /* ======= PAGE CONTENT ======= */
    .page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.5rem 1.25rem 2.5rem;
		flex 1 0 auto;
    }

    .filter-row {
      position: relative;
      margin-bottom: 1.8rem;
      padding: 0.9rem 1.2rem;
      border-radius: 0.9rem;
      background: white;
      border: 1px solid #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.25rem;
      flex-wrap: wrap;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .filter-breadcrumb {
      font-size: 0.8rem;
      color: #6b7280;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      white-space: nowrap;
    }

    .filter-breadcrumb a { color: #6b7280; }
    .filter-breadcrumb a:hover { color: black; }
    .filter-breadcrumb span.separator { color: #d1d5db; }
    .filter-breadcrumb-current { color: #374151; font-weight: 500; }

    .filter-controls {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex: 1;
      min-width: 0;
      justify-content: flex-end;
    }

    .filter-search {
      flex: 0 1 320px;
      min-width: 200px;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: white;
      border-radius: 999px;
      padding: 0.4rem 0.9rem;
      border: 1px solid #d1d5db;
    }

    .filter-search input {
      border: none;
      outline: none;
      width: 100%;
      font-size: 0.88rem;
      background: transparent;
      color: black;
    }

    .filter-search input::placeholder { color: #6b7280; }

    .icon-btn {
      width: 40px;
      height: 40px;
      border-radius: 999px;
      border: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: background 0.15s ease, transform 0.12s ease;
    }

    .hamburger-btn {
      background: black;
      color: white;
    }

    .hamburger-btn:hover {
      background: #333;
      transform: translateY(-1px);
    }

    .hamburger-btn span {
      position: relative;
      width: 18px;
      height: 2px;
      border-radius: 999px;
      background: white;
      transition: background 0.15s ease;
    }

    .hamburger-btn span::before,
    .hamburger-btn span::after {
      content: "";
      position: absolute;
      left: 0;
      width: 18px;
      height: 2px;
      border-radius: 999px;
      background: white;
      transition: transform 0.18s ease, top 0.18s ease, bottom 0.18s ease;
    }

    .hamburger-btn span::before { top: -5px; }
    .hamburger-btn span::after { bottom: -5px; }

    .hamburger-btn.is-open span { background: transparent; }
    .hamburger-btn.is-open span::before { top: 0; transform: rotate(45deg); }
    .hamburger-btn.is-open span::after { bottom: 0; transform: rotate(-45deg); }

    .filter-menu {
      position: absolute;
      right: 1.2rem;
      top: calc(100% + 0.6rem);
      width: min(640px, 100%);
      background: white;
      border-radius: 0.9rem;
      border: 1px solid #e5e7eb;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
      padding: 1rem 1.1rem 0.9rem;
      display: none;
      opacity: 0;
      transform: translateY(4px);
      transition: opacity 0.18s ease, transform 0.18s ease;
      z-index: 20;
    }

    .filter-menu.is-open {
      display: block;
      opacity: 1;
      transform: translateY(0);
    }

    .filter-menu-header {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 0.6rem;
    }

    .filter-menu-title { font-size: 0.86rem; font-weight: 600; color: black; }

    .filter-menu-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.6rem 1.25rem;
      font-size: 0.8rem;
    }

    .filter-menu-group-title {
      font-weight: 600;
      margin-bottom: 0.25rem;
      cursor: pointer;
      border: none;
      background: transparent;
      padding: 0;
      font-size: 0.8rem;
      text-align: left;
      color: black;
      display: block;
    }

    .filter-menu-group-title.active {
      text-decoration: underline;
    }

    .filter-menu-items {
      list-style: none;
      padding-left: 0;
      margin: 0;
      color: #6b7280;
    }

    .filter-menu-items li a {
      font-size: 0.78rem;
      color: inherit;
      display: block;
    }

    .filter-menu-items li a:hover { color: black; }

    /* Sort */
    .product-list-section { margin-bottom: 2rem; }

    .product-list-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.75rem;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .product-list-heading { font-size: 1.05rem; font-weight: 600; }

    .sort-dropdown { position: relative; }

    .sort-toggle {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.45rem 0.9rem;
      border-radius: 999px;
      border: 1px solid #e5e7eb;
      background: white;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      font-size: 0.85rem;
      color: inherit;
    }

    .sort-label { color: #6b7280; }
    .sort-value { font-weight: 600; color: black; }
    .sort-chevron { margin-left: 0.15rem; font-size: 0.75rem; }

    .sort-menu {
      position: absolute;
      right: 0;
      top: calc(100% + 0.4rem);
      width: 230px;
      background: white;
      border-radius: 0.9rem;
      border: 1px solid #e5e7eb;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
      padding: 0.5rem 0;
      display: none;
      z-index: 25;
    }

    .sort-menu.is-open { display: block; }

    .sort-option {
      width: 100%;
      text-align: left;
      padding: 0.5rem 1rem;
      border: none;
      background: transparent;
      font-size: 0.86rem;
      color: black;
      cursor: pointer;
      display: block;
    }

    .sort-option:hover { background: #f9fafb; }
    .sort-option.is-active { font-weight: 600; background: #f3f4f6; }

    /* Product Grid */
    .product-list-grid {
      display: grid;
      gap: 1.25rem;
      align-items: stretch;
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    @media (max-width: 1024px) {
      .product-list-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 768px) {
      .product-list-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 520px) {
      .product-list-grid { grid-template-columns: 1fr; }
    }

    .product-card {
      border-radius: 10px;
      border: 1px solid #e5e7eb;
      overflow: hidden;
      background: white;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      transition: box-shadow 0.2s ease, transform 0.2s ease;
      height: 100%;
      color: inherit;
    }

    .product-card:hover {
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      transform: translateY(-3px);
    }

    .product-card-img {
      position: relative;
      background: #e5e7eb;
      padding-top: 120%;
    }

    .product-card-img-inner {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .product-card-img-inner img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .product-card-body {
      padding: 0.6rem 0.7rem 0.8rem;
      font-size: 0.85rem;
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }

    .product-card-name { font-weight: 500; color: #111827; }
    .product-card-meta { font-size: 0.75rem; color: #6b7280; }

    .product-card-price {
      font-size: 0.9rem;
      color: #111827;
      margin-top: 0.1rem;
      font-weight: 600;
    }

    
    @media (max-width: 900px) {
      .filter-row { flex-direction: column; align-items: stretch; }
      .filter-controls { justify-content: space-between; }
      .filter-search { flex: 1; }
    }

    @media (max-width: 600px) {
       
    }

    @media (max-width: 640px) {
      .filter-menu { left: 0.9rem; right: 0.9rem; width: auto; }
      .filter-menu-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
         
            /* FOOTER — matches index.php */
    footer {
      background: #111 !important;
      color: white !important;
      padding: 50px 20px 30px !important;
      margin-top: 40px !important;
      display: block !important;
      width: 100% !important;
    }

    .footer-grid {
      max-width: 1200px !important;
      margin: 0 auto !important;
      display: grid !important;
      grid-template-columns: repeat(4, 1fr) !important;
      gap: 40px !important;
      padding: 0 1.5rem !important;
    }

    .footer-col h4 {
      margin-bottom: 20px;
      font-size: 16px;
    }

    .footer-col ul {
      list-style: none !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    .footer-col li {
      margin-bottom: 10px;
    }

    .footer-col a {
      color: #d1d5db !important;
      text-decoration: none !important;
    }

    .footer-col a:hover {
      color: white !important;
    }

    .social-icons {
      display: flex;
      gap: 15px;
      margin-top: 15px;
    }

    .social-icons i {
      font-size: 20px;
      cursor: pointer;
      color: #f3f4f6;
    }

    .copyright {
      text-align: center;
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid #333;
      color: #888;
      font-size: 14px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }                
                        
                        
             
  </style>
</head>

<body> 
  <!-- NAVBAR  -->
    <div class="navbar">
    <div class="logo-section">
      <a href="index.php" class="logo-link">
        <img src="logo.png" alt="EveryWear Logo" class="site-logo">
      </a>
    </div>

    <div class="nav-buttons">
      <a href="index.php"    class="nav-button">Home</a>
      <a href="about.php"    class="nav-button">About Us</a>
      <a href="products.php" class="nav-button active">Products</a>
      <a href="reviews.php"  class="nav-button">Reviews</a>
      <a href="orders.php"   class="nav-button">Orders</a>
    </div>

    <div class="right-controls">
      <div class="right-default">
        <?php if ($isLoggedIn): ?>
          <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
          <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
          <a href="login.php" class="login-btn">Log in</a>
          <a href="create-account.php" class="create-btn">Create Account</a>
        <?php endif; ?>

        <a href="cart.php" class="icon-link">
          <img src="basket.png" alt="Cart" class="nav-icon">
          <?php if($cartCount > 0): ?>
            <span class="cart-count-badge"><?php echo $cartCount; ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </div>

  <!-- ======= PAGE CONTENT ======= -->
  <div class="page">
    <section class="filter-row">
      <div class="filter-breadcrumb">
        <a href="index.php">Home</a>
        <span class="separator">/</span>
        <a href="products.php" id="viewAllLink">View All</a>
        <span class="separator">/</span>
        <span id="crumbCategory" class="filter-breadcrumb-current">
          <?php echo $breadcrumb; ?>
        </span>
      </div>

      <div class="filter-controls">
        <form method="GET" action="products.php" class="filter-search" id="searchForm">
          <i class="ri-search-line" style="color: #6b7280;"></i>
          <input
            id="searchInput"
            name="search"
            type="text"
            placeholder="Search EveryWear"
            value="<?php echo htmlspecialchars($search); ?>"
          />
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
          <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        </form>

        <button class="icon-btn hamburger-btn" id="filterMenuToggle" aria-label="Browse categories" aria-expanded="false">
          <span></span>
        </button>
      </div>

      <!-- Browse by category now uses grouped categories -->
      <div class="filter-menu" id="filterMenu" aria-hidden="true">
        <div class="filter-menu-header">
          <div class="filter-menu-title">Browse by category</div>
        </div>

        <div class="filter-menu-grid">
          <?php if (!empty($groupedCategories)): ?>
            <?php foreach ($groupedCategories as $group => $items): ?>
              <div>
                <a href="products.php?search=<?php echo urlencode($group); ?>" class="filter-menu-group-title"><?php echo htmlspecialchars($group); ?></a>
                <ul class="filter-menu-items">
                  <?php foreach ($items as $item): ?>
                    <li>
                      <a href="products.php?category=<?php echo urlencode($item['full']); ?>"
                         <?php echo ($category === $item['full']) ? 'style="color:black;font-weight:600;"' : ''; ?>>
                        <?php echo htmlspecialchars($item['item'] ?? $group); ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="color: #6b7280; font-size: 0.9rem;">No categories found.</div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="product-list-section">
      <div class="product-list-header">
        <h2 class="product-list-heading">
          <?php
          if (!empty($category)) {
            echo htmlspecialchars($category) . " Collection";
          } elseif (!empty($search)) {
            echo "Search Results for: " . htmlspecialchars($search);
          } else {
            echo "All Products";
          }
          ?>
          <span style="font-size: 0.9rem; color: #6b7280; font-weight: normal; margin-left: 10px;">
            (<?php echo count($products); ?> items)
          </span>
        </h2>

        <div class="sort-dropdown" id="sortDropdown">
          <a href="#" class="sort-toggle" id="sortToggle">
            <span class="sort-label">Sort by :</span>
            <span class="sort-value" id="sortCurrent">
              <?php
              $sortLabels = [
                'recommended' => 'Recommended',
                'new' => "What's New",
                'rating' => 'Customer Rating',
                'price-desc' => 'Price: High to Low',
                'price-asc' => 'Price: Low to High'
              ];
              echo $sortLabels[$sort] ?? 'Recommended';
              ?>
            </span>
            <span class="sort-chevron">▾</span>
          </a>
          <div class="sort-menu" id="sortMenu">
            <?php foreach ($sortLabels as $key => $label): ?>
              <a href="products.php?category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $key; ?>"
                 class="sort-option <?php echo ($sort === $key) ? 'is-active' : ''; ?>">
                <?php echo $label; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div id="productList" class="product-list-grid">
        <?php if (empty($products)): ?>
          <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #6b7280;">
            <p style="font-size: 1.1rem;">No products found.</p>
            <p>Try a different search or category.</p>
          </div>
        <?php else: ?>
          <?php foreach ($products as $product): ?>
            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="product-card">
              <div class="product-card-img">
                <div class="product-card-img-inner">
                  <?php if (!empty($product['image_main'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image_main']); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                  <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #6b7280;">
                      No Image
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="product-card-body">
                <div class="product-card-name"><?php echo htmlspecialchars($product['name']); ?></div>
                <div class="product-card-meta">
                  <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?> ·
                  <?php
                  if (!empty($product['materials'])) {
                    echo htmlspecialchars(substr($product['materials'], 0, 50));
                    if (strlen($product['materials']) > 50) echo '...';
                  } else {
                    echo 'Various materials';
                  }
                  ?>
                </div>
                <div class="product-card-price">
                  £<?php echo number_format($product['price'], 2); ?>
                </div>
                <?php if (!empty($product['rating'])): ?>
                  <div style="color: #f59e0b; font-size: 0.8rem; margin-top: 2px;">
                    ★ <?php echo number_format($product['rating'], 1); ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($product['is_sustainable']) && $product['is_sustainable']): ?>
                  <div style="font-size: 0.7rem; color: #10b981; margin-top: 5px;">
                    ♻️ Sustainable
                  </div>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <!-- FOOTER -->
  <footer>
    <div class="footer-grid">
      <div class="footer-col">
        <h4>Shop</h4>
        <ul>
          <?php foreach ($groupedCategories as $group => $items): ?>
            <li><a href="products.php?search=<?php echo urlencode($group); ?>"><?php echo htmlspecialchars($group); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Help</h4>
        <ul>
          <li><a href="#">Contact Us</a></li>
          <li><a href="#">Shipping Info</a></li>
          <li><a href="#">Returns</a></li>
          <li><a href="#">FAQ</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>About</h4>
        <ul>
          <li><a href="about.php">Our Story</a></li>
          <li><a href="#">Sustainability</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="#">Press</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Connect</h4>
        <div class="social-icons">
          <i class="ri-instagram-line"></i>
          <i class="ri-facebook-circle-line"></i>
          <i class="ri-twitter-line"></i>
          <i class="ri-tiktok-line"></i>
        </div>
        <p style="margin-top: 20px; color: #aaa; font-size: 14px;">
          Student project for university
        </p>
      </div>
    </div>

    <div class="copyright">
      &copy; 2025 EveryWear. This is a university project.
    </div>
  </footer>

  <script>
    const searchForm = document.getElementById("searchForm");
    const searchInput = document.getElementById("searchInput");
    const sortToggle = document.getElementById("sortToggle");
    const sortMenu = document.getElementById("sortMenu");
    const menuToggleBtn = document.getElementById("filterMenuToggle");
    const filterMenu = document.getElementById("filterMenu");

    // Search form submission
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        searchForm.submit();
      }
    });

    // Sort menu toggle
    sortToggle.addEventListener("click", function (e) {
      e.preventDefault();
      sortMenu.classList.toggle("is-open");
    });

    document.addEventListener("click", function (e) {
      if (!sortMenu.contains(e.target) && !sortToggle.contains(e.target)) {
        sortMenu.classList.remove("is-open");
      }
    });

    // Filter menu toggle
    function toggleMenu(force) {
      const willOpen = typeof force === "boolean"
        ? force
        : !filterMenu.classList.contains("is-open");

      filterMenu.classList.toggle("is-open", willOpen);
      menuToggleBtn.classList.toggle("is-open", willOpen);
      menuToggleBtn.setAttribute("aria-expanded", String(willOpen));
      filterMenu.setAttribute("aria-hidden", String(!willOpen));
    }

    menuToggleBtn.addEventListener("click", function (e) {
      e.preventDefault();
      toggleMenu();
    });

    document.addEventListener("click", function (e) {
      if (!filterMenu.classList.contains("is-open")) return;
      if (!filterMenu.contains(e.target) && !menuToggleBtn.contains(e.target)) {
        toggleMenu(false);
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        if (filterMenu.classList.contains("is-open")) toggleMenu(false);
        if (sortMenu.classList.contains("is-open")) sortMenu.classList.remove("is-open");
      }
    });
  </script>
</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

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

// Build SQL query - REMOVED references to 'type' column
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

// Get distinct categories for filter menu - FIXED: Only get categories
try {
    $categoryStmt = $pdo->query("SELECT DISTINCT category FROM Products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Remove the $typeStmt query completely - there's no 'type' column

// Also update the breadcrumb logic to not reference 'type'
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
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
     * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: white;
      color: black;
      line-height: 1.5;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1.5rem 1.25rem 2.5rem;
      background: white;
      min-height: 100vh;
    }

    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 0.9rem;
      border-bottom: 1px solid #e5e7eb;
      margin-bottom: 1.25rem;
      gap: 0.75rem;
    }

    .logo img {
      height: 42px;
      width: auto;
      display: block;
      object-fit: contain;
    }

    .header-nav {
      display: flex;
      gap: 1.25rem;
      font-size: 0.85rem;
      color: #6b7280;
    }

    .header-nav a {
      position: relative;
      padding-bottom: 0.1rem;
      color: #6b7280;
      text-decoration: none;
    }

    .header-nav a:hover::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: 0;
      width: 100%;
      height: 1px;
      background: black;
    }

    .header-nav a.active {
      color: black;
      font-weight: 500;
    }

    .user-controls {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .user-controls .welcome-msg {
      padding: 6px 12px;
      background: #4a78ff;
      color: white;
      border-radius: 4px;
      font-weight: bold;
      font-size: 14px;
    }

    .user-controls .logout-btn {
      padding: 6px 12px;
      background: #e35f26;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      font-size: 14px;
      display: inline-block;
    }

    .user-controls .login-btn,
    .user-controls .create-btn {
      padding: 6px 12px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
    }

    .user-controls .login-btn {
      background: #4a78ff;
      color: white;
    }

    .user-controls .create-btn {
      background: black;
      color: white;
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
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }

    .filter-breadcrumb {
      font-size: 0.8rem;
      color: #6b7280;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      white-space: nowrap;
    }

    .filter-breadcrumb a {
      color: #6b7280;
      text-decoration: none;
    }

    .filter-breadcrumb a:hover {
      color: black;
    }

    .filter-breadcrumb span.separator {
      color: #d1d5db;
    }

    .filter-breadcrumb-current {
      color: #374151;
      font-weight: 500;
    }

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

    .filter-search-icon {
      width: 14px;
      height: 14px;
      border-radius: 999px;
      border: 2px solid #6b7280;
      position: relative;
      flex-shrink: 0;
    }

    .filter-search-icon::after {
      content: "";
      position: absolute;
      width: 7px;
      height: 2px;
      background: #6b7280;
      border-radius: 999px;
      right: -4px;
      bottom: -1px;
      transform: rotate(35deg);
    }

    .filter-search input {
      border: none;
      outline: none;
      width: 100%;
      font-size: 0.88rem;
      background: transparent;
      color: black;
    }

    .filter-search input::placeholder {
      color: #6b7280;
    }

    .icon-btn {
      width: 40px;
      height: 40px;
      border-radius: 999px;
      border: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.25);
      transition: background 0.15s ease, box-shadow 0.15s ease,
                  transform 0.12s ease, color 0.15s ease;
    }

    .wishlist-btn {
      background: #f9fafb;
      color: black;
    }

    .wishlist-btn::before {
      content: "\2661";
      font-size: 17px;
      line-height: 1;
    }

    .wishlist-btn:hover {
      background: #e5e7eb;
      transform: translateY(-1px);
    }

    .wishlist-btn.has-items {
      background: #e5e7eb;
      box-shadow: 0 10px 26px rgba(15, 23, 42, 0.28);
    }

    .hamburger-btn {
      background: black;
      color: white;
    }

    .hamburger-btn:hover {
      background: #111827;
      box-shadow: 0 12px 32px rgba(15, 23, 42, 0.35);
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

    .hamburger-btn span::before {
      top: -5px;
    }

    .hamburger-btn span::after {
      bottom: -5px;
    }

    .hamburger-btn.is-open span {
      background: transparent;
    }

    .hamburger-btn.is-open span::before {
      top: 0;
      transform: rotate(45deg);
    }

    .hamburger-btn.is-open span::after {
      bottom: 0;
      transform: rotate(-45deg);
    }

    @media (max-width: 720px) {
      .filter-row {
        flex-direction: column;
        align-items: stretch;
      }

      .filter-controls {
        justify-content: space-between;
      }

      .filter-search {
        flex: 1;
      }
    }

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

    .filter-menu-title {
      font-size: 0.86rem;
      font-weight: 600;
      color: black;
    }

    .filter-menu-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 0.6rem 3rem;
      font-size: 0.8rem;
    }

    @media (max-width: 640px) {
      .filter-menu {
        left: 0.9rem;
        right: 0.9rem;
        width: auto;
      }
      .filter-menu-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
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
    }

    .filter-menu-group-title.active {
      text-decoration: underline;
      text-decoration-thickness: 1px;
      text-underline-offset: 2px;
    }

    .filter-menu-items {
      list-style: none;
      padding-left: 0;
      margin: 0;
      color: gray;
    }

    .filter-menu-items li {
      margin: 0.1rem 0;
    }

    .filter-menu-items li button {
      border: none;
      background: transparent;
      padding: 0;
      margin: 0;
      font-size: 0.78rem;
      color: inherit;
      cursor: pointer;
      text-align: left;
    }

    .filter-menu-items li button:hover {
      color: black;
    }

    .product-list-section {
      margin-bottom: 2rem;
    }

    .product-list-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.75rem;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .product-list-heading {
      font-size: 1.05rem;
      font-weight: 600;
    }

    .product-sort {
      position: relative;
      font-size: 0.85rem;
      color: #6b7280;
    }

    .sort-dropdown {
      position: relative;
    }

    .sort-toggle {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.45rem 0.9rem;
      border-radius: 999px;
      border: 1px solid #e5e7eb;
      background: white;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
      font-size: 0.85rem;
      text-decoration: none;
      color: inherit;
    }

    .sort-label {
      color: #6b7280;
    }

    .sort-value {
      font-weight: 600;
      color: black;
    }

    .sort-chevron {
      margin-left: 0.15rem;
      font-size: 0.75rem;
    }

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

    .sort-menu.is-open {
      display: block;
    }

    .sort-option {
      width: 100%;
      text-align: left;
      padding: 0.5rem 1rem;
      border: none;
      background: transparent;
      font-size: 0.86rem;
      color: black;
      cursor: pointer;
      text-decoration: none;
      display: block;
    }

    .sort-option:hover {
      background: #f9fafb;
    }

    .sort-option.is-active {
      font-weight: 600;
      background: #f3f4f6;
    }

    .product-list-grid {
      display: grid;
      gap: 1.25rem;
      align-items: stretch;
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    @media (max-width: 1024px) {
      .product-list-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    @media (max-width: 768px) {
      .product-list-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 520px) {
      .product-list-grid {
        grid-template-columns: 1fr;
      }
    }

    .product-card {
      border-radius: 0.9rem;
      border: 1px solid #e5e7eb;
      overflow: hidden;
      background: #f9fafb;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      transition: box-shadow 0.2s ease, transform 0.2s ease,
        border-color 0.2s ease, background 0.2s ease;
      height: 100%;
      text-decoration: none;
      color: inherit;
    }

    .product-card:hover {
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
      transform: translateY(-2px);
      border-color: #d1d5db;
      background: white;
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

    .product-wishlist-btn {
      position: absolute;
      top: 0.55rem;
      right: 0.55rem;
      width: 30px;
      height: 30px;
      border-radius: 999px;
      border: 1px solid transparent;
      background: rgba(255, 255, 255, 0.96);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.3);
      transition: background 0.15s ease, transform 0.12s ease,
                  border-color 0.15s ease, box-shadow 0.15s ease;
      z-index: 2;
    }

    .product-wishlist-btn::before {
      content: "\2661";
      font-size: 15px;
      color: black;
      line-height: 1;
    }

    .product-wishlist-btn:hover {
      transform: translateY(-1px);
      background: white;
    }

    .product-wishlist-btn.is-active {
      background: white;
      border-color: black;
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.4);
    }

    .product-wishlist-btn.is-active::before {
      content: "\2665";
      color: #dc2626;
    }

    .product-card-body {
      padding: 0.6rem 0.7rem 0.8rem;
      font-size: 0.85rem;
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }

    .product-card-name {
      font-weight: 500;
      color: #111827;
    }

    .product-card-meta {
      font-size: 0.75rem;
      color: #6b7280;
    }

    .product-card-price {
      font-size: 0.9rem;
      color: #111827;
      margin-top: 0.1rem;
      font-weight: 600;
    }

    .product-card-old-price {
      font-size: 0.8rem;
      color: #9ca3af;
      text-decoration: line-through;
      margin-left: 5px;
    }

    .product-card-rating {
      color: #f59e0b;
      font-size: 0.8rem;
      margin-top: 2px;
    }

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

    #backToTop:hover {
      background: #111827;
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
  </style>
</head>

<body>
  <div class="page">
    <header>
      <div class="logo">
        <a href="index.php"><img src="logo.png" alt="EveryWear Logo"></a>
      </div>
      <nav class="header-nav">
        <a href="index.php">Home</a>
        <a href="products.php" class="active">Shop</a>
        <a href="about.php">About</a>
        <a href="orders.php">Orders</a>
      </nav>
      <div class="user-controls">
        <?php if ($isLoggedIn): ?>
          <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
          <a href="logout.php" class="logout-btn">Log Out</a>
        <?php else: ?>
          <a href="login.php" class="login-btn">Log In</a>
          <a href="create-account.php" class="create-btn">Create Account</a>
        <?php endif; ?>
      </div>
    </header>

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
          <span class="filter-search-icon" aria-hidden="true"></span>
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

        <button class="icon-btn wishlist-btn" id="wishlistToggle" aria-label="View wishlist"></button>

        <button class="icon-btn hamburger-btn" id="filterMenuToggle" aria-label="Browse categories" aria-expanded="false">
          <span></span>
        </button>
      </div>

      <div class="filter-menu" id="filterMenu" aria-hidden="true">
        <div class="filter-menu-header">
          <div class="filter-menu-title">Browse by category</div>
        </div>

        <div class="filter-menu-grid">
          <div>
            <button class="filter-menu-group-title" data-filter-group="Bottoms">
              Bottoms
            </button>
            <ul class="filter-menu-items">
              <li><button class="menu-item" data-category="Bottoms" data-type="Jeans">Jeans</button></li>
              <li><button class="menu-item" data-category="Bottoms" data-type="Shorts">Shorts</button></li>
              <li><button class="menu-item" data-category="Bottoms" data-type="Joggers">Joggers</button></li>
              <li><button class="menu-item" data-category="Bottoms" data-type="Jorts">Jorts</button></li>
            </ul>
          </div>

          <div>
            <button class="filter-menu-group-title" data-filter-group="Tops">
              Tops
            </button>
            <ul class="filter-menu-items">
              <li><button class="menu-item" data-category="Tops" data-type="T-shirt">T-shirts</button></li>
              <li><button class="menu-item" data-category="Tops" data-type="Shirt">Shirts</button></li>
              <li><button class="menu-item" data-category="Tops" data-type="Hoodie">Hoodies</button></li>
              <li><button class="menu-item" data-category="Tops" data-type="Jumper">Jumpers</button></li>
              <li><button class="menu-item" data-category="Tops" data-type="Vest">Vests</button></li>
            </ul>
          </div>

          <div>
            <button class="filter-menu-group-title" data-filter-group="Footwear">
              Footwear
            </button>
            <ul class="filter-menu-items">
              <li><button class="menu-item" data-category="Footwear" data-type="Crocs">Crocs</button></li>
              <li><button class="menu-item" data-category="Footwear" data-type="Sandals">Sandals</button></li>
              <li><button class="menu-item" data-category="Footwear" data-type="Boots">Boots</button></li>
            </ul>
          </div>

          <div>
            <button class="filter-menu-group-title" data-filter-group="Outerwear">
              Outerwear
            </button>
            <ul class="filter-menu-items">
              <li><button class="menu-item" data-category="Outerwear" data-type="Denim Jacket">Denim Jackets</button></li>
              <li><button class="menu-item" data-category="Outerwear" data-type="Cardigan">Cardigans</button></li>
              <li><button class="menu-item" data-category="Outerwear" data-type="Puffer Jacket">Puffer Jackets</button></li>
              <li><button class="menu-item" data-category="Outerwear" data-type="Jacket">Shell Jackets</button></li>
            </ul>
          </div>

          <div>
            <button class="filter-menu-group-title" data-filter-group="Accessories">
              Accessories
            </button>
            <ul class="filter-menu-items">
              <li><button class="menu-item" data-category="Accessories" data-type="Cap">Caps</button></li>
              <li><button class="menu-item" data-category="Accessories" data-type="Scarf">Scarves</button></li>
              <li><button class="menu-item" data-category="Accessories" data-type="Jewellery">Jewellery</button></li>
              <li><button class="menu-item" data-category="Accessories" data-type="Gloves">Gloves</button></li>
            </ul>
    </div>

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

        <div class="product-sort">
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
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #6b7280;">
                      No Image
                    </div>
                  <?php endif; ?>
                </div>
                <button type="button" class="product-wishlist-btn" data-id="<?php echo $product['product_id']; ?>"></button>
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
          <li><a href="#">Delivery &amp; Returns</a></li>
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

  <button id="backToTop" aria-label="Back to top">↑</button>

  <script>
    // Simplified JavaScript - removed type-related code
    const wishlistToggle = document.getElementById("wishlistToggle");
    const viewAllLink = document.getElementById("viewAllLink");
    const backToTopBtn = document.getElementById("backToTop");
    const searchForm = document.getElementById("searchForm");
    const searchInput = document.getElementById("searchInput");
    const sortToggle = document.getElementById("sortToggle");
    const sortMenu = document.getElementById("sortMenu");
    const menuToggleBtn = document.getElementById("filterMenuToggle");
    const filterMenu = document.getElementById("filterMenu");

    const wishlist = new Set();

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

    // Wishlist functionality
    function updateWishlistIcon() {
      wishlistToggle.classList.toggle("has-items", wishlist.size > 0);
    }

    // Attach wishlist handlers
    document.querySelectorAll(".product-wishlist-btn").forEach(function (btn) {
      btn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        const id = btn.dataset.id;
        if (wishlist.has(id)) {
          wishlist.delete(id);
          btn.classList.remove("is-active");
        } else {
          wishlist.add(id);
          btn.classList.add("is-active");
        }
        updateWishlistIcon();
      };
    });

    // Back to top button
    window.addEventListener("scroll", function () {
      if (window.scrollY > 350) {
        backToTopBtn.classList.add("is-visible");
      } else {
        backToTopBtn.classList.remove("is-visible");
      }
    });

    backToTopBtn.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  </script>
</body>
</html>
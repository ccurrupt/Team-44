<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
$cartJustUpdated = false;

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            if (isset($_GET['id'], $_GET['size'])) {
                $add_id = intval($_GET['id']);
                $size   = $_GET['size'];
                $name   = $_GET['name']  ?? 'Product';
                $price  = floatval($_GET['price']);
                $image  = $_GET['image'] ?? 'images/placeholder.jpg';
                $found  = false;
                foreach ($_SESSION['cart'] as $index => $item) {
                    if ($item['id'] == $add_id && $item['size'] == $size) {
                        $_SESSION['cart'][$index]['quantity']++;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'id' => $add_id, 'size' => $size,
                        'name' => $name, 'price' => $price, 'image' => $image,
                        'quantity' => 1
                    ];
                }
            }
            header('Location: productline.php?cart=open');
            exit();
        case 'update':
            if (isset($_GET['index'], $_GET['change'])) {
                $index  = intval($_GET['index']);
                $change = intval($_GET['change']);
                if (isset($_SESSION['cart'][$index])) {
                    $_SESSION['cart'][$index]['quantity'] += $change;
                    if ($_SESSION['cart'][$index]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$index]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                    }
                }
            }
            header('Location: productline.php?cart=open');
            exit();
        case 'remove':
            if (isset($_GET['index'])) {
                $index = intval($_GET['index']);
                if (isset($_SESSION['cart'][$index])) {
                    unset($_SESSION['cart'][$index]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
            }
            header('Location: productline.php?cart=open');
            exit();
    }
}

$cartCount = count($_SESSION['cart']);
$cartTotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>EveryWear</title>
<link rel="icon" type="image/png" href="images/logo.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css"
        rel="stylesheet"/>

  <style>

    body {
      margin: 0;
      padding: 0;
      font-family: "Inter", Arial, sans-serif;
      background-color: #f7f8fa;
      color: #333;
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
      height: 100%;
      min-width: 120px;
    }

    .logo-section img {
      height: 90px; 
    }

    .site-logo {
      height: 90px;
      width: auto;
      margin-left: 18px;
      margin-right: 0;
    }

    .logo-link {
      display: inline-flex;
      align-items: center;
      cursor: pointer;
      text-decoration: none;
      -webkit-tap-highlight-color: transparent;
    }

    .logo-link:focus { outline: none; }

    .logo-link:focus-visible {
      outline: 3px solid rgba(75,116,255,0.25);
      border-radius: 6px;
    }

    .logo-link:active { outline: none; }

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

    .nav-buttons {
      display: flex;
      gap: 20px;
      margin-left: 0;
      flex: 1 1 auto;
      min-width: 0;
    }

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
      transform: translateY(-2px) scale(1.07);
      outline: none;
    }

    .nav-buttons a.nav-button.active {
      background-color: #4b74ff;
      color: #ffffff;
    }

    .right-controls {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
      margin-left: auto;
      position: relative;
    }

    .right-default {
      display: flex;
      align-items: center;
      gap: 12px;
      transition: opacity 0.25s ease, transform 0.25s ease;
    }

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
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .create-btn:hover {
      background: linear-gradient(to right, #30CDF5, #00FAA0);
      color: #000;
      transform: translateY(-2px) scale(1.07);
    }

    .create-btn:active { transform: translateY(0); }

    .btn-icon {
      width: 18px;
      height: 18px;
      object-fit: contain;
    }

    .login-btn,
    .create-btn {
      text-decoration: none;
    }

    .icon-link {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 36px;
      width: 36px;
      cursor: pointer;
      text-decoration: none;
      transition: transform 0.25s ease, opacity 0.25s ease;
      position: relative;
    }

    .icon-link:hover {
      transform: scale(1.15);
      opacity: 0.9;
    }

    .nav-icon {
      width: 22px;
      height: 22px;
      object-fit: contain;
      pointer-events: none;
    }

    .cart-count-badge {
      position: absolute;
      top: -4px;
      right: -4px;
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

    .cart-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.4);
      z-index: 99;
    }
    .cart-overlay.active { display: block; }

    .cart-sidebar {
      position: fixed;
      top: 0;
      right: -420px;
      width: 400px;
      height: 100%;
      background: #fff;
      box-shadow: -2px 0 10px rgba(0,0,0,0.15);
      padding: 60px 20px 20px;
      z-index: 100;
      overflow-y: auto;
      transition: right 0.35s ease;
      display: flex;
      flex-direction: column;
    }
    .cart-sidebar.active { right: 0; }
    .cart-sidebar h2 {
      text-align: center;
      font-size: 22px;
      margin-bottom: 20px;
    }
    #cart-close {
      position: absolute;
      top: 18px;
      right: 18px;
      font-size: 28px;
      cursor: pointer;
      color: #333;
    }
    #cart-close:hover { color: #000; }

    .cart-items-list { flex: 1; overflow-y: auto; }

    .cart-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }
    .cart-item img {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 6px;
      flex-shrink: 0;
    }
    .cart-item-details { flex: 1; font-size: 13px; }
    .cart-item-details strong { display: block; margin-bottom: 4px; }
    .cart-item-details span { color: #666; }

    .quantity-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
    }
    .qty-btn {
      width: 26px;
      height: 26px;
      border: 1px solid #ccc;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 16px;
      user-select: none;
    }
    .qty-btn:hover { background: #f0f0f0; }

    .cart-remove { cursor: pointer; color: #999; font-size: 18px; }
    .cart-remove:hover { color: #e35f26; }

    .cart-footer {
      border-top: 1px solid #eee;
      padding-top: 16px;
      margin-top: 10px;
    }
    .cart-total {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 12px;
      text-align: right;
    }
    .btn-checkout {
      display: block;
      width: 100%;
      padding: 12px;
      background: #111827;
      color: white;
      text-align: center;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-checkout:hover { background: #000; }
	
                         
        .btn-view-cart {
      display: block;
      width: 100%;
      padding: 12px;
      background: #fff;
      color: #111827;
      text-align: center;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      border: 2px solid #111827;
      transition: background 0.2s, color 0.2s;
      margin-bottom: 8px;
    }
    .btn-view-cart:hover {
      background: #111827;
      color: #fff;
    }                     
                         
                         
    @media (max-width: 768px) {
      .site-logo, .logo-section img {
        height: 64px;
      }
      .navbar {
        height: auto;
      }
      .logo-section { min-width: 140px; }
      .nav-buttons {
        display: none;
      }
    }

    .page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1.5rem 1.25rem 2.5rem;
      background: transparent;
      min-height: 100vh;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .filter-row {
      position: relative;
      margin: 1.5rem auto 1.8rem;
      padding: 0.9rem 1.2rem;
      border-radius: 0.9rem;
      background: white;
      border: 1px solid white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.25rem;
      flex-wrap: wrap;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }

    .filter-breadcrumb {
      font-size: 0.8rem;
      color: gray;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      white-space: nowrap;
    }

    .filter-breadcrumb a {
      color: gray;
    }

    .filter-breadcrumb span.separator {
      color: lightgray;
    }

    .filter-breadcrumb-current {
      color: dimgray;
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
      border: 1px solid lightgray;
    }

    .filter-search-icon {
      width: 14px;
      height: 14px;
      border-radius: 999px;
      border: 2px solid darkgray;
      position: relative;
      flex-shrink: 0;
    }

    .filter-search-icon::after {
      content: "";
      position: absolute;
      width: 7px;
      height: 2px;
      background: darkgray;
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
      color: gray;
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
      background: whitesmoke;
      color: black;
    }

    .wishlist-btn::before {
      content: "\2661";
      font-size: 17px;
      line-height: 1;
    }

    .wishlist-btn:hover {
      background: gainsboro;
      transform: translateY(-1px);
    }

    .wishlist-btn.has-items {
      background: gainsboro;
      box-shadow: 0 10px 26px rgba(15, 23, 42, 0.28);
    }

    .hamburger-btn {
      background: black;
      color: white;
    }

    .hamburger-btn:hover {
      background: black;
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

    .hamburger-btn span::before { top: -5px; }
    .hamburger-btn span::after  { bottom: -5px; }

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
      border: 1px solid white;
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
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.6rem 1.25rem;
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
      color: gray;
    }

    .sort-dropdown { position: relative; }

    .sort-toggle {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.45rem 0.9rem;
      border-radius: 999px;
      border: 1px solid white;
      background: white;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
      font-size: 0.85rem;
    }

    .sort-label { color: gray; }

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
      border: 1px solid white;
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
    }

    .sort-option:hover {
      background: whitesmoke;
    }

    .sort-option.is-active {
      font-weight: 600;
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
      border: 1px solid white;
      overflow: hidden;
      background: whitesmoke;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      transition: box-shadow 0.2s ease, transform 0.2s ease,
                  border-color 0.2s ease, background 0.2s ease;
      height: 100%;
    }

    .product-card:hover {
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
      transform: translateY(-2px);
      border-color: lightgray;
      background: white;
    }

    .product-card-img {
      position: relative;
      background: lightgray;
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
    }

    .product-wishlist-btn::before {
      content: "\2661";
      font-size: 25px;
      color: black;
      line-height: 1;
    }
    
    .product-wishlist-btn.is-active::before {
      content: "\2665";
      color: red;
      font-size: 25px;
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

    .product-card-body {
      padding: 0.6rem 0.7rem 0.8rem;
      font-size: 0.85rem;
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
      height: 100%;
    }

    .product-card-name {
      font-weight: 500;
    }

    .product-card-meta {
      font-size: 0.75rem;
      color: gray;
    }

    .product-card-footer {
      margin-top: auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      padding-top: 0.25rem;
    }

    .product-card-price {
      font-size: 0.8rem;
      color: dimgray;
    }

    .product-card-add-cart {
      border: none;
      background: #111827;
      color: white;
      width: 34px;
      height: 34px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.3);
      transition: background 0.15s ease, transform 0.12s ease;
      flex-shrink: 0;
    }

    .product-card-add-cart i {
      font-size: 20px; 
    }

    .product-card-add-cart:hover {
      background: black;
      transform: translateY(-1px);
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
      background: black;
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


    /* ??? DARK MODE TOGGLE BUTTON ??? */
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

    /* ??? DARK MODE ??? */
    body.dark { background-color: #121212; color: #e0e0e0; }
    body.dark .navbar { background: #1a1a1a; box-shadow: 0 1px 8px rgba(0,0,0,0.4); }
    body.dark .nav-buttons a.nav-button { background: #2a2a2a; color: #e0e0e0; }
    body.dark .filter-row { background: #1e1e1e; border-color: #2a2a2a; }
    body.dark .filter-search { background: #2a2a2a; border-color: #444; }
    body.dark .filter-search input { color: #e0e0e0; }
    body.dark .filter-breadcrumb,
    body.dark .filter-breadcrumb a { color: #aaa; }
    body.dark .filter-breadcrumb-current { color: #ccc; }
    body.dark .icon-btn { background: #2a2a2a; color: #e0e0e0; }
    body.dark .product-wishlist-btn::before { color: #e0e0e0; }
    body.dark .product-card { background: #1e1e1e; border-color: #2a2a2a; }
    body.dark .product-card-name { color: #f0f0f0; }
    body.dark .product-card-meta,
    body.dark .product-card-price { color: #aaa; }
    body.dark .cart-sidebar { background: #1a1a1a; color: #e0e0e0; }
    body.dark .cart-sidebar h2 { color: #f0f0f0; }
    body.dark #cart-close { color: #ccc; }
    body.dark .cart-item { border-bottom-color: #333; }
    body.dark .cart-item-details span { color: #aaa; }
    body.dark .qty-btn { border-color: #555; background: #2a2a2a; color: #ddd; }
    body.dark .cart-footer { border-top-color: #333; }
    body.dark .cart-total { color: #f0f0f0; }

  </style>
</head>

<body>
  <div class="navbar">
    <div class="logo-section">
      <a href="index.php" class="logo-link" aria-label="Go to homepage">
        <img src="images/logo.png"
             loading="eager"
             alt="EveryWear Logo"
             width="120"
             height="90"
             class="site-logo">
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
            <span class="welcome-msg">Hi <?php echo htmlspecialchars($userName); ?>!</span>
            <a href="logout.php" class="login-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Log in</a>
            <a href="create-account.php" class="create-btn">
              <img src="images/account.png" alt="" class="btn-icon">
              Create Account
            </a>
        <?php endif; ?>

        <a href="#" id="cartToggle" class="icon-link" aria-label="Open basket">
          <img src="images/basket.png" alt="Basket" class="nav-icon">
          <?php if ($cartCount > 0): ?>
            <span class="cart-count-badge"><?php echo $cartCount; ?></span>
          <?php endif; ?>
        </a>

        <!-- DARK MODE TOGGLE -->
        <button type="button" id="themeToggle" class="theme-toggle-btn" aria-label="Toggle dark mode">
          <span id="themeIcon">&#127769;</span>
        </button>
      </div>
    </div>
  </div>

<!-- CART OVERLAY -->
<div class="cart-overlay" id="cartOverlay"></div>

<!-- CART SIDEBAR -->
<div class="cart-sidebar" id="cartSidebar">
  <h2>Your Cart</h2>
  <i id="cart-close" class="ri-close-circle-fill"></i>

  <div class="cart-items-list">
    <?php if (empty($_SESSION['cart'])): ?>
      <p style="color:#999;margin-top:20px;text-align:center;">Your cart is empty</p>
    <?php else: ?>
      <?php foreach ($_SESSION['cart'] as $index => $item): ?>
        <div class="cart-item">
          <img src="<?php echo htmlspecialchars($item['image']); ?>"
               alt="<?php echo htmlspecialchars($item['name']); ?>">
          <div class="cart-item-details">
            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
            <span>Size: <?php echo htmlspecialchars($item['size']); ?></span><br>
            <span>&pound;<?php echo number_format($item['price'], 2); ?></span>
            <div class="quantity-controls">
              <a href="productline.php?action=update&index=<?php echo $index; ?>&change=-1" class="qty-btn">&minus;</a>
              <span><?php echo $item['quantity']; ?></span>
              <a href="productline.php?action=update&index=<?php echo $index; ?>&change=1" class="qty-btn">+</a>
            </div>
          </div>
          <a href="productline.php?action=remove&index=<?php echo $index; ?>" class="cart-remove">
            <i class="ri-delete-bin-line"></i>
          </a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

    <?php if (!empty($_SESSION['cart'])): ?>
  <div class="cart-footer">
    <div class="cart-total">Total: &pound;<?php echo number_format($cartTotal, 2); ?></div>
    <a href="cart.php" class="btn-view-cart">View Cart</a>
    <a href="checkout.php" class="btn-checkout">Go to Checkout</a>
  </div>
  <?php endif; ?>
</div>

  <div class="page">
    <section class="filter-row">
      <div class="filter-breadcrumb">
        <a href="#">Home</a>
        <span class="separator">/</span>
        <a href="#" id="viewAllLink">View All</a>
        <span class="separator">/</span>
        <span id="crumbCategory" class="filter-breadcrumb-current">All products</span>
      </div>

      <div class="filter-controls">
        <div class="filter-search">
          <span class="filter-search-icon" aria-hidden="true"></span>
          <input
            id="searchInput"
            type="text"
            placeholder="Search EveryWear"
          />
        </div>

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
        <h2 class="product-list-heading">All styles</h2>

        <div class="product-sort">
          <div class="sort-dropdown" id="sortDropdown">
            <button type="button" class="sort-toggle" id="sortToggle">
              <span class="sort-label">Sort by :</span>
              <span class="sort-value" id="sortCurrent">Recommended</span>
              
            </button>
            <div class="sort-menu" id="sortMenu">
              <button class="sort-option is-active" data-sort="recommended">Recommended</button>
              <button class="sort-option" data-sort="new">What's New</button>
              <button class="sort-option" data-sort="popular">Popularity</button>
              <button class="sort-option" data-sort="discount">Better Discount</button>
              <button class="sort-option" data-sort="price-desc">Price: High to Low</button>
              <button class="sort-option" data-sort="price-asc">Price: Low to High</button>
              <button class="sort-option" data-sort="rating">Customer Rating</button>
            </div>
          </div>
        </div>
      </div>

      <div id="productList" class="product-list-grid"></div>
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
          <li><a href="delivery.php">Delivery &amp; Returns</a></li>
          <li><a href="#">10% Student Discount</a></li>
          <li><a href="FAQ.php">FAQs</a></li>
          <li><a href="#">My Account</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Join Now</h4>
        <ul>
          <li><a href="membership.php">Become a member today and get exclusive benefits!</a></li>
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

  <button id="backToTop" aria-label="Back to top">?</button>

  <script>
    const products = [
      { id: "3", category: "Bottoms", type: "Jeans",
        name: "Two-Toned Unisex Panel Jeans",
        price: "\u00A326.00", thumbnail: "images/jeans1_main.jpg",
        isNew: false, popularity: 920, rating: 4.6, discount: 25 },
      { id: "9", category: "Bottoms", type: "Shorts",
        name: "Light Wash Baggy Shorts",
        price: "\u00A324.00", thumbnail: "images/shorts1_main.jpg",
        isNew: true, popularity: 780, rating: 4.5, discount: 30 },
      { id: "7", category: "Bottoms", type: "Joggers",
        name: "Sand Fleece Joggers",
        price: "\u00A322.00", thumbnail: "images/joggers1_main.jpg",
        isNew: false, popularity: 840, rating: 4.7, discount: 18 },
      { id: "16", category: "Bottoms", type: "Jorts",
        name: "Light-wash Denim Jorts",
        price: "\u00A321.00", thumbnail: "images/jorts1_main.jpg",
        isNew: true, popularity: 610, rating: 4.3, discount: 15 },

      { id: "1", category: "Tops", type: "T-shirt",
        name: "Boxy Logo T-Shirt",
        price: "\u00A314.00", thumbnail: "images/tshirts1_main.jpg",
        isNew: false, popularity: 1100, rating: 4.8, discount: 30 },
      { id: "8", category: "Tops", type: "Shirt",
        name: "Oversized Poplin Shirt",
        price: "\u00A326.00", thumbnail: "images/shirts1_main.jpg",
        isNew: true, popularity: 830, rating: 4.6, discount: 22 },
      { id: "2", category: "Tops", type: "Hoodie",
        name: "Heavyweight Logo Hoodie",
        price: "\u00A332.00", thumbnail: "images/hoodies1_main.jpg",
        isNew: false, popularity: 970, rating: 4.7, discount: 28 },
      { id: "15", category: "Tops", type: "Jumper",
        name: "Ribbed Knit Jumper",
        price: "\u00A329.00", thumbnail: "images/jumper1_main.jpg",
        isNew: false, popularity: 650, rating: 4.4, discount: 20 },
      { id: "19", category: "Tops", type: "Vest",
        name: "Racer Rib Vest",
        price: "\u00A311.00", thumbnail: "images/vest1_main.jpg",
        isNew: true, popularity: 540, rating: 4.2, discount: 18 },

      { id: "5", category: "Footwear", type: "Crocs",
        name: "Chunky Platform Crocs",
        price: "\u00A339.00", thumbnail: "images/crocs1_main.jpg",
        isNew: false, popularity: 880, rating: 4.5, discount: 35 },
      { id: "12", category: "Footwear", type: "Sandals",
        name: "Minimal Strappy Sandals",
        price: "\u00A325.00", thumbnail: "images/sandals1_main.jpg",
        isNew: true, popularity: 720, rating: 4.4, discount: 27 },
      { id: "14", category: "Footwear", type: "Boots",
        name: "Suede Ankle Boots",
        price: "\u00A349.00", thumbnail: "images/boots1_main.jpg",
        isNew: false, popularity: 640, rating: 4.6, discount: 32 },

      { id: "11", category: "Outerwear", type: "Denim Jacket",
        name: "Cropped Denim Jacket",
        price: "\u00A342.00", thumbnail: "images/denims1_main.jpg",
        isNew: true, popularity: 730, rating: 4.6, discount: 30 },
      { id: "13", category: "Outerwear", type: "Cardigan",
        name: "Soft Knit Cardigan",
        price: "\u00A337.00", thumbnail: "images/cardigen1_main.jpg",
        isNew: false, popularity: 690, rating: 4.5, discount: 24 },
      { id: "6", category: "Outerwear", type: "Puffer Jacket",
        name: "Short Puffer Jacket",
        price: "\u00A355.00", thumbnail: "images/pufferjacket1_main.jpg",
        isNew: false, popularity: 810, rating: 4.7, discount: 33 },
      { id: "10", category: "Outerwear", type: "Jacket",
        name: "Waterproof Shell Jacket",
        price: "\u00A352.00", thumbnail: "images/waterproofjacket1_main.jpg",
        isNew: true, popularity: 760, rating: 4.5, discount: 29 },

      { id: "17", category: "Accessories", type: "Cap",
        name: "Simple Black Cap",
        price: "\u00A313.00", thumbnail: "images/cap1_main.jpg",
        isNew: false, popularity: 540, rating: 4.3, discount: 20 },
      { id: "18", category: "Accessories", type: "Scarf",
        name: "Brushed Scarf",
        price: "\u00A317.00", thumbnail: "images/scarf1_main.jpg",
        isNew: true, popularity: 510, rating: 4.4, discount: 25 },
      { id: "20", category: "Accessories", type: "Jewellery",
        name: "Minimal Cord Bracelet Set",
        price: "\u00A310.00", thumbnail: "images/bracelet1_main.jpg",
        isNew: false, popularity: 480, rating: 4.2, discount: 30 },
      { id: "21", category: "Accessories", type: "Gloves",
        name: "Winter Gloves",
        price: "\u00A312.00", thumbnail: "images/gloves1_main.jpg",
        isNew: true, popularity: 450, rating: 4.1, discount: 18 }
    ];

    const productListEl   = document.getElementById("productList");
    const searchInput     = document.getElementById("searchInput");
    const crumbCategoryEl = document.getElementById("crumbCategory");

    const menuToggleBtn = document.getElementById("filterMenuToggle");
    const filterMenu    = document.getElementById("filterMenu");
    const menuGroups    = document.querySelectorAll(".filter-menu-group-title");
    const menuItems     = document.querySelectorAll(".menu-item");

    const wishlistToggle = document.getElementById("wishlistToggle");
    const viewAllLink    = document.getElementById("viewAllLink");
    const backToTopBtn   = document.getElementById("backToTop");

    const sortToggle   = document.getElementById("sortToggle");
    const sortMenu     = document.getElementById("sortMenu");
    const sortCurrent  = document.getElementById("sortCurrent");
    const sortOptions  = sortMenu.querySelectorAll(".sort-option");

    const wishlist = new Set(
      JSON.parse(localStorage.getItem("wishlist")) || []
    );

    function saveWishlist() {
      localStorage.setItem("wishlist", JSON.stringify([...wishlist]));
      if (wishlist.size > 0) {
        wishlistToggle.classList.add("has-items");
      } else {
        wishlistToggle.classList.remove("has-items");
      }
    }

    const cartToggle  = document.getElementById("cartToggle");
    const cartSidebar = document.getElementById("cartSidebar");
    const cartOverlay = document.getElementById("cartOverlay");
    const cartClose   = document.getElementById("cart-close");

    function openCart()  { cartSidebar.classList.add("active");    cartOverlay.classList.add("active");    document.body.style.overflow = "hidden"; }
    function closeCart() { cartSidebar.classList.remove("active"); cartOverlay.classList.remove("active"); document.body.style.overflow = ""; }

    if (cartToggle)  cartToggle.addEventListener("click",  e => { e.preventDefault(); openCart(); });
    if (cartClose)   cartClose.addEventListener("click",   closeCart);
    if (cartOverlay) cartOverlay.addEventListener("click", closeCart);

    let activeCategory = "All";
    let activeType     = "";
    let searchQuery    = "";
    let sortMode       = "recommended";

    function getPriceNumber(p) {
      return parseFloat(p.price.replace(/[^\d.]/g, ""));
    }

    function renderProductGrid(list) {
      productListEl.innerHTML = "";
      if (!list.length) {
        productListEl.innerHTML =
          "<p style='font-size:0.9rem;color:gray;'>No styles found. Try another search or category.</p>";
        return;
      }

      list.forEach((p) => {
        const card = document.createElement("article");
        card.className = "product-card";
        card.dataset.category = p.category;
        card.dataset.type = p.type;
        card.dataset.id = p.id;

        const imgWrap = document.createElement("div");
        imgWrap.className = "product-card-img";

        const wishBtn = document.createElement("button");
        wishBtn.type = "button";
        wishBtn.className = "product-wishlist-btn";
        wishBtn.dataset.id = p.id;
        if (wishlist.has(p.id)) {
          wishBtn.classList.add("is-active");
        }
        wishBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          const productId = wishBtn.dataset.id;
          if (wishlist.has(productId)) {
            wishlist.delete(productId);
            wishBtn.classList.remove("is-active");
          } else {
            wishlist.add(productId);
            wishBtn.classList.add("is-active");
          }
          saveWishlist();
        });

        const inner = document.createElement("div");
        inner.className = "product-card-img-inner";

        const img = document.createElement("img");
        img.src = p.thumbnail;
        img.alt = p.name;

        inner.appendChild(img);
        imgWrap.appendChild(inner);
        imgWrap.appendChild(wishBtn);

        const body = document.createElement("div");
        body.className = "product-card-body";

        const name = document.createElement("div");
        name.className = "product-card-name";
        name.textContent = p.name;

        const meta = document.createElement("div");
        meta.className = "product-card-meta";
        meta.textContent =
          "Unisex \u00B7 " + p.category + " \u00B7 " + p.type + " \u00B7 \u2605" + p.rating.toFixed(1);

        const footer = document.createElement("div");
        footer.className = "product-card-footer";

        const price = document.createElement("div");
        price.className = "product-card-price";
        price.textContent = p.price;

        const cartBtn = document.createElement("button");
        cartBtn.type = "button";
        cartBtn.className = "product-card-add-cart";
        cartBtn.innerHTML = '<i class="ri-shopping-cart-2-line"></i>';

        footer.appendChild(price);
        footer.appendChild(cartBtn);

        body.appendChild(name);
        body.appendChild(meta);
        body.appendChild(footer);

        card.appendChild(imgWrap);
        card.appendChild(body);

        // Navigate to product detail page on card click (but not when cart/wishlist buttons clicked)
        card.addEventListener("click", function (e) {
          if (e.target.closest(".product-card-add-cart") || e.target.closest(".product-wishlist-btn")) return;
          window.location.href = "productdescrip.php?id=" + p.id;
        });

        productListEl.appendChild(card);
      });

      attachCartHandlers();
    }

    function applyFilters(scrollToFirst = false) {
      const q = searchQuery.trim().toLowerCase();

      const filtered = products.filter((p) => {
        if (activeCategory !== "All" && p.category !== activeCategory) return false;
        if (activeType && p.type !== activeType) return false;

        if (!q) return true;
        const haystack = (p.name + " " + p.category + " " + p.type).toLowerCase();
        return haystack.includes(q);
      });

      const sorted = filtered.slice();

      switch (sortMode) {
        case "price-asc":
          sorted.sort((a, b) => getPriceNumber(a) - getPriceNumber(b));
          break;
        case "price-desc":
          sorted.sort((a, b) => getPriceNumber(b) - getPriceNumber(a));
          break;
        case "rating":
          sorted.sort((a, b) => b.rating - a.rating);
          break;
        case "popular":
          sorted.sort((a, b) => b.popularity - a.popularity);
          break;
        case "discount":
          sorted.sort((a, b) => b.discount - a.discount);
          break;
        case "new":
          sorted.sort((a, b) => {
            if (a.isNew === b.isNew) return b.popularity - a.popularity;
            return a.isNew ? -1 : 1;
          });
          break;
        case "recommended":
        default:
          sorted.sort((a, b) => {
            const scoreA = a.rating * 50 + a.popularity * 0.5 + a.discount * 2;
            const scoreB = b.rating * 50 + b.popularity * 0.5 + b.discount * 2;
            return scoreB - scoreA;
          });
          break;
      }

      renderProductGrid(sorted);

      if (scrollToFirst && sorted.length) {
        const firstCard = productListEl.querySelector("article.product-card");
        if (firstCard) {
          firstCard.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }
    }

    function updateBreadcrumb() {
      if (activeCategory === "All") {
        crumbCategoryEl.textContent = "All products";
      } else if (activeType) {
        crumbCategoryEl.textContent = activeCategory + " – " + activeType;
      } else {
        crumbCategoryEl.textContent = activeCategory;
      }
    }

    function resetToAll(scroll = true) {
      activeCategory = "All";
      activeType = "";
      searchQuery = "";
      searchInput.value = "";
      sortMode = "recommended";
      sortCurrent.textContent = "Recommended";
      sortOptions.forEach(btn => btn.classList.remove("is-active"));
      sortMenu.querySelector('[data-sort="recommended"]').classList.add("is-active");

      menuGroups.forEach(b => b.classList.remove("active"));

      updateBreadcrumb();
      applyFilters(scroll);
    }

    searchInput.addEventListener("input", function (e) {
      searchQuery = e.target.value;
      applyFilters();
    });

    sortToggle.addEventListener("click", function () {
      sortMenu.classList.toggle("is-open");
    });

    sortOptions.forEach(function (btn) {
      btn.addEventListener("click", function () {
        sortOptions.forEach(b => b.classList.remove("is-active"));
        btn.classList.add("is-active");

        sortMode = btn.dataset.sort || "recommended";
        sortCurrent.textContent = btn.textContent.trim();
        sortMenu.classList.remove("is-open");

        applyFilters(true);
      });
    });

    document.addEventListener("click", function (e) {
      if (!sortMenu.contains(e.target) && !sortToggle.contains(e.target)) {
        sortMenu.classList.remove("is-open");
      }
    });

    viewAllLink.addEventListener("click", function (e) {
      e.preventDefault();
      resetToAll(true);
    });

    function toggleMenu(force) {
      const willOpen = typeof force === "boolean"
        ? force
        : !filterMenu.classList.contains("is-open");

      filterMenu.classList.toggle("is-open", willOpen);
      menuToggleBtn.classList.toggle("is-open", willOpen);
      menuToggleBtn.setAttribute("aria-expanded", String(willOpen));
      filterMenu.setAttribute("aria-hidden", String(!willOpen));
    }

    menuToggleBtn.addEventListener("click", function () {
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
        if (cartSidebar && cartSidebar.classList.contains("active")) {
          closeCart();
        }
      }
    });

    menuGroups.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const group = btn.dataset.filterGroup;
        activeCategory = group;
        activeType = "";

        menuGroups.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        updateBreadcrumb();
        applyFilters(true);
        toggleMenu(false);
      });
    });

    menuItems.forEach(function (item) {
      item.addEventListener("click", function () {
        activeCategory = item.dataset.category || "All";
        activeType = item.dataset.type || "";

        menuGroups.forEach(function (b) {
          const group = b.dataset.filterGroup;
          b.classList.toggle("active", group === activeCategory);
        });

        updateBreadcrumb();
        applyFilters(true);
        toggleMenu(false);
      });
    });

    function updateWishlistIcon() {
      wishlistToggle.classList.toggle("has-items", wishlist.size > 0);
    }
    wishlistToggle.addEventListener("click", () => {
      if (wishlist.size === 0) {
        alert("Your wishlist is empty");
        return;
      }
      
      const wishlistedProducts = products.filter(p => wishlist.has(p.id));
      crumbCategoryEl.textContent = "Wishlist";
      renderProductGrid(wishlistedProducts);
      window.scrollTo({ top: 0, behavior: "smooth" });
    });

    wishlistToggle.addEventListener("click", function () {
      productListEl.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    // Single initialisation — runs after DOM is ready
    saveWishlist();
    applyFilters();

    /* ?? DARK MODE ?? */
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

    function attachCartHandlers() {
      document.querySelectorAll(".product-card-add-cart").forEach(function (btn) {
        btn.onclick = function (e) {
          e.stopPropagation();
          const card = btn.closest(".product-card");
          if (!card) return;
          const nameEl    = card.querySelector(".product-card-name");
          const priceEl   = card.querySelector(".product-card-price");
          const imgEl     = card.querySelector(".product-card-img-inner img");
          const productId = card.dataset.id;
          window.location.href =
            "productdescrip.php?" +
            "action=add" +
            "&id=" + encodeURIComponent(productId) +
            "&size=M" +
            "&name=" + encodeURIComponent(nameEl ? nameEl.textContent : "") +
            "&price=" + encodeURIComponent(priceEl ? priceEl.textContent.replace(/[^\d.]/g, "") : "0") +
            "&image=" + encodeURIComponent(imgEl ? imgEl.src : "");
        };
      });
    }
    
    
    // --- Set initial filter based on the URL (for deep linking and correct redirect from homepage) ---
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const urlCategory = urlParams.get('category');
    if (urlCategory) {
        activeCategory = urlCategory;
        activeType = "";
        // Optional: remove any search from previous sessions
        searchQuery = "";
        if (searchInput) searchInput.value = "";
        updateBreadcrumb();
        applyFilters(true);
        // Optionally: highlight the right menu, if you want
        if (menuGroups.length) {
            menuGroups.forEach(b => b.classList.toggle("active", b.dataset.filterGroup === urlCategory));
        }
    }
});
    
    
    
        // Auto-open cart if redirected after cart action
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cart') === 'open') {
      openCart();
      // Clean the URL so refreshing doesn't re-open
      const cleanUrl = window.location.pathname;
      window.history.replaceState({}, '', cleanUrl);
    }

  </script>
  
  <?php include 'chatbot-widget.php'; ?>
</body>
</html>

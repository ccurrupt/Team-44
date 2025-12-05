<?php
require_once 'dbconfig.php'; // gets the db connection

// check if user is logged in, if not send them to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=login_required");
    exit();
}

// only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit();
}

// get data from the form that was submitted
$userId    = (int) $_SESSION['user_id']; // user id from session
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0; // product id
$quantity  = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1; // how many they want
$size      = isset($_POST['size']) ? trim($_POST['size']) : ''; // size they selected

// basic validation - make sure we have proper values
if ($productId <= 0 || $quantity <= 0) {
    header("Location: products.php?error=invalid_input");
    exit();
}

// if no size selected, use medium as default
if (empty($size)) {
    $size = 'M'; // default to medium
}

try {
    // check if the product actually exists in db
    $check = $pdo->prepare("
        SELECT product_id, price 
        FROM Products 
        WHERE product_id = :pid
    ");
    $check->execute([':pid' => $productId]);
    $product = $check->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: products.php?error=product_not_found");
        exit();
    }
    
    // calculate total price for this item
    $price = (float)$product['price'];
    $totalPrice = $price * $quantity; // price * quantity

    // find user's current cart or make a new one
    $cartStmt = $pdo->prepare("
        SELECT cart_id
        FROM Cart
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $cartStmt->execute([':uid' => $userId]);
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $cartId = (int) $cart['cart_id']; // use existing cart
    } else {
        // make a new cart for user
        $insertCart = $pdo->prepare("
            INSERT INTO Cart (user_id, created_at)
            VALUES (:uid, NOW())
        ");
        $insertCart->execute([':uid' => $userId]);
        $cartId = (int) $pdo->lastInsertId(); // get the new cart id
    }

    // check if this product with same size is already in cart
    // if same product + same size, just update quantity
    // if different size, add as new item
    $existing = $pdo->prepare("
        SELECT cart_item_id, quantity, total_price
        FROM Cart_Items
        WHERE cart_id = :cid AND product_id = :pid AND size = :size
    ");
    $existing->execute([
        ':cid'  => $cartId,
        ':pid'  => $productId,
        ':size' => $size
    ]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // item already in cart, just add more quantity
        $newQty = (int) $row['quantity'] + $quantity;
        $newTotalPrice = $price * $newQty; // recalc total

        $update = $pdo->prepare("
            UPDATE Cart_Items
            SET quantity = :qty, total_price = :total_price
            WHERE cart_item_id = :itemId
        ");
        $update->execute([
            ':qty'        => $newQty,
            ':total_price' => $newTotalPrice,
            ':itemId'     => (int) $row['cart_item_id']
        ]);
    } else {
        // new item, add to cart
        $insertItem = $pdo->prepare("
            INSERT INTO Cart_Items (cart_id, product_id, quantity, total_price, size, created_at)
            VALUES (:cid, :pid, :qty, :total_price, :size, NOW())
        ");
        $insertItem->execute([
            ':cid'         => $cartId,
            ':pid'         => $productId,
            ':qty'         => $quantity,
            ':total_price' => $totalPrice,
            ':size'        => $size
        ]);
    }

    // all good, go to cart page
    header("Location: cart.php?added=1");
    exit();

} catch (PDOException $e) {
    // if db error, log it and show error page
    error_log("Add to cart error: " . $e->getMessage());
    header("Location: products.php?error=server_error");
    exit();
}
?>
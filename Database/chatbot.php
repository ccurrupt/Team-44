<?php
header("Content-Type: application/json");

$message = strtolower($_POST['message'] ?? "");

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

$message = strtolower($_POST['message'] ?? "");

// Default reply
$response = "I'm not sure yet. A support agent will reply soon.";

// GREETINGS
if (str_contains($message, "hi") || str_contains($message, "hello")) {
    $response = "Hi, Welcome to EveryWear! How can I help you today?";
}

// PRODUCT QUESTIONS
elseif (
    str_contains($message, "where can i find") ||
    str_contains($message, "show me") ||
    str_contains($message, "details") ||
    str_contains($message, "sizes") ||
    str_contains($message, "available")
) {
    // Extract product keyword (basic approach)
    preg_match("/t-shirt|hoodie|jeans|joggers/", $message, $matches);
    
    if ($matches) {
        $keyword = $matches[0];
        $stmt = $mysqli->prepare("SELECT name, sizes, colors, stock, url FROM products WHERE LOWER(name) LIKE ?");
        $search = "%$keyword%";
        $stmt->bind_param("s", $search);
        $stmt->execute();
        $stmt->bind_result($name, $sizes, $colors, $stock, $url);
        
        if ($stmt->fetch()) {
            $response = "🛍 Product: $name\nAvailable sizes: $sizes\nColors: $colors\nStock: $stock\nCheck it here: $url";
        } else {
            $response = "Sorry, we couldn't find a product matching '$keyword'.";
        }
        $stmt->close();
    } else {
        $response = "Can you specify the product name?";
    }
}

// ORDER QUESTIONS
elseif (
    str_contains($message, "delivery") ||
    str_contains($message, "arrive") ||
    str_contains($message, "shipping") ||
    str_contains($message, "how long")
) {
    $response = "Orders usually arrive within 3–5 working days in the UK.";
}

echo json_encode(["reply"=>$response]);

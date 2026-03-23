<?php
/**
 * chatbot.php — EveryWear AI Support Chatbot Backend
 *
 * Receives POST messages from the chat widget, matches intent,
 * queries the Products table when needed, and returns a JSON reply.
 *
 * @version 2.0
 */

header("Content-Type: application/json");

// ── Only accept POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["reply" => "Method not allowed."]);
    exit();
}

// ── Database connection (PDO) ──
$host     = 'localhost';
$dbname   = 'cs2team44_db';
$username = 'cs2team44';
$password = 'wpRwMNcuA4uajOG92dzRRqbhb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["reply" => "Sorry, I'm having trouble connecting right now. Please try again later."]);
    exit();
}

// ── Get and sanitise user message ──
$message = strtolower(trim($_POST['message'] ?? ''));

if ($message === '') {
    echo json_encode(["reply" => "Please type a message so I can help you!"]);
    exit();
}

// ── Product keyword list (matches your Products table) ──
$productKeywords = [
    't-shirt','tshirt','t shirt',
    'hoodie','hoody',
    'jeans','joggers','shorts','jorts',
    'jacket','puffer','cardigan','vest','shirt','jumper',
    'boots','sandals','crocs',
    'cap','scarf','bracelet','gloves',
    'poplin','knit','denim','fleece','waterproof','shell'
];
$productPattern = '/' . implode('|', array_map('preg_quote', $productKeywords)) . '/';

// ── Category list ──
$categories = ['tops','bottoms','outerwear','footwear','accessories'];
$categoryPattern = '/' . implode('|', $categories) . '/';

// ── Default fallback ──
$response = "I'm not sure about that one. Try asking me about:\n• Our products\n• Delivery & shipping\n• Returns & refunds\n• Your account\n• Student discount\n\nOr visit our Contact Us page for more help!";

// =====================================================
//  INTENT MATCHING (order matters — most specific first)
// =====================================================

// ── 1. GREETINGS ──
if (preg_match('/\b(hi|hello|hey|hiya|sup|yo|morning|afternoon|evening)\b/', $message)) {
    $greetings = [
        "Hi 👋 Welcome to EveryWear! How can I help you today?",
        "Hey there! 👋 What can I help you with?",
        "Hello! Welcome to EveryWear support. Ask me anything!",
    ];
    $response = $greetings[array_rand($greetings)];
}

// ── 2. PRODUCT SEARCH (by keyword) ──
elseif (
    str_contains($message, 'where can i find') ||
    str_contains($message, 'show me') ||
    str_contains($message, 'do you have') ||
    str_contains($message, 'do you sell') ||
    str_contains($message, 'details') ||
    str_contains($message, 'sizes') ||
    str_contains($message, 'available') ||
    str_contains($message, 'looking for') ||
    str_contains($message, 'product') ||
    str_contains($message, 'stock') ||
    str_contains($message, 'buy') ||
    preg_match($productPattern, $message)
) {
    // Try to extract a product keyword
    preg_match($productPattern, $message, $matches);

    if (!empty($matches)) {
        $keyword = $matches[0];
        try {
            $stmt = $pdo->prepare(
                "SELECT product_id, name, price, category, stock_quantity
                 FROM Products
                 WHERE LOWER(name) LIKE :keyword
                 LIMIT 5"
            );
            $stmt->execute([':keyword' => "%$keyword%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) > 0) {
                $response = "🛍 Here's what I found for \"$keyword\":\n\n";
                foreach ($results as $p) {
                    $stockText = ($p['stock_quantity'] > 0) ? "In stock" : "Out of stock";
                    $response .= "• " . $p['name'] . " — £" . number_format($p['price'], 2)
                               . " (" . $p['category'] . ") — " . $stockText . "\n";
                }
                $response .= "\nVisit our Products page to see full details and add to cart!";
            } else {
                $response = "Sorry, I couldn't find a product matching \"$keyword\". Try browsing our Products page for the full range!";
            }
        } catch (PDOException $e) {
            $response = "Sorry, I had trouble searching our products. Please try again!";
        }
    } else {
        $response = "Which product are you looking for? Try saying something like:\n• \"Do you have hoodies?\"\n• \"Show me jeans\"\n• \"T-shirt sizes\"";
    }
}

// ── 3. BROWSE BY CATEGORY ──
elseif (preg_match($categoryPattern, $message, $catMatch)) {
    $cat = ucfirst($catMatch[0]);
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM Products WHERE LOWER(category) LIKE :cat"
        );
        $stmt->execute([':cat' => "%" . strtolower($cat) . "%"]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $row['total'] ?? 0;

        $response = "👕 We have $count products in our $cat collection! Check them out on our Products page.";
    } catch (PDOException $e) {
        $response = "Check out our $cat collection on the Products page!";
    }
}

// ── 4. PRICE / HOW MUCH ──
elseif (
    str_contains($message, 'how much') ||
    str_contains($message, 'price') ||
    str_contains($message, 'cost') ||
    str_contains($message, 'cheap') ||
    str_contains($message, 'expensive')
) {
    preg_match($productPattern, $message, $matches);

    if (!empty($matches)) {
        $keyword = $matches[0];
        try {
            $stmt = $pdo->prepare(
                "SELECT name, price FROM Products WHERE LOWER(name) LIKE :keyword LIMIT 3"
            );
            $stmt->execute([':keyword' => "%$keyword%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) > 0) {
                $response = "💰 Prices for \"$keyword\":\n\n";
                foreach ($results as $p) {
                    $response .= "• " . $p['name'] . " — £" . number_format($p['price'], 2) . "\n";
                }
            } else {
                $response = "I couldn't find that product. Which item are you interested in?";
            }
        } catch (PDOException $e) {
            $response = "Sorry, I had trouble looking that up. Check our Products page for prices!";
        }
    } else {
        $response = "Which product would you like the price for? Try: \"How much is the hoodie?\"";
    }
}

// ── 5. DELIVERY / SHIPPING ──
elseif (
    str_contains($message, 'delivery') ||
    str_contains($message, 'arrive') ||
    str_contains($message, 'shipping') ||
    str_contains($message, 'how long') ||
    str_contains($message, 'dispatch') ||
    str_contains($message, 'post')
) {
    $response = "📦 Our delivery options:\n\n"
              . "• Standard Delivery — 3–5 business days (£3.99)\n"
              . "• Express Delivery — 1–2 business days (£6.99)\n"
              . "• FREE Delivery on orders over £50\n\n"
              . "All orders include tracking! Check our Delivery & Returns page for more info.";
}

// ── 6. RETURNS / REFUNDS ──
elseif (
    str_contains($message, 'return') ||
    str_contains($message, 'refund') ||
    str_contains($message, 'exchange') ||
    str_contains($message, 'send back') ||
    str_contains($message, 'money back')
) {
    $response = "🔄 Our returns policy:\n\n"
              . "• Return within 30 days of receiving your order\n"
              . "• Items must be unworn and in original condition\n"
              . "• All tags must be attached\n"
              . "• Proof of purchase required\n\n"
              . "Refunds are processed within 5–7 business days to your original payment method.";
}

// ── 7. ORDER TRACKING ──
elseif (
    str_contains($message, 'track') ||
    str_contains($message, 'my order') ||
    str_contains($message, 'order status') ||
    str_contains($message, 'where is my') ||
    str_contains($message, 'order history')
) {
    $response = "📍 You can track your orders from your account dashboard. Once shipped, you'll get a tracking email too!\n\nGo to My Account → Orders to view your history.";
}

// ── 8. ACCOUNT / LOGIN / PASSWORD ──
elseif (
    str_contains($message, 'account') ||
    str_contains($message, 'sign up') ||
    str_contains($message, 'register') ||
    str_contains($message, 'login') ||
    str_contains($message, 'log in') ||
    str_contains($message, 'password') ||
    str_contains($message, 'forgot')
) {
    $response = "🔐 Account help:\n\n"
              . "• Log in or create an account from the top of any page\n"
              . "• Forgot your password? Use the reset link on the login page\n"
              . "• Your account lets you track orders, save favourites, and checkout faster!";
}

// ── 9. SUSTAINABILITY / ECO ──
elseif (
    str_contains($message, 'sustain') ||
    str_contains($message, 'eco') ||
    str_contains($message, 'recycle') ||
    str_contains($message, 'organic') ||
    str_contains($message, 'environment') ||
    str_contains($message, 'green') ||
    str_contains($message, 'ethical')
) {
    $response = "♻️ Sustainability is at the heart of EveryWear!\n\n"
              . "Many of our products use:\n"
              . "• Recycled polyester & fibres\n"
              . "• Organic cotton\n"
              . "• Eco-conscious materials\n\n"
              . "Check individual product pages for sustainability details!";
}

// ── 10. CONTACT / SUPPORT ──
elseif (
    str_contains($message, 'contact') ||
    str_contains($message, 'email') ||
    str_contains($message, 'support') ||
    str_contains($message, 'speak to') ||
    str_contains($message, 'phone') ||
    str_contains($message, 'human') ||
    str_contains($message, 'agent') ||
    str_contains($message, 'real person')
) {
    $response = "📧 You can reach our team via:\n\n"
              . "• Our Contact Us page\n"
              . "• Email: support@everywear.com\n\n"
              . "We'll get back to you as soon as possible!";
}

// ── 11. PAYMENT ──
elseif (
    str_contains($message, 'pay') ||
    str_contains($message, 'card') ||
    str_contains($message, 'checkout') ||
    str_contains($message, 'payment') ||
    str_contains($message, 'visa') ||
    str_contains($message, 'mastercard') ||
    str_contains($message, 'apple pay')
) {
    $response = "💳 We accept all major credit and debit cards. Your payment is securely processed at checkout.\n\nAdd items to your basket and proceed to checkout when you're ready!";
}

// ── 12. DISCOUNT / STUDENT ──
elseif (
    str_contains($message, 'discount') ||
    str_contains($message, 'student') ||
    str_contains($message, 'offer') ||
    str_contains($message, 'promo') ||
    str_contains($message, 'code') ||
    str_contains($message, 'sale') ||
    str_contains($message, 'deal')
) {
    $response = "🎓 Students get 10% off!\n\nLog in with your student account and the discount is applied automatically at checkout. Create an account to get started!";
}

// ── 13. SIZE GUIDE ──
elseif (
    str_contains($message, 'size guide') ||
    str_contains($message, 'what size') ||
    str_contains($message, 'sizing') ||
    str_contains($message, 'fit')
) {
    $response = "📏 Our products are generally true to size. Some items feature relaxed or oversized fits — check the product description for specific sizing notes.\n\nSizes typically range from XS to XL. If you're between sizes, we recommend going up one.";
}

// ── 14. CART / BASKET ──
elseif (
    str_contains($message, 'cart') ||
    str_contains($message, 'basket') ||
    str_contains($message, 'add to')
) {
    $response = "🛒 You can add items to your basket from any product page. Click the basket icon in the top navigation to view your cart and proceed to checkout!";
}

// ── 15. ABOUT / WHO ARE YOU ──
elseif (
    str_contains($message, 'about') ||
    str_contains($message, 'who are you') ||
    str_contains($message, 'what is everywear') ||
    str_contains($message, 'tell me about')
) {
    $response = "👕 EveryWear is a unisex fashion brand designed for all. We offer quality tops, bottoms, outerwear, footwear, and accessories with a focus on comfort, style, and sustainability.\n\nVisit our About Us page to learn more!";
}

// ── 16. THANKS / BYE ──
elseif (
    str_contains($message, 'thank') ||
    str_contains($message, 'thanks') ||
    str_contains($message, 'cheers') ||
    str_contains($message, 'bye') ||
    str_contains($message, 'goodbye') ||
    str_contains($message, 'see ya')
) {
    $farewells = [
        "You're welcome! 😊 Happy shopping at EveryWear!",
        "No problem! Let me know if you need anything else. 👋",
        "Glad I could help! Enjoy your day! ✨",
    ];
    $response = $farewells[array_rand($farewells)];
}

// ── 17. HELP / WHAT CAN YOU DO ──
elseif (
    str_contains($message, 'help') ||
    str_contains($message, 'what can you do') ||
    str_contains($message, 'options') ||
    str_contains($message, 'menu')
) {
    $response = "🤖 I can help you with:\n\n"
              . "🛍 Product search — \"Show me hoodies\"\n"
              . "💰 Prices — \"How much is the t-shirt?\"\n"
              . "📦 Delivery info — \"How long does delivery take?\"\n"
              . "🔄 Returns — \"What's your returns policy?\"\n"
              . "📍 Order tracking — \"Where is my order?\"\n"
              . "🔐 Account help — \"How do I log in?\"\n"
              . "🎓 Student discount — \"Do you have a student discount?\"\n"
              . "♻️ Sustainability — \"Are your products eco-friendly?\"\n\n"
              . "Just type a question!";
}

echo json_encode(["reply" => $response]);

<?php
/**
 * tests.php
 *
 * Plain PHP test runner for EveryWear (Team 44).
 * No frameworks or installs needed — just run it in the browser
 * or via the terminal.
 *
 * Browser: https://yoursite.com/tests.php
 * Terminal: php tests.php
 *
 * IMPORTANT: Delete or move this file off the server after testing.
 * It should never be publicly accessible on the live site.
 *
 * @author EveryWear Team 44
 */

// ─── TEST RUNNER ─────────────────────────────────────────────────────────────

$passed = 0;
$failed = 0;
$results = [];

/**
 * Runs a single test and records the result.
 *
 * @param string   $name      Description of what is being tested
 * @param bool     $condition The assertion — true = pass, false = fail
 * @param string   $detail    Optional extra info shown on failure
 */
function test(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed, $results;

    if ($condition) {
        $passed++;
        $results[] = ['pass', $name, ''];
    } else {
        $failed++;
        $results[] = ['fail', $name, $detail];
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// Extracted from the actual PHP files so we can test the logic
// without needing a database connection or a full page load.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Validates signup form fields.
 * Mirrors the validation logic in process-signup.php.
 */
function validateSignupData(array $data): array
{
    $errors = [];

    if (empty(trim($data['first_name'] ?? '')))
        $errors[] = "First name is required";

    if (empty(trim($data['last_name'] ?? '')))
        $errors[] = "Last name is required";

    $email = trim($data['email'] ?? '');
    if (empty($email))
        $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format";

    $password = $data['password'] ?? '';
    if (empty($password))
        $errors[] = "Password is required";
    elseif (strlen($password) < 6)
        $errors[] = "Password must be at least 6 characters";

    return $errors;
}

/**
 * Checks whether two passwords match.
 * Mirrors the password match check in process-signup.php.
 */
function passwordsMatch(string $a, string $b): bool
{
    return $a === $b;
}

/**
 * Sanitises cart POST input.
 * Mirrors the input handling in add_to_cart.php.
 */
function sanitiseCartInput(array $post): array
{
    $productId = isset($post['product_id']) ? (int)  $post['product_id'] : 0;
    $quantity  = isset($post['quantity'])   ? (int)  $post['quantity']   : 1;
    $size      = isset($post['size'])       ? trim($post['size'])         : '';

    if (empty($size)) $size = 'M';

    return [$productId, $quantity, $size];
}

/**
 * Validates that cart product_id and quantity are positive.
 * Mirrors the validation guard in add_to_cart.php.
 */
function isValidCartInput(int $productId, int $quantity): bool
{
    return $productId > 0 && $quantity > 0;
}

/**
 * Calculates a single cart line item total.
 * Mirrors the price × quantity calculation in add_to_cart.php.
 */
function calculateLineTotal(float $price, int $quantity): float
{
    return $price * $quantity;
}

/**
 * Calculates the full cart grand total.
 * Mirrors the total used across cart-related pages.
 */
function calculateCartTotal(array $items): float
{
    $total = 0.0;
    foreach ($items as $item) {
        $total += (float)$item['price'] * (int)$item['quantity'];
    }
    return $total;
}

/**
 * Simulates clearing the session on logout.
 * Mirrors logout.php — $_SESSION = [].
 */
function clearSession(array $session): array
{
    return [];
}

/**
 * Builds the PDO DSN string.
 * Mirrors dbconfig.php.
 */
function buildDsn(string $host, string $db): string
{
    return "mysql:host=$host;dbname=$db";
}


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: process-signup.php — Signup Validation
// ═══════════════════════════════════════════════════════════════════════════════

$errors = validateSignupData([
    'first_name' => 'Jane', 'last_name' => 'Doe',
    'email' => 'jane@example.com', 'password' => 'secret123'
]);
test('Valid signup data produces no errors', count($errors) === 0);

test('Empty form produces 4 errors', count(validateSignupData([])) === 4);

$errors = validateSignupData([
    'first_name' => 'Jane', 'last_name' => 'Doe',
    'email' => 'notanemail', 'password' => 'secret123'
]);
test('Invalid email format fails validation', in_array("Invalid email format", $errors));

$errors = validateSignupData([
    'first_name' => 'Jane', 'last_name' => 'Doe',
    'email' => 'jane@example.com', 'password' => 'abc'
]);
test('Password under 6 chars fails validation',
    in_array("Password must be at least 6 characters", $errors));

$errors = validateSignupData([
    'first_name' => 'Jane', 'last_name' => 'Doe',
    'email' => 'jane@example.com', 'password' => 'abc123'
]);
test('Password of exactly 6 chars passes validation', count($errors) === 0);

$errors = validateSignupData([
    'first_name' => '   ', 'last_name' => 'Doe',
    'email' => 'jane@example.com', 'password' => 'secret123'
]);
test('Whitespace-only first name fails validation',
    in_array("First name is required", $errors));


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: process-signup.php — Password Matching & Hashing
// ═══════════════════════════════════════════════════════════════════════════════

test('Identical passwords match',        passwordsMatch('mypassword', 'mypassword'));
test('Different passwords do not match', !passwordsMatch('password1', 'password2'));
test('Password match is case-sensitive', !passwordsMatch('Password', 'password'));

$plain  = 'mysecretpassword';
$hashed = password_hash($plain, PASSWORD_DEFAULT);
test('Hashed password is not plain text',       $plain !== $hashed);
test('password_verify confirms correct hash',   password_verify($plain, $hashed));
test('Wrong password fails verification',       !password_verify('wrongpassword', $hashed));

$hash1 = password_hash($plain, PASSWORD_DEFAULT);
$hash2 = password_hash($plain, PASSWORD_DEFAULT);
test('Same password produces different hashes each time (bcrypt salt)', $hash1 !== $hash2);


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: add_to_cart.php — Input Sanitisation
// ═══════════════════════════════════════════════════════════════════════════════

[$pid, $qty, $size] = sanitiseCartInput(['product_id' => '5', 'quantity' => '2', 'size' => ' L ']);
test('Product ID is cast to int',         $pid  === 5);
test('Quantity is cast to int',           $qty  === 2);
test('Size is trimmed of whitespace',     $size === 'L');

[,, $size] = sanitiseCartInput(['product_id' => '3', 'quantity' => '1']);
test('Missing size defaults to M',        $size === 'M');

[, $qty,] = sanitiseCartInput(['product_id' => '3']);
test('Missing quantity defaults to 1',   $qty === 1);

[$pid,,] = sanitiseCartInput(['product_id' => '7abc']);
test('Non-numeric product_id cast to int', is_int($pid));


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: add_to_cart.php — Validation & Price Calculation
// ═══════════════════════════════════════════════════════════════════════════════

test('Valid product_id and quantity passes', isValidCartInput(1, 2));
test('product_id of 0 fails validation',    !isValidCartInput(0, 1));
test('Negative quantity fails validation',  !isValidCartInput(1, -1));
test('Both zero fails validation',          !isValidCartInput(0, 0));

test('Line total: 14.00 × 2 = 28.00',  calculateLineTotal(14.00, 2)  === 28.0);
test('Line total: 26.00 × 1 = 26.00',  calculateLineTotal(26.00, 1)  === 26.0);
test('Line total: 32.00 × 3 = 96.00',  calculateLineTotal(32.00, 3)  === 96.0);

$cart = [
    ['price' => 14.00, 'quantity' => 2],
    ['price' => 26.00, 'quantity' => 1],
    ['price' => 32.00, 'quantity' => 3],
];
test('Cart total sums all items correctly: £150.00', calculateCartTotal($cart) === 150.0);
test('Empty cart total is 0',                        calculateCartTotal([])    === 0.0);
test('Single item cart total',
    calculateCartTotal([['price' => 22.00, 'quantity' => 3]]) === 66.0);


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: logout.php — Session Clearing
// ═══════════════════════════════════════════════════════════════════════════════

$session = ['user_id' => 42, 'first_name' => 'Jane', 'cart' => ['item1']];
test('Session is empty after logout',          count(clearSession($session)) === 0);
test('Clearing empty session stays empty',     count(clearSession([]))       === 0);


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: dbconfig.php — DSN String
// ═══════════════════════════════════════════════════════════════════════════════

$dsn = buildDsn('localhost', 'cs2team44_db');
test('DSN starts with mysql:',              strpos($dsn, 'mysql:')            === 0);
test('DSN contains correct host',           strpos($dsn, 'host=localhost')    !== false);
test('DSN contains correct database name',  strpos($dsn, 'dbname=cs2team44_db') !== false);


// ═══════════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS — new files
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Maps login error GET codes to friendly messages.
 * Mirrors the switch statement in login.php.
 */
function mapLoginError(string $code): string
{
    switch ($code) {
        case 'empty_fields':   return 'Please fill in all fields.';
        case 'no_user':
        case 'wrong_password': return 'Invalid email or password.';
        case 'db_error':       return 'Database error. Please try again later.';
        default:               return '';
    }
}

/**
 * Validates login form fields (empty check only).
 * Mirrors the validation guard in process-login.php.
 */
function validateLoginInput(string $email, string $password): bool
{
    return !empty($email) && !empty($password);
}

/**
 * Determines whether a user is an admin based on their role string.
 * Mirrors the session-setting logic in process-login.php.
 */
function isAdmin(string $role): bool
{
    return $role === 'admin';
}

/**
 * Builds the full_name session value from first and last name.
 * Mirrors the session assignment in process-login.php.
 */
function buildFullName(string $firstName, string $lastName): string
{
    return $firstName . ' ' . $lastName;
}

/**
 * Checks whether an order status update is in the allowed list.
 * Mirrors the whitelist check in admin-orders.php.
 */
function isAllowedStatus(string $status): bool
{
    return in_array($status, ['placed', 'processing', 'shipped', 'delivered']);
}

/**
 * Validates an email address format using a simple regex.
 * Mirrors the JS validation in login.php and reset-password.php.
 */
function isValidEmailFormat(string $email): bool
{
    return (bool) preg_match('/^[^@]+@[^@]+\.[^@]+$/', $email);
}

/**
 * Calculates the checkout subtotal from a session cart.
 * Mirrors the subtotal calculation in shipping.php.
 */
function calculateSubtotal(array $cart): float
{
    $subtotal = 0.0;
    foreach ($cart as $item) {
        $price = isset($item['price'])    ? (float) $item['price']    : 0;
        $qty   = isset($item['quantity']) ? (int)   $item['quantity'] : 1;
        $subtotal += $price * $qty;
    }
    return $subtotal;
}

/**
 * Calculates the checkout grand total (subtotal + shipping).
 * Mirrors the total display logic in shipping.php.
 */
function calculateGrandTotal(float $subtotal, float $shipping): float
{
    return $subtotal + $shipping;
}


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: login.php — Error Code Mapping
// ═══════════════════════════════════════════════════════════════════════════════

test('empty_fields maps to correct message',
    mapLoginError('empty_fields') === 'Please fill in all fields.');

test('no_user maps to invalid credentials message',
    mapLoginError('no_user') === 'Invalid email or password.');

test('wrong_password maps to same message as no_user',
    mapLoginError('wrong_password') === 'Invalid email or password.');

test('db_error maps to database error message',
    mapLoginError('db_error') === 'Database error. Please try again later.');

test('Unknown error code returns empty string',
    mapLoginError('made_up_code') === '');

test('No error code (empty string) returns empty string',
    mapLoginError('') === '');


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: process-login.php — Login Input Validation
// ═══════════════════════════════════════════════════════════════════════════════

test('Valid email and password passes login validation',
    validateLoginInput('jane@example.com', 'secret123'));

test('Empty email fails login validation',
    !validateLoginInput('', 'secret123'));

test('Empty password fails login validation',
    !validateLoginInput('jane@example.com', ''));

test('Both fields empty fails login validation',
    !validateLoginInput('', ''));

test('Whitespace-only email still passes empty check',
    validateLoginInput('   ', 'secret123'));


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: process-login.php — Admin Role & Session Building
// ═══════════════════════════════════════════════════════════════════════════════

test('Role "admin" is correctly identified as admin',  isAdmin('admin'));
test('Role "user" is not identified as admin',         !isAdmin('user'));
test('Empty role string is not admin',                 !isAdmin(''));
test('Role check is case-sensitive ("Admin" != admin)', !isAdmin('Admin'));

test('Full name built correctly from first and last name',
    buildFullName('Jane', 'Doe') === 'Jane Doe');

test('Full name with single-word last name',
    buildFullName('Ali', 'Khan') === 'Ali Khan');


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: admin-orders.php — Order Status Whitelist
// ═══════════════════════════════════════════════════════════════════════════════

test('"placed" is an allowed order status',      isAllowedStatus('placed'));
test('"processing" is an allowed order status',  isAllowedStatus('processing'));
test('"shipped" is an allowed order status',     isAllowedStatus('shipped'));
test('"delivered" is an allowed order status',   isAllowedStatus('delivered'));
test('"cancelled" is NOT an allowed status',     !isAllowedStatus('cancelled'));
test('"refunded" is NOT an allowed status',      !isAllowedStatus('refunded'));
test('Empty string is NOT an allowed status',    !isAllowedStatus(''));
test('Status check is case-sensitive',           !isAllowedStatus('Shipped'));


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: login.php / reset-password.php — Email Format Validation
// ═══════════════════════════════════════════════════════════════════════════════

test('Valid email passes format check',          isValidEmailFormat('jane@example.com'));
test('Email with subdomain passes',              isValidEmailFormat('jane@mail.example.com'));
test('Missing @ fails format check',             !isValidEmailFormat('notanemail'));
test('Missing domain fails format check',        !isValidEmailFormat('jane@'));
test('Missing local part fails format check',    !isValidEmailFormat('@example.com'));
test('Empty string fails format check',          !isValidEmailFormat(''));
test('Plain word fails format check',            !isValidEmailFormat('hello'));


// ═══════════════════════════════════════════════════════════════════════════════
// TESTS: shipping.php — Subtotal & Grand Total Calculation
// ═══════════════════════════════════════════════════════════════════════════════

$cart = [
    ['price' => 32.00, 'quantity' => 1],
    ['price' => 14.00, 'quantity' => 2],
];
test('Shipping subtotal sums cart correctly: £60.00',
    calculateSubtotal($cart) === 60.0);

test('Empty cart gives subtotal of £0.00',
    calculateSubtotal([]) === 0.0);

test('Single item subtotal',
    calculateSubtotal([['price' => 55.00, 'quantity' => 1]]) === 55.0);

test('Cart item missing price defaults to 0',
    calculateSubtotal([['quantity' => 2]]) === 0.0);

test('Cart item missing quantity defaults to 1',
    calculateSubtotal([['price' => 20.00]]) === 20.0);

test('Standard delivery grand total (subtotal + £3.99)',
    calculateGrandTotal(60.0, 3.99) === 63.99);

test('Express delivery grand total (subtotal + £7.99)',
    calculateGrandTotal(60.0, 7.99) === 67.99);

test('Zero shipping grand total equals subtotal',
    calculateGrandTotal(50.0, 0.0) === 50.0);


// ═══════════════════════════════════════════════════════════════════════════════
// OUTPUT
// ═══════════════════════════════════════════════════════════════════════════════

$total = $passed + $failed;
$isCli = PHP_SAPI === 'cli'; // detect if running in terminal or browser

if ($isCli) {
    // ── Terminal output ──────────────────────────────────────────────────────
    echo PHP_EOL . "  EveryWear Test Suite" . PHP_EOL;
    echo "  " . str_repeat("─", 50) . PHP_EOL;

    foreach ($results as [$status, $name, $detail]) {
        $icon = $status === 'pass' ? '  ✔' : '  ✘';
        echo "$icon  $name" . PHP_EOL;
        if ($detail) echo "       → $detail" . PHP_EOL;
    }

    echo PHP_EOL . "  " . str_repeat("─", 50) . PHP_EOL;
    echo "  $passed/$total tests passed";
    if ($failed > 0) echo "  ($failed failed)";
    echo PHP_EOL . PHP_EOL;

} else {
    // ── Browser output ───────────────────────────────────────────────────────
    $allPassed = $failed === 0;
    $barColor  = $allPassed ? '#28a745' : '#dc3545';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EveryWear Tests</title>
    <style>
        body { font-family: monospace; background: #111; color: #eee; padding: 2rem; margin: 0; }
        h1   { font-size: 1.4rem; margin-bottom: 0.25rem; }
        .sub { color: #888; font-size: 0.85rem; margin-bottom: 2rem; }
        .bar { height: 6px; border-radius: 3px; background: <?= $barColor ?>; width: 100%; margin-bottom: 2rem; }
        .result { padding: 6px 0; border-bottom: 1px solid #222; display: flex; gap: 12px; }
        .pass { color: #28a745; }
        .fail { color: #dc3545; }
        .name { flex: 1; }
        .detail { color: #f0ad4e; font-size: 0.85rem; display: block; padding-left: 28px; }
        .summary { margin-top: 2rem; font-size: 1.1rem; font-weight: bold; }
        .summary.ok  { color: #28a745; }
        .summary.bad { color: #dc3545; }
        .warning { background: #7c2d12; color: #fed7aa; padding: 12px 16px;
                   border-radius: 6px; margin-top: 2rem; font-size: 0.85rem; }
    </style>
</head>
<body>
    <h1>&#9878; EveryWear Test Suite</h1>
    <div class="sub">Team 44 &mdash; <?= $total ?> tests</div>
    <div class="bar"></div>

    <?php foreach ($results as [$status, $name, $detail]): ?>
        <div class="result">
            <span class="<?= $status ?>"><?= $status === 'pass' ? '&#10004;' : '&#10008;' ?></span>
            <span class="name"><?= htmlspecialchars($name) ?></span>
        </div>
        <?php if ($detail): ?>
            <span class="detail">&rarr; <?= htmlspecialchars($detail) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="summary <?= $allPassed ? 'ok' : 'bad' ?>">
        <?= $passed ?>/<?= $total ?> tests passed
        <?= $failed > 0 ? " &mdash; $failed failed" : ' &mdash; All good!' ?>
    </div>

    <div class="warning">
        &#9888; Remember to delete or remove this file from your server after testing.<br>
        It should never be publicly accessible on the live site.
    </div>
</body>
</html>
<?php } ?>

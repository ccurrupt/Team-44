<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Map error codes from process-login.php to friendly messages
$error = '';
switch ($_GET['error'] ?? '') {
    case 'empty_fields':   $error = 'Please fill in all fields.'; break;
    case 'no_user':
    case 'wrong_password': $error = 'Invalid email or password.'; break;
    case 'db_error':       $error = 'Database error. Please try again later.'; break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>EveryWear – Login</title>
<link
    href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css"
    rel="stylesheet"
/>
<link rel="stylesheet" href="style.css">

<style>
    body {
        font-family: "Goudy Bookletter 1911", sans-serif;
        background: #ffffff;
        padding: 20px;
    }

    .container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        max-width: 1100px;
        margin: auto;
        display: flex;
        gap: 40px;
    }

    .logo-row {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-left: 10px;
    }

    .logo-row img {
        height: 70px;
        margin-right: 50px;
    }

    /* Left login panel */
    .login-panel {
        background: #d9d9d9;
        padding: 70px;
        border-radius: 10px;
        width: 400px;
    }

    .login-panel input {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #aaa;
        margin-bottom: 15px;
        font-size: 15px;
        font-family: bold;
    }

    .reset-link {
        font-size: 16px;
        margin-top: -10px;
        text-decoration: underline;
        cursor: pointer;
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        background: #417BFF;
        color: white;
        border: none;
        font-size: 18px;
        cursor: pointer;
        margin-top: 15px;
        font-weight: bold;
    }

    .create-panel {
        text-align: center;
        width: 400px;
        margin-left: 60px;
        margin-top: 50px;
    }

    .create-btn {
        background: black;
        color: white;
        padding: 15px 20px;
        border-radius: 30px;
        font-size: 18px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: none;
        cursor: pointer;
        margin-top: 20px;
        text-decoration: none;
    }

    .error {
        color: red;
        font-size: 13px;
        margin-top: -10px;
        margin-bottom: 10px;
    }
</style>
</head>
<body>
    <!-- TOP NAVIGATION -->
    <div class="navbar">

        <!-- LOGO -->
        <div class="logo-section">
            <a href="index.php" class="logo-link" aria-label="Go to homepage">
                <img src="images/logo.png" loading="eager" alt="EveryWear Logo" width="120" height="90" class="site-logo">
            </a>
        </div>
    </div>

    <div class="container">

        <!-- LEFT LOGIN BOX -->
        <div class="login-panel">
            <h3>Ready to Continue Shopping?</h3>

            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="error" style="color:#388e3c;background:#e8f5e9;border-left-color:#388e3c;">
                    Account created successfully! You can now log in.
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="process-login.php">
                <input type="email" name="email" id="email" placeholder="Email Address"
                       value="<?php echo htmlspecialchars($email); ?>">
                <div class="error" id="emailError"></div>

                <input type="password" name="password" id="password" placeholder="Password">
                <div class="error" id="passwordError"></div>

                <a href="reset-password.php" class="reset-link">Forgot your password?</a>

                <button type="submit" class="login-btn">Log in</button>
            </form>

        </div>

    <div class="create-panel">
        <h2>New to EveryWear?<br>Create your account<br>today</h2>

        <a href="create-account.php" class="create-btn">
            <i class="ri-account-circle-fill"></i>
            Create Account
        </a>

    </div>

<script>
document.getElementById("loginForm").addEventListener("submit", function(e) {
    // Clear previous errors
    document.getElementById("emailError").textContent = "";
    document.getElementById("passwordError").textContent = "";

    let valid = true;
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();

    // Email validation
    if (!email.match(/^[^@]+@[^@]+\.[^@]+$/)) {
        document.getElementById("emailError").textContent = "Enter a valid email address.";
        valid = false;
    }

    // Password validation
    if (password.length < 6) {
        document.getElementById("passwordError").textContent = "Password must be at least 6 characters.";
        valid = false;
    }

    if (!valid) {
        e.preventDefault(); // Only block submission if client-side validation fails
    }
    // If valid, form submits normally to PHP for authentication
});
</script>

</body>
</html>

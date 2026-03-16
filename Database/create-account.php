<?php
$msg = "";
$msg_class = ""; // For styling different message types

if (isset($_GET["error"])) {
    switch ($_GET["error"]) {
        case "email_used": 
            $msg = "That email is already registered.";
            $msg_class = "error";
            break;
        case "nomatch": 
            $msg = "Passwords must match.";
            $msg_class = "error";
            break;
        case "dberror": 
            $msg = "Database error. Please try again.";
            $msg_class = "error";
            break;
        case "validation": 
            $msg = "Please fill in all required fields correctly.";
            $msg_class = "error";
            break;
    }
}
if (isset($_GET["created"])) { 
    $msg = "Account created! You may now log in.";
    $msg_class = "success";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Create Account</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f7f7f7;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
        margin: 0;
    }

    .container {
        background: white;
        width: 100%;
        max-width: 400px;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .logo {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 25px;
    }

    .logo img {
        height: 40px;
        margin-right: 10px;
    }

    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }

    .form-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        font-weight: bold;
        margin-bottom: 6px;
        color: #555;
    }

    input {
        width: 100%;
        padding: 12px;
        border: 1px solid #bbb;
        border-radius: 5px;
        font-size: 15px;
        box-sizing: border-box;
    }

    input:focus {
        outline: none;
        border-color: #666;
    }

    .password-wrapper {
        position: relative;
    }

    .show-btn {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        background: none;
        border: none;
        font-size: 14px;
        cursor: pointer;
        color: #666;
    }

    .btn {
        width: 100%;
        background: black;
        color: white;
        font-size: 16px;
        padding: 14px;
        border-radius: 5px;
        cursor: pointer;
        border: none;
        margin-top: 10px;
        font-weight: bold;
        transition: background-color 0.3s;
    }

    .btn:hover {
        background: #333;
    }

    .login-link {
        text-align: center;
        margin-top: 20px;
        font-size: 15px;
        color: #666;
    }

    .login-link a {
        color: #333;
        font-weight: bold;
        text-decoration: none;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    .message-box {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
    }

    .message-box.error {
        background: #ffdddd;
        border-left: 4px solid #f44336;
        color: #d32f2f;
    }

    .message-box.success {
        background: #d4edda;
        border-left: 4px solid #28a745;
        color: #155724;
    }
</style>
</head>
<body>

<div class="container">
    <h2>Create Account</h2>
    
    <?php if ($msg != ""): ?>
    <div class="message-box <?php echo $msg_class; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <form action="process-signup.php" method="POST">
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Email Address" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <button type="button" class="show-btn" onclick="togglePassword('password')">Show</button>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="button" class="show-btn" onclick="togglePassword('confirm_password')">Show</button>
            </div>
        </div>

        <button type="submit" class="btn">Create Account</button>
    </form>

    <div class="login-link">
        Already have an account? <a href="login.php">Log in</a>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;
        
        if (field.type === 'password') {
            field.type = 'text';
            button.textContent = 'Hide';
        } else {
            field.type = 'password';
            button.textContent = 'Show';
        }
    }
</script>

</body>
</html>
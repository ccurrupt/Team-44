<?php
session_start();
require_once 'dbconfig.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if user exists
            $sql = "SELECT user_id, first_name, last_name, email, password FROM Users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    
                    // Redirect to index
                    header("Location: index.php");
                    exit();
                } else {
                    $error = 'Invalid email or password';
                }
            } else {
                $error = 'Invalid email or password';
            }
        } catch(PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    }
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

<style>
    body {
        font-family: "Goudy Bookletter 1911", sans-serif;
        background: #ffffff;
        padding: 20px;
        margin: 0;
    }

    .container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        gap: 40px;
        flex-wrap: wrap;
    }

    .logo-row {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-left: 10px;
        max-width: 1100px;
        margin: 0 auto 20px;
    }

    .logo-row img {
        height: 70px;
        margin-right: 50px;
        cursor: pointer;
    }

    /* Left login panel */
    .login-panel {
        background: #d9d9d9;
        padding: 40px;
        border-radius: 10px;
        flex: 1;
        min-width: 300px;
    }

    .login-panel h3 {
        margin-bottom: 25px;
        font-size: 24px;
        color: #333;
    }

    .login-panel input {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #aaa;
        margin-bottom: 5px;
        font-size: 15px;
        box-sizing: border-box;
    }

    .reset-link {
        font-size: 16px;
        margin-top: 10px;
        text-decoration: underline;
        cursor: pointer;
        color: #333;
        display: inline-block;
    }

    .reset-link:hover {
        color: #417BFF;
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
        margin-top: 20px;
        font-weight: bold;
        transition: background 0.3s;
    }

    .login-btn:hover {
        background: #2a65ff;
    }

    .create-panel {
        text-align: center;
        flex: 1;
        min-width: 300px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .create-panel h2 {
        font-size: 28px;
        line-height: 1.4;
        margin-bottom: 20px;
        color: #333;
    }

    .create-btn {
        background: black;
        color: white;
        padding: 15px 25px;
        border-radius: 30px;
        font-size: 18px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: none;
        cursor: pointer;
        margin-top: 10px;
        text-decoration: none;
        transition: background 0.3s;
    }

    .create-btn:hover {
        background: #333;
    }

    .error {
        color: #d32f2f;
        font-size: 14px;
        margin-top: -5px;
        margin-bottom: 10px;
        padding: 8px;
        background: #ffebee;
        border-radius: 4px;
        border-left: 4px solid #d32f2f;
    }

    .success {
        color: #388e3c;
        font-size: 14px;
        margin-top: -5px;
        margin-bottom: 10px;
        padding: 8px;
        background: #e8f5e9;
        border-radius: 4px;
        border-left: 4px solid #388e3c;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .container {
            flex-direction: column;
            gap: 20px;
        }
        
        .login-panel, .create-panel {
            width: 100%;
            margin-left: 0;
            margin-top: 0;
        }
        
        .logo-row {
            justify-content: center;
            padding-left: 0;
        }
        
        .logo-row img {
            margin-right: 0;
        }
    }
</style>
</head>
<body>

<div class="logo-row">
    <a href="index.php">
        <img src="logo.png" alt="EveryWear Logo">
    </a>
</div>

<div class="container">

    <!-- LEFT LOGIN BOX -->
    <div class="login-panel">
        <h3>Ready to Continue Shopping?</h3>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="success">Account created successfully! You can now log in.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="email" name="email" id="email" placeholder="Email Address" 
                   value="<?php echo htmlspecialchars($email); ?>" required>
            
            <input type="password" name="password" id="password" placeholder="Password" required>
            
            <p class="reset-link" onclick="alert('Password reset feature coming soon!')">Reset your password</p>

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

</div>

<script>
// Client-side validation
document.querySelector('form').addEventListener('submit', function(e) {
    let email = document.getElementById('email').value.trim();
    let password = document.getElementById('password').value.trim();
    let isValid = true;
    
    // Clear any previous inline error styling
    document.getElementById('email').style.borderColor = '';
    document.getElementById('password').style.borderColor = '';
    
    //Email validation
    if (!email.match(/^[^@]+@[^@]+\.[^@]+$/)) {
        document.getElementById('email').style.borderColor = '#d32f2f';
        isValid = false;
    }
    
    //Password validation
    if (password.length < 6) {
        document.getElementById('password').style.borderColor = '#d32f2f';
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        if (!email.match(/^[^@]+@[^@]+\.[^@]+$/)) {
            alert('Please enter a valid email address.');
        } else if (password.length < 6) {
            alert('Password must be at least 6 characters.');
        }
    }
});

//Interactivity
document.getElementById('email').addEventListener('focus', function() {
    this.style.borderColor = '#417BFF';
});

document.getElementById('password').addEventListener('focus', function() {
    this.style.borderColor = '#417BFF';
});

// Show password on click
let showPassword = false;
document.addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && document.activeElement === document.getElementById('password')) {
        // Add show/hide password button on tab focus (optional feature)
        if (!document.querySelector('.show-password-btn')) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'show-password-btn';
            btn.textContent = 'Show';
            btn.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #417BFF; cursor: pointer;';
            btn.onclick = function() {
                const passwordField = document.getElementById('password');
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    btn.textContent = 'Hide';
                } else {
                    passwordField.type = 'password';
                    btn.textContent = 'Show';
                }
            };
            document.getElementById('password').parentNode.style.position = 'relative';
            document.getElementById('password').parentNode.appendChild(btn);
        }
    }
});
</script>

</body>
</html>
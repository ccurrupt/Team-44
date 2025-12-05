<?php
session_start();
require_once 'dbconfig.php';  // Include the PDO connection

// Get and sanitize input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate the input
if (empty($email) || empty($password)) {
    header("Location: login.php?error=empty_fields");
    exit();
}

try {
    // Prepare and execute using PDO
    $sql = "SELECT user_id, first_name, last_name, email, password FROM Users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    // Checks if user exists
    if ($stmt->rowCount() === 0) {
        header("Location: login.php?error=no_user");
        exit();
    }
    
    // Gets the user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        header("Location: login.php?error=wrong_password");
        exit();
    }
    
    // Set session variables
    $_SESSION["user_id"] = $user["user_id"];
    $_SESSION["first_name"] = $user["first_name"];
    $_SESSION["last_name"] = $user["last_name"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["full_name"] = $user["first_name"] . ' ' . $user["last_name"];
    
    // Redirect to home page
    header("Location: /index.php");
    exit();
    
} catch(PDOException $e) {
    // Log error
    error_log("Login error: " . $e->getMessage());
    
    // Redirect with error
    header("Location: login.php?error=db_error");
    exit();
}
?>

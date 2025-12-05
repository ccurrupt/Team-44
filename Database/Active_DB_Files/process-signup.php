<?php
//Include the database configuration
require_once 'dbconfig.php';

//Get form data
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

//Basic validation
$errors = [];

//Check required fields
if (empty($first_name)) {
    $errors[] = "First name is required";
}

if (empty($last_name)) {
    $errors[] = "Last name is required";
}

if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (empty($password)) {
    $errors[] = "Password is required";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
}

if ($password !== $confirm_password) {
    header("Location: create-account.php?error=nomatch");
    exit();
}

//If no validation errors, proceed with database operations
if (empty($errors)) {
    try {
        //Checks if email already exists
        $check_sql = "SELECT user_id FROM Users WHERE email = :email";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            header("Location: create-account.php?error=email_used");
            exit();
        }
        
        //Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        //Inserts new user into database
        $insert_sql = "INSERT INTO Users (first_name, last_name, email, password, created_at) 
                       VALUES (:first_name, :last_name, :email, :password, NOW())";
        
        $insert_stmt = $pdo->prepare($insert_sql);
        
        //Bind parameters
        $insert_stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
        $insert_stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
        $insert_stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        
        //Execute the query
        if ($insert_stmt->execute()) {
            // Success - redirect back with success message
            header("Location: create-account.php?created=1");
            exit();
        } else {
            // Database error
            header("Location: create-account.php?error=dberror");
            exit();
        }
        
    } catch(PDOException $e) {
        // Handle database errors
        error_log("Database error: " . $e->getMessage());
        header("Location: create-account.php?error=dberror");
        exit();
    }
} else {
    // If there are validation errors, you could store them in session and redirect back
    // For simplicity, we'll redirect with a generic error
    header("Location: create-account.php?error=validation");
    exit();
}
?>
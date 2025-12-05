<?php
// Database connection
$host = 'localhost';
$db   = 'cs2team44_db';
$user = 'cs2team44';
$pass = 'wpRwMNcuA4uajOG92dzRRqbhb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
session_start();
?>
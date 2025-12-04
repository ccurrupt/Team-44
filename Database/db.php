<?php
$host = "cs2410-web01pvm.aston.ac.uk";
$user = "cs2team44";
$password = "wpRwMNcuA4uajOG92dzRRqbhb";
$dbname = "cs2team44_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

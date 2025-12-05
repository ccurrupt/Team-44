<?php
require_once 'dbconfig.php';

// Remove all session data
$_SESSION = [];
session_destroy();

// Redirect back to the home page
header("Location: index.php");
exit();


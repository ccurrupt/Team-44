<?php
include 'db.php';

$sql = "SELECT * FROM Products LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    echo "No product found!";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $product['name']; ?></title>
</head>
<body>
    <h1><?php echo $product['name']; ?></h1>
    <img src="images/<?php echo $product['image']; ?>" width="300">
    <p>Price: £<?php echo $product['price']; ?></p>
    <p>Rating: <?php echo $product['rating']; ?> (<?php echo $product['reviews']; ?> reviews)</p>
    <p><?php echo $product['description']; ?></p>
</body>
</html>

<?php
include 'connect.php';

// Default query
$query = "SELECT * FROM products";
$conditions = [];
$params = [];

// Apply filters
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $conditions[] = "category = ?";
    $params[] = $_GET['category'];
}
if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $conditions[] = "price >= ?";
    $params[] = $_GET['min_price'];
}
if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $conditions[] = "price <= ?";
    $params[] = $_GET['max_price'];
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Prepare and execute query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}
?>
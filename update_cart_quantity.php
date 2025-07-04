<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['message' => 'Please log in to update cart']);
    exit;
}

// Fetch the user ID
$user_query = "SELECT id, name FROM users WHERE email = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "s", $_SESSION['username']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
$user_id = $user ? $user['id'] : 0;
$name = $user ? $user['name'] : '';
mysqli_stmt_close($user_stmt);

if (!$user_id) {
    echo json_encode(['message' => 'User not found']);
    exit;
}

// Get the product ID and quantity from the AJAX request
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['message' => 'Invalid product ID or quantity']);
    exit;
}

// Update the quantity in the cart
$update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
if (!$update_stmt) {
    echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($update_stmt, "iii", $quantity, $user_id, $product_id);
if (mysqli_stmt_execute($update_stmt)) {
    echo json_encode(['message' => 'Quantity updated']);
} else {
    echo json_encode(['message' => 'Error updating quantity']);
}
mysqli_stmt_close($update_stmt);

mysqli_close($conn);
?>
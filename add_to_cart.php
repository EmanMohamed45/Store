<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['message' => 'Please log in to add to cart']);
    exit;
}

// Fetch the user ID
$user_query = "SELECT id, name FROM users WHERE email = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
if (!$user_stmt) {
    echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
    exit;
}
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

// Get the product ID from the AJAX request
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
if ($product_id <= 0) {
    echo json_encode(['message' => 'Invalid product ID']);
    exit;
}

// Check if the product is already in the cart
$check_query = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
if (!$check_stmt) {
    echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $product_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$cart_item = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if ($cart_item) {
    // Increment quantity
    $new_quantity = $cart_item['quantity'] + 1;
    $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    if (!$update_stmt) {
        echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($update_stmt, "iii", $new_quantity, $user_id, $product_id);
    if (mysqli_stmt_execute($update_stmt)) {
        // Fetch updated cart count
        $count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
        $count_stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($count_stmt, "i", $user_id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        $cart_count = $count_row['count'];
        mysqli_stmt_close($count_stmt);

        echo json_encode(['message' => 'Product quantity updated in cart', 'cart_count' => $cart_count]);
    } else {
        echo json_encode(['message' => 'Error updating cart']);
    }
    mysqli_stmt_close($update_stmt);
} else {
    // Add to cart
    $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    if (!$insert_stmt) {
        echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $product_id);
    if (mysqli_stmt_execute($insert_stmt)) {
        // Fetch updated cart count
        $count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
        $count_stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($count_stmt, "i", $user_id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        $cart_count = $count_row['count'];
        mysqli_stmt_close($count_stmt);

        echo json_encode(['message' => 'Added to cart', 'cart_count' => $cart_count]);
    } else {
        echo json_encode(['message' => 'Error adding to cart']);
    }
    mysqli_stmt_close($insert_stmt);
}

mysqli_close($conn);
?>
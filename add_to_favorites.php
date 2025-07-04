<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['message' => 'Please log in to add to favorites']);
    exit;
}

// Fetch the user ID
$user_query = "SELECT id FROM users WHERE email = ?";
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

// Check if the product is already favorited
$check_query = "SELECT * FROM favorites WHERE user_id = ? AND product_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
if (!$check_stmt) {
    echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $product_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$is_favorited = mysqli_num_rows($check_result) > 0;
mysqli_stmt_close($check_stmt);

if ($is_favorited) {
    // Remove from favorites
    $delete_query = "DELETE FROM favorites WHERE user_id = ? AND product_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    if (!$delete_stmt) {
        echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($delete_stmt, "ii", $user_id, $product_id);
    if (mysqli_stmt_execute($delete_stmt)) {
        echo json_encode(['message' => 'Removed from favorites']);
    } else {
        echo json_encode(['message' => 'Error removing from favorites']);
    }
    mysqli_stmt_close($delete_stmt);
} else {
    // Add to favorites
    $insert_query = "INSERT INTO favorites (user_id, product_id) VALUES (?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    if (!$insert_stmt) {
        echo json_encode(['message' => 'Error preparing query: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $product_id);
    if (mysqli_stmt_execute($insert_stmt)) {
        echo json_encode(['message' => 'Added to favorites']);
    } else {
        echo json_encode(['message' => 'Error adding to favorites']);
    }
    mysqli_stmt_close($insert_stmt);
}

mysqli_close($conn);
?>
<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Fetch the user ID
$user_query = "SELECT id FROM users WHERE email = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "s", $_SESSION['username']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
$user_id = $user ? $user['id'] : 0;
mysqli_stmt_close($user_stmt);

if (!$user_id) {
    die("User not found.");
}

// Validate form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $payment_method = isset($_POST['payment_method']) && in_array($_POST['payment_method'], ['cash_on_delivery', 'online']) ? $_POST['payment_method'] : '';

    if (empty($address) || empty($payment_method)) {
        echo "<p style='color: red; text-align: center;'>Please fill in all required fields.</p>";
        exit;
    }

    // Fetch cart items
    $cart_query = "SELECT p.id, p.price, c.quantity FROM products p JOIN cart c ON p.id = c.product_id WHERE c.user_id = ?";
    $cart_stmt = mysqli_prepare($conn, $cart_query);
    mysqli_stmt_bind_param($cart_stmt, "i", $user_id);
    mysqli_stmt_execute($cart_stmt);
    $cart_result = mysqli_stmt_get_result($cart_stmt);

    $cart_items = [];
    $total_price = 0;
    while ($row = mysqli_fetch_assoc($cart_result)) {
        $cart_items[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }
    mysqli_stmt_close($cart_stmt);

    if (empty($cart_items)) {
        echo "<p style='color: red; text-align: center;'>Your cart is empty.</p>";
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert order
        $order_query = "INSERT INTO orders (user_id, total_price, payment_method, address) VALUES (?, ?, ?, ?)";
        $order_stmt = mysqli_prepare($conn, $order_query);
        mysqli_stmt_bind_param($order_stmt, "idss", $user_id, $total_price, $payment_method, $address);
        mysqli_stmt_execute($order_stmt);
        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($order_stmt);

        // Insert order items
        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $item_stmt = mysqli_prepare($conn, $item_query);
        foreach ($cart_items as $item) {
            mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            mysqli_stmt_execute($item_stmt);
        }
        mysqli_stmt_close($item_stmt);

        // Clear cart
        $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
        $clear_cart_stmt = mysqli_prepare($conn, $clear_cart_query);
        mysqli_stmt_bind_param($clear_cart_stmt, "i", $user_id);
        mysqli_stmt_execute($clear_cart_stmt);
        mysqli_stmt_close($clear_cart_stmt);

        // Commit transaction
        mysqli_commit($conn);

        // Redirect to orders page
        header('Location: orders.php?success=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<p style='color: red; text-align: center;'>Error processing order: " . $e->getMessage() . "</p>";
    }
}

mysqli_close($conn);
?>
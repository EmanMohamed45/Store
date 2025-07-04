<?php
session_start();
include 'connect.php';

$cart_count = 0;
if (isset($_SESSION['username'])) {
    $user_query = "SELECT id, name FROM users WHERE email = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "s", $_SESSION['username']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
$user_id = $user ? $user['id'] : 0;
$name = $user ? $user['name'] : '';
mysqli_stmt_close($user_stmt);

    if ($user_id) {
        $count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
        $count_stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($count_stmt, "i", $user_id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        $cart_count = $count_row['count'];
        mysqli_stmt_close($count_stmt);
    }
}

echo json_encode(['count' => $cart_count]);
mysqli_close($conn);
?>
<?php
include 'connect.php';

if (isset($_POST['qr_code'])) {
    $qr_code = mysqli_real_escape_string($conn, $_POST['qr_code']);
    $query = "SELECT id FROM products WHERE qr_code = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $qr_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['message' => 'Product added to cart!', 'product_id' => $row['id']]);
    } else {
        echo json_encode(['message' => '']);
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
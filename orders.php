<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
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
    die("User not found.");
}

// Fetch orders
$order_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$order_stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($order_stmt, "i", $user_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($order_result)) {
    $orders[] = $row;
}
mysqli_stmt_close($order_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="store.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
    <title>Your Orders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" Crossref>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="best" id="orders">
        <div class="specialHeader">
            <div class="circle" style="margin-right: 15px;"></div>
            <h3>Your Orders</h3>
            <div class="circle"></div>
        </div>
        <div class="bigbox">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-item">
                        <h4>Order #<?php echo $order['id']; ?></h4>
                        <p><strong>Total:</strong> EGP <?php echo number_format($order['total_price'], 2); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucwords($order['status']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                        <button class="view-details" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">View Details</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You have no orders.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function viewOrderDetails(orderId) {
            $.ajax({
                url: 'get_order_details.php',
                method: 'POST',
                data: { order_id: orderId },
                success: function(response) {
                    Swal.fire({
                        title: 'Order Details',
                        html: response.html,
                        icon: 'info',
                        confirmButtonText: 'Close',
                        confirmButtonColor: 'rgb(237, 188, 57)',
                    });
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error fetching order details. Please try again.',
                        icon: 'error',
                        confirmButtonColor: 'rgb(237, 188, 57)',
                    });
                }
            });
        }

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            Swal.fire({
                title: 'Order Placed!',
                text: 'Your order has been successfully placed.',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: 'rgb(237, 188, 57)',
            });
        <?php endif; ?>
    </script>
</body>
</html>
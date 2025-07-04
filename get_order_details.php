<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['message' => 'Please log in']);
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
    echo json_encode(['message' => 'User not found']);
    exit;
}

// Get the order ID
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['message' => 'Invalid order ID']);
    exit;
}

// Fetch order items
$query = "SELECT p.name, p.price, oi.quantity FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND oi.order_id IN (SELECT id FROM orders WHERE user_id = ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}
mysqli_stmt_close($stmt);

// Generate HTML for order details
ob_start();
?>
<table style="width:100%; border-collapse: collapse;">
    <thead>
        <tr style="border-bottom: 1px solid #ddd;">
            <th style="padding: 8px; text-align: left;">Product</th>
            <th style="padding: 8px; text-align: left;">Price</th>
            <th style="padding: 8px; text-align: left;">Quantity</th>
            <th style="padding: 8px; text-align: left;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php echo htmlspecialchars($item['name']); ?></td>
                <td style="padding: 8px;">EGP <?php echo number_format($item['price'], 2); ?></td>
                <td style="padding: 8px;"><?php echo $item['quantity']; ?></td>
                <td style="padding: 8px;">EGP <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$html = ob_get_clean();

echo json_encode(['html' => $html]);
mysqli_close($conn);
?>
<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Fetch the user ID and name
$user_query = "SELECT id, name FROM users WHERE email = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "s", $_SESSION['username']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
$user_id = $user ? $user['id'] : 0;
$name = $user ? $user['name'] : ''; // جلب الاسم
mysqli_stmt_close($user_stmt);

if (!$user_id) {
    die("User not found.");
}

// Fetch the user's cart items
$query = "SELECT p.*, c.quantity FROM products p JOIN cart c ON p.id = c.product_id WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cart_items = [];
$total_price = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $cart_items[] = $row;
    $total_price += $row['price'] * $row['quantity'];
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="store.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
    <title>Your Cart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="best" id="cart">
        <div class="specialHeader">
            <div class="circle" style="margin-right: 15px;"></div>
            <h3>Your Cart</h3>
            <div class="circle"></div>
        </div>
        <div class="bigbox">
            <?php if (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $item): ?>
            <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                <div class="cart-details">
                    <span><?php echo htmlspecialchars($item['name']); ?></span><br>
                    <span><?php echo htmlspecialchars($item['description']); ?></span><br>
                    <div class="price">
                        <span class="currency">EGP</span>
                        <span class="money"><?php echo number_format($item['price'], 2); ?></span>
                    </div>
                    <div class="quantity">
                        <label>Quantity:</label>
                        <input type="number" value="<?php echo $item['quantity']; ?>" min="1"
                            onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                    </div>
                    <div class="subtotal">
                        <span>Subtotal: EGP <span
                                class="subtotal-amount"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></span></span>
                    </div>
                    <button class="remove-from-cart"
                        onclick="removeFromCart(<?php echo $item['id']; ?>)">Remove</button>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="cart-total">
                <h4>Total: EGP <span id="total-price"><?php echo number_format($total_price, 2); ?></span></h4>
                <?php if (!empty($cart_items)): ?>
                <form id="checkout-form" method="POST" action="checkout.php">
                    <h5>Shipping Address</h5>
                    <div class="form-group">
                        <label for="address">Full Address</label>
                        <textarea name="address" id="address" class="form-control" required
                            placeholder="Enter your full address"></textarea>
                    </div>
                    <h5>Payment Method</h5>
                    <div class="form-group">
                        <label><input type="radio" name="payment_method" value="cash_on_delivery" checked> Cash on
                            Delivery</label>
                        <label><input type="radio" name="payment_method" value="online"> Online Payment</label>
                    </div>
                    <button type="submit" class="checkout-btn">Confirm Order</button>
                </form>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p>Your cart is empty.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        Swal.fire({
            title: 'Error',
            text: 'Quantity must be at least 1.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: 'rgb(237, 188, 57)',
        });
        return;
    }
    $.ajax({
        url: 'update_cart_quantity.php',
        method: 'POST',
        data: { product_id: productId, quantity: quantity },
        dataType: 'json', // تحديد نوع البيانات كـ JSON
        success: function(response) {
            if (response.message) {
                Swal.fire({
                    title: 'Success',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: 'rgb(237, 188, 57)',
                });
                // Update subtotal dynamically
                const price = parseFloat($(`.cart-item[data-product-id="${productId}"] .money`).text().replace(',', ''));
                const newSubtotal = (price * quantity).toFixed(2);
                $(`.cart-item[data-product-id="${productId}"] .subtotal-amount`).text(newSubtotal);

                // Update total price dynamically
                let total = 0;
                $('.cart-item').each(function() {
                    const itemPrice = parseFloat($(this).find('.money').text().replace(',', ''));
                    const itemQuantity = parseInt($(this).find('input[type="number"]').val());
                    total += itemPrice * itemQuantity;
                });
                $('#total-price').text(total.toFixed(2));

                // Update cart count in header
                $.ajax({
                    url: 'get_cart_count.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(countResponse) {
                        $('#cart-count').text(countResponse.count);
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: 'Unexpected response from server.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: 'rgb(237, 188, 57)',
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Error updating quantity. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: 'rgb(237, 188, 57)',
            });
        }
    });
}

function removeFromCart(productId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to remove this item from your cart?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'No, keep it',
        confirmButtonColor: 'rgb(237, 188, 57)',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'remove_from_cart.php',
                method: 'POST',
                data: { product_id: productId },
                dataType: 'json', // تحديد نوع البيانات كـ JSON
                success: function(response) {
                    if (response.message) {
                        Swal.fire({
                            title: 'Removed!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: 'rgb(237, 188, 57)',
                        });
                        $(`.cart-item[data-product-id="${productId}"]`).remove();

                        // Update total price dynamically
                        let total = 0;
                        $('.cart-item').each(function() {
                            const price = parseFloat($(this).find('.money').text().replace(',', ''));
                            const quantity = parseInt($(this).find('input[type="number"]').val());
                            total += price * quantity;
                        });
                        $('#total-price').text(total.toFixed(2));

                        // Update cart count in header
                        $.ajax({
                            url: 'get_cart_count.php',
                            method: 'GET',
                            dataType: 'json',
                            success: function(countResponse) {
                                $('#cart-count').text(countResponse.count);
                            }
                        });

                        // Show empty cart message if no items remain
                        if ($('.cart-item').length === 0) {
                            $('.bigbox').html('<p>Your cart is empty.</p>');
                        }
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'Unexpected response from server.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: 'rgb(237, 188, 57)',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error removing item from cart. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: 'rgb(237, 188, 57)',
                    });
                }
            });
        }
    });
}
</script>
</body>

</html>
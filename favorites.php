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

// Fetch the user's favorited products
$query = "SELECT p.* FROM products p JOIN favorites f ON p.id = f.product_id WHERE f.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$favorites = [];
while ($row = mysqli_fetch_assoc($result)) {
    $favorites[] = $row;
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
    <title>Your Favorites</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="best" id="favorites">
        <div class="specialHeader">
            <div class="circle" style="margin-right: 15px;"></div>
            <h3>Your Favorites</h3>
            <div class="circle"></div>
        </div>
        <div class="bigbox">
            <?php if (!empty($favorites)): ?>
                <?php foreach ($favorites as $product): ?>
                    <div class="imgbox">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="">
                        <br>
                        <span><?php echo htmlspecialchars($product['name']); ?></span><br>
                        <span><?php echo htmlspecialchars($product['description']); ?></span>
                        <br>
                        <div class="price">
                            <span class="currency">EGP</span>
                            <span class="money"><?php echo number_format($product['price'], 2); ?></span>
                            <?php if ($product['old_price']): ?>
                                <span class="old-price">EGP <?php echo number_format($product['old_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-heart favorite-icon favorited" data-product-id="<?php echo $product['id']; ?>" onclick="toggleFavorite(<?php echo $product['id']; ?>)"></i>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You have no favorited products.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
function toggleFavorite(productId) {
    $.ajax({
        url: 'add_to_favorites.php',
        method: 'POST',
        data: { product_id: productId },
        dataType: 'json', // تحديد نوع البيانات كـ JSON
        success: function(response) {
            if (response.message) {
                Swal.fire({
                    title: response.message === 'Added to favorites' ? 'Added!' : 'Removed!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: 'rgb(237, 188, 57)',
                });
                if (response.message === 'Removed from favorites') {
                    $(`i[data-product-id="${productId}"]`).closest('.imgbox').remove();
                    // تحديث عدد المفضلة في الهيدر
                    $.ajax({
                        url: 'get_favorite_count.php',
                        method: 'GET',
                        dataType: 'json',
                        success: function(countResponse) {
                            $('#favorite-count').text(countResponse.count);
                            if ($('.imgbox').length === 0) {
                                $('.bigbox').html('<p>You have no favorited products.</p>');
                            }
                        }
                    });
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
                text: 'Error processing request. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: 'rgb(237, 188, 57)',
            });
        }
    });
}
</script>
</body>
</html>
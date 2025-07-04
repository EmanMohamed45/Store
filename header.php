<?php
if (!isset($_SESSION)) {
    session_start();
}
include 'connect.php';

$favorite_count = 0;
$cart_count = 0;
$name = ''; // متغير لتخزين الاسم
if (isset($_SESSION['username'])) {
    $user_query = "SELECT id, name FROM users WHERE email = ?";
    $user_stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($user_stmt, "s", $_SESSION['username']);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    $user_id = $user ? $user['id'] : 0;
    $name = $user ? $user['name'] : ''; // جلب الاسم
    mysqli_stmt_close($user_stmt);

    if ($user_id) {
        // Favorites count
        $fav_count_query = "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?";
        $fav_count_stmt = mysqli_prepare($conn, $fav_count_query);
        mysqli_stmt_bind_param($fav_count_stmt, "i", $user_id);
        mysqli_stmt_execute($fav_count_stmt);
        $fav_count_result = mysqli_stmt_get_result($fav_count_stmt);
        $fav_count_row = mysqli_fetch_assoc($fav_count_result);
        $favorite_count = $fav_count_row['count'];
        mysqli_stmt_close($fav_count_stmt);

        // Cart count
        $cart_count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
        $cart_count_stmt = mysqli_prepare($conn, $cart_count_query);
        mysqli_stmt_bind_param($cart_count_stmt, "i", $user_id);
        mysqli_stmt_execute($cart_count_stmt);
        $cart_count_result = mysqli_stmt_get_result($cart_count_stmt);
        $cart_count_row = mysqli_fetch_assoc($cart_count_result);
        $cart_count = $cart_count_row['count'];
        mysqli_stmt_close($cart_count_stmt);
    }
}
?>

<div class="header">
    <div class="container">
        <div class="logo">
            <img src="Images/l_itech-png.png" alt="">
        </div>
        <div class="links">
            <ul>
                <li><a href="index.php#home">Home</a></li>
                <li><a href="index.php#comingSoon">Coming Soon</a></li>
                <li><a href="index.php#trending">Trending Products</a></li>
                <li><a href="favorites.php">Favorites (<span id="favorite-count"><?php echo $favorite_count; ?></span>)</a></li>
                <li><a href="cart.php">Cart (<span id="cart-count"><?php echo $cart_count; ?></span>)</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="index.php#contact">Contact</a></li>
                <?php if (isset($_SESSION['username'])): ?>
                    <li><a href="#">Welcome, <?php echo htmlspecialchars($name); ?></a></li>
                    <li><a href="#" onclick="confirmLogout()">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Log in</a></li>
                    <li><a href="signup.php">Sign up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
function confirmLogout() {
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "logout.php";
    }
}
</script>
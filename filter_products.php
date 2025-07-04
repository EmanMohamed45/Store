<?php
session_start();
include 'connect.php';

// Start output buffering
ob_start();

// Set JSON header
header('Content-Type: application/json');

// Fetch products from the database
$query = "SELECT * FROM products";
$conditions = [];
$params = [];
$param_types = "";

// Apply category filter
if (isset($_POST['categories']) && !empty($_POST['categories']) && is_array($_POST['categories'])) {
    $categories = array_map(function($cat) use ($conn) {
        return mysqli_real_escape_string($conn, $cat);
    }, $_POST['categories']);
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $conditions[] = "category IN ($placeholders)";
    $params = array_merge($params, $categories);
    $param_types .= str_repeat('s', count($categories));
}

// Apply price filters
if (isset($_POST['min_price']) && is_numeric($_POST['min_price']) && $_POST['min_price'] >= 0) {
    $conditions[] = "price >= ?";
    $params[] = (float)$_POST['min_price'];
    $param_types .= 'd';
}
if (isset($_POST['max_price']) && is_numeric($_POST['max_price']) && $_POST['max_price'] >= 0) {
    $conditions[] = "price <= ?";
    $params[] = (float)$_POST['max_price'];
    $param_types .= 'd';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Apply sorting
if (isset($_POST['sort']) && !empty($_POST['sort'])) {
    if ($_POST['sort'] === 'price_asc') {
        $query .= " ORDER BY price ASC";
    } elseif ($_POST['sort'] === 'price_desc') {
        $query .= " ORDER BY price DESC";
    }
}

// Prepare and execute query
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    ob_end_clean();
    echo json_encode(['error' => 'Query preparation failed: ' . mysqli_error($conn)]);
    exit;
}

if (!empty($params)) {
    if (!mysqli_stmt_bind_param($stmt, $param_types, ...$params)) {
        ob_end_clean();
        echo json_encode(['error' => 'Parameter binding failed: ' . mysqli_stmt_error($stmt)]);
        exit;
    }
}

if (!mysqli_stmt_execute($stmt)) {
    ob_end_clean();
    echo json_encode(['error' => 'Query execution failed: ' . mysqli_stmt_error($stmt)]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    ob_end_clean();
    echo json_encode(['error' => 'Failed to get query results: ' . mysqli_stmt_error($stmt)]);
    exit;
}

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

mysqli_stmt_close($stmt);

// Generate HTML for the filtered products
ob_start();
if (!empty($products)) {
    foreach ($products as $product) {
        $favorited = false;
        if (isset($_SESSION['username'])) {
            $user_query = "SELECT id, name FROM users WHERE email = ?";
            $user_stmt = mysqli_prepare($conn, $user_query);
            if (!$user_stmt) {
                ob_end_clean();
                echo json_encode(['error' => 'User query preparation failed: ' . mysqli_error($conn)]);
                exit;
            }
            mysqli_stmt_bind_param($user_stmt, "s", $_SESSION['username']);
            if (!mysqli_stmt_execute($user_stmt)) {
                ob_end_clean();
                echo json_encode(['error' => 'User query execution failed: ' . mysqli_stmt_error($user_stmt)]);
                exit;
            }
            $user_result = mysqli_stmt_get_result($user_stmt);
            if (!$user_result) {
                ob_end_clean();
                echo json_encode(['error' => 'Failed to get user query results: ' . mysqli_stmt_error($user_stmt)]);
                exit;
            }
            $user = mysqli_fetch_assoc($user_result);
            $user_id = $user ? $user['id'] : 0;
            $name = $user ? $user['name'] : '';
            mysqli_stmt_close($user_stmt);

            if ($user_id) {
                $fav_query = "SELECT * FROM favorites WHERE user_id = ? AND product_id = ?";
                $fav_stmt = mysqli_prepare($conn, $fav_query);
                if (!$fav_stmt) {
                    ob_end_clean();
                    echo json_encode(['error' => 'Favorite query preparation failed: ' . mysqli_error($conn)]);
                    exit;
                }
                mysqli_stmt_bind_param($fav_stmt, "ii", $user_id, $product['id']);
                if (!mysqli_stmt_execute($fav_stmt)) {
                    ob_end_clean();
                    echo json_encode(['error' => 'Favorite query execution failed: ' . mysqli_stmt_error($fav_stmt)]);
                    exit;
                }
                $fav_result = mysqli_stmt_get_result($fav_stmt);
                if (!$fav_result) {
                    ob_end_clean();
                    echo json_encode(['error' => 'Failed to get favorite query results: ' . mysqli_stmt_error($fav_stmt)]);
                    exit;
                }
                $favorited = mysqli_num_rows($fav_result) > 0;
                mysqli_stmt_close($fav_stmt);
            }
        }
        ?>
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
            <?php if (isset($_SESSION['username'])): ?>
                <i class="fas fa-heart favorite-icon <?php echo $favorited ? 'favorited' : ''; ?>" data-product-id="<?php echo $product['id']; ?>" onclick="toggleFavorite(<?php echo $product['id']; ?>)"></i>
                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
            <?php endif; ?>
        </div>
        <?php
    }
} else {
    echo "<p>No products found.</p>";
}

$html = ob_get_clean();
ob_end_clean();
echo json_encode(['html' => $html]);
mysqli_close($conn);
exit;
?>
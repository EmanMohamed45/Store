<?php
// Start the session
session_start();
include 'connect.php';

// Fetch distinct categories from the products table
$category_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL";
$category_result = mysqli_query($conn, $category_query);
$categories = [];
while ($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row['category'];
}

// Fetch products from the database (initial load)
$query = "SELECT * FROM products";
$conditions = [];
$params = [];
$param_types = "";

// Apply category filter (handle multiple categories)
if (isset($_GET['categories']) && !empty($_GET['categories'])) {
    $categories_selected = array_map('mysqli_real_escape_string', array_fill(0, count($_GET['categories']), $conn), $_GET['categories']);
    $placeholders = implode(',', array_fill(0, count($categories_selected), '?'));
    $conditions[] = "category IN ($placeholders)";
    $params = array_merge($params, $categories_selected);
    $param_types .= str_repeat('s', count($categories_selected));
}

// Apply price filters
if (isset($_GET['min_price']) && is_numeric($_GET['min_price']) && $_GET['min_price'] >= 0) {
    $conditions[] = "price >= ?";
    $params[] = $_GET['min_price'];
    $param_types .= 'd';
}
if (isset($_GET['max_price']) && is_numeric($_GET['max_price']) && $_GET['max_price'] >= 0) {
    $conditions[] = "price <= ?";
    $params[] = $_GET['max_price'];
    $param_types .= 'd';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Apply sorting
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    if ($_GET['sort'] === 'price_asc') {
        $query .= " ORDER BY price ASC";
    } elseif ($_GET['sort'] === 'price_desc') {
        $query .= " ORDER BY price DESC";
    }
}

// Prepare and execute query
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Query preparation failed: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}

if (!mysqli_stmt_execute($stmt)) {
    die("Query execution failed: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    die("Failed to get query results: " . mysqli_stmt_error($stmt));
}

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
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
    <title>Electronic Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js"></script>
    <script src="https://rawgit.com/schmich/instascan/master/build/instascan.min.js"></script>
    <video id="preview" style="display:none;"></video>
</head>

<body>
    <!-- Include the header -->
    <?php include 'header.php'; ?>

    <!-- Start home page -->
    <div class="home" id="home">
        <div class="container">
            <div class="intro">
                <h1>Get 15% off your first order</h1>
                <p>All your requirements in one place</p>
                <div class="code">
                    use code: welcome
                </div>
            </div>
        </div>
    </div>
    <!-- End home page -->

    <!-- Start Coming Soon page -->
    <div class="gallery" id="comingSoon">
        <div class="specialHeader">
            <div class="circle" style="margin-right: 15px;"></div>
            <h3>coming soon</h3>
            <div class="circle"></div>
        </div>
        <div class="container">
            <div class="imgbox"><img src="Images/top-view-smartphone-with-keyboard-charger.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/Screenshot 2023-04-02 232442.png" alt=""></div>
            <div class="imgbox"><img src="Images/photo_2023-04-02_23-07-17.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/Screenshot 2023-04-02 232538.png" alt=""></div>
            <div class="imgbox"><img src="Images/smartphone-with-tablet-headphones-table.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/photo_2023-04-02_23-27-14.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/photo_10_2023-04-03_00-15-00.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/photo_7_2023-04-03_00-15-00.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/photo_15_2023-04-03_00-15-00.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/photo_17_2023-04-03_00-15-00.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/photo_22_2023-04-03_00-15-00.jpg" alt=""></div>
            <div class="imgbox"><img src="Images/photo_2023-04-02_23-07-19.jpg" alt=""></div>
        </div>
    </div>
    <!-- End Coming Soon page -->
    
    <!-- Start Filter Form -->
    <div class="filter-form">
        <form id="filter-form" onsubmit="applyFilters(); return false;">
            <label>Categories:</label><br>
            <input type="checkbox" name="categories[]" value="Laptop"> Laptop<br>
            <input type="checkbox" name="categories[]" value="Accessories"> Accessories<br>
            <input type="checkbox" name="categories[]" value="Smartphone"> Smartphone<br>
            <label for="min_price">Min Price:</label>
            <input type="number" name="min_price" placeholder="Min Price" value="" min="0">
            <label for="max_price">Max Price:</label>
            <input type="number" name="max_price" placeholder="Max Price" value="" min="0">
            <label for="sort">Sort By:</label>
            <select name="sort">
                <option value="">Default</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
            </select>
            <button type="submit">Filter</button>
            <a href="#" onclick="clearFilters(); return false;" class="clear-filters">Clear Filters</a>
        </form>
    </div>
    <!-- End Filter Form -->

    <!-- Start Trending Products page -->
    <div class="best" id="trending">
        <div class="specialHeader">
            <div class="circle" style="margin-right: 15px;"></div>
            <h3>Trending Products</h3>
            <div class="circle"></div>
        </div>
        <div class="bigbox" id="product-list">
            <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
            <?php
            $favorited = false;
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
                    $fav_query = "SELECT * FROM favorites WHERE user_id = ? AND product_id = ?";
                    $fav_stmt = mysqli_prepare($conn, $fav_query);
                    if ($fav_stmt) {
                        mysqli_stmt_bind_param($fav_stmt, "ii", $user_id, $product['id']);
                        mysqli_stmt_execute($fav_stmt);
                        $fav_result = mysqli_stmt_get_result($fav_stmt);
                        $favorited = mysqli_num_rows($fav_result) > 0;
                        mysqli_stmt_close($fav_stmt);
                    }
                }
            }
            ?>
            <div class="imgbox">
                <img src="placeholder.jpg" data-src="<?php echo htmlspecialchars($product['image']); ?>"
                    class="lazyload" alt="">
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
                <i class="fas fa-heart favorite-icon <?php echo $favorited ? 'favorited' : ''; ?>"
                    data-product-id="<?php echo $product['id']; ?>"
                    onclick="toggleFavorite(<?php echo $product['id']; ?>)"></i>
                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>"
                    onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p>No products found.</p>
            <?php endif; ?>
        </div>
    </div>
    <!-- End Trending Products page -->

    <!-- Start contact page -->
    <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d23285.506316962314!2d-79.4088180755119!3d43.63922549729466!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89d4cb90d7c63ba5%3A0x323555502ab4c477!2sToronto%2C%20ON%2C%20Canada!5e0!3m2!1sen!2sus!4v1681423329065!5m2!1sen!2sus"
        width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"></iframe>
    <div class="contact" id="contact">
        <div class="left">
            <a href="contact.php" target="_blank">Questions? Contact Us...</a>
        </div>
        <div class="right">
            <img src="Images/contact1.png" alt="">
            <img src="Images/contact2.png" alt="">
            <img src="Images/contact3.png" alt="">
        </div>
    </div>
    <!-- End contact page -->
    <script>
        function validateFilterForm() {
            const minPrice = document.querySelector('input[name="min_price"]').value;
            const maxPrice = document.querySelector('input[name="max_price"]').value;

            if (minPrice !== '' && maxPrice !== '' && parseFloat(minPrice) > parseFloat(maxPrice)) {
                Swal.fire({
                    title: 'Error',
                    text: 'Minimum price cannot be greater than maximum price.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return false;
            }
            return true;
        }

        function applyFilters() {
            if (!validateFilterForm()) return;

            const formData = $('#filter-form').serializeArray();
            $.ajax({
                url: 'filter_products.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        Swal.fire({
                            title: 'Error',
                            text: response.error,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: 'rgb(237, 188, 57)',
                        });
                    } else {
                        $('#product-list').html(response.html);
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error fetching filtered products. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: 'rgb(237, 188, 57)',
                    });
                }
            });
        }

        function clearFilters() {
            $('#filter-form')[0].reset();
            applyFilters();
        }

        function toggleFavorite(productId) {
            $.ajax({
                url: 'add_to_favorites.php',
                method: 'POST',
                data: {
                    product_id: productId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.message) {
                        Swal.fire({
                            title: response.message === 'Added to favorites' ? 'Added!' : 'Removed!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: 'rgb(237, 188, 57)',
                        });
                        $(`i[data-product-id="${productId}"]`).toggleClass('favorited');
                        $.ajax({
                            url: 'get_favorite_count.php',
                            method: 'GET',
                            dataType: 'json',
                            success: function(countResponse) {
                                $('#favorite-count').text(countResponse.count);
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
                        text: 'Error processing request. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: 'rgb(237, 188, 57)',
                    });
                }
            });
        }

        function addToCart(productId) {
            $.ajax({
                url: 'add_to_cart.php',
                method: 'POST',
                data: {
                    product_id: productId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.message) {
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: 'rgb(237, 188, 57)',
                        });
                        $('#cart-count').text(response.cart_count);
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
                        text: 'Error adding to cart. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: 'rgb(237, 188, 57)',
                    });
                }
            });
        }

        function scanProduct() {
            Swal.fire({
                title: 'Scan Product',
                text: 'Please scan the QR code on the product.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Start Scanning',
                confirmButtonColor: 'rgb(237, 188, 57)',
            }).then((result) => {
                if (result.isConfirmed) {
                    let scanner = new Instascan.Scanner({
                        video: document.getElementById('preview')
                    });
                    scanner.addListener('scan', function(content) {
                        $.ajax({
                            url: 'check_product.php',
                            method: 'POST',
                            data: {
                                qr_code: content
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.message) {
                                    Swal.fire({
                                        title: 'Success',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK',
                                        confirmButtonColor: 'rgb(237, 188, 57)',
                                    });
                                    addToCart(response.product_id);
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Product not found.',
                                        icon: 'error',
                                        confirmButtonText: 'OK',
                                        confirmButtonColor: 'rgb(237, 188, 57)',
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Error scanning product.',
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: 'rgb(237, 188, 57)',
                                });
                            }
                        });
                        scanner.stop();
                    });
                    Instascan.Camera.getCameras().then(function(cameras) {
                        if (cameras.length > 0) {
                            scanner.start(cameras[0]);
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: 'No camera found.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: 'rgb(237, 188, 57)',
                            });
                        }
                    }).catch(function(e) {
                        Swal.fire({
                            title: 'Error',
                            text: e,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: 'rgb(237, 188, 57)',
                        });
                    });
                }
            });
        }
    </script>
</body>

</html>
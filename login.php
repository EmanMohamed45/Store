<?php
session_start();

// Reset login attempts if enough time has passed since the last attempt
if (isset($_SESSION['last_attempt_time']) && (time() - $_SESSION['last_attempt_time']) > 5) {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_attempt_time']);
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Login Page</title>
    <link rel="stylesheet" type="text/css" href="login.css">
</head>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');

    emailInput.addEventListener('input', function() {
        if (!this.checkValidity()) {
            this.style.borderBottomColor = '#ff4444';
        } else {
            this.style.borderBottomColor = '#fff';
        }
    });

    passwordInput.addEventListener('input', function() {
        if (this.value.length < 6) {
            this.style.borderBottomColor = '#ff4444';
        } else {
            this.style.borderBottomColor = '#fff';
        }
    });
});
</script>

<body>

    <div class="login-box">
        <h2><q> Log in </q></h2>
        <form action="" method="post">
            <p>Username</p>
            <input type="text" name="username" placeholder="Enter Email" required>
            <p>Password</p>
            <input type="password" name="password" placeholder="Enter Password" required>
            <input type="submit" name="Login" value="Login">
            <a href="signup.php">Don't have an account? Sign up</a>
        </form>
    </div>

    <?php
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['username'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Rate limiting
    $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
    $_SESSION['last_attempt_time'] = time(); // Record the time of this attempt

    if ($_SESSION['login_attempts'] > 5) {
        sleep(10); // Delay 5 seconds after 5 failed attempts
        // Reset attempts after delay
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_attempt_time']);
        echo "<p style='color: red; text-align: center;'>Too many attempts. You can now try again.</p>";
    } else {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $email;
            $_SESSION['login_attempts'] = 0; // Reset attempts on success
            unset($_SESSION['last_attempt_time']);
            header("Location: index.php");
            exit;
        } else {
            echo "<p style='color: red; text-align: center;'>Invalid email or password. Attempt " . $_SESSION['login_attempts'] . " of 5.</p>";
        }
    }

    mysqli_close($conn);
}
?>
</body>

</html>
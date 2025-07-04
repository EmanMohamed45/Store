<!DOCTYPE html>
<html>
<head>
    <title>Signup Page</title>
    <link rel="stylesheet" type="text/css" href="signup.css">
</head>
<body>

<div class="signup-box">
    <h2><q> Sign up </q></h2>
    <form action="signup.php" method="post">
        <p>Name</p>
        <input type="text" name="name" placeholder="Enter Name" required minlength="2" maxlength="50">
        <p>Email</p>
        <input type="email" name="email" placeholder="Enter Email" required>
        <p>Password</p>
        <input type="password" name="password" placeholder="Enter Password (min 6 chars)" required minlength="6">
        <input type="submit" name="Signup" value="Signup">
        <a href="login.php">Already have an account? Log in</a>
    </form>
</div>

<?php
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    $check_query = "SELECT email FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    if (mysqli_num_rows($check_result) > 0) {
        echo "<p class='error-message'>Email already in use.</p>";
    } else {
        $query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $password);
        if (mysqli_stmt_execute($stmt)) {
            echo "<p class='success-message'>Signup successful! Redirecting to login...</p>";
            header("refresh:2;url=login.php");
            exit;
        } else {
            echo "<p class='error-message'>Error: " . mysqli_stmt_error($stmt) . "</p>";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_stmt_close($check_stmt);
    mysqli_close($conn);
}
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.querySelector('input[name="name"]');
    const emailInput = document.querySelector('input[name="email"]');
    const passwordInput = document.querySelector('input[name="password"]');

    nameInput.addEventListener('input', function() {
        if (!this.checkValidity()) {
            this.style.borderBottomColor = '#ff4444';
        } else {
            this.style.borderBottomColor = '#fff';
        }
    });

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
</body>
</html>
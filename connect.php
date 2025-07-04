<?php
$host = "localhost";
$user = "root";
$password = "";
$databaseName = "elctro_stor";
// $port = 3307;
$conn = mysqli_connect($host, $user, $password, $databaseName);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
<?php

require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV["host"];
$user = $_ENV["user"];
$pass = $_ENV["pass"];
$db = $_ENV["db"];

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    echo "Failed to connect to database!";
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_select_db($conn, $db);
?>

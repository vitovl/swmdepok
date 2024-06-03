<?php
include "../koneksi.php";
require '../vendor/autoload.php';

use \Firebase\JWT\JWT;

function login() {
    global $conn;
    $response = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Membaca dan menguraikan data JSON
        $data = json_decode(file_get_contents('php://input'), true);

        // Tambahkan log untuk debug
        error_log(print_r($data, true));

        // Memastikan data JSON diterima dan diurai dengan benar
        if (!isset($data["email"]) || empty($data["email"])) {
            $response = ['status' => 'error', 'message' => 'Please Enter Your Email Details'];
        } else if (!isset($data["password"]) || empty($data["password"])) {
            $response = ['status' => 'error', 'message' => 'Please Enter Your Password Details'];
        } else {
            $email = mysqli_real_escape_string($conn, $data["email"]);
            $password = $data["password"];

            $sqllogin = "SELECT * FROM user_login WHERE email = '$email'";
            $query = mysqli_query($conn, $sqllogin);

            if (mysqli_num_rows($query) > 0) {
                $user = mysqli_fetch_assoc($query);
                if (password_verify($password, $user['password'])) {
                    // Kode pembuatan token
                    $key = "fjaodanifeiraifanneiona3937582"; // Ganti dengan kunci rahasia Anda
                    $expiryTime = time() + (24 * 60 * 60); // 24 jam
                    $payload = [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'exp' => $expiryTime
                    ];
                    $token = JWT::encode($payload, $key, 'HS256'); // Tambahkan algoritma enkripsi
                    // Akhir kode pembuatan token

                    $response = [
                        'status' => 'success',
                        'message' => 'Login successful',
                        'token' => $token
                    ];
                } else {
                    header("HTTP/1.0 400 Bad Request");
                    $response = ['status' => 'error', 'message' => 'Invalid Email or Password'];
                }
            } else {
                header("HTTP/1.0 404 Not Found");
                $response = ['status' => 'error', 'message' => 'User not found. Please register first.'];
            }
        }
    } 
    // else {
    //     header("HTTP/1.0 405 Method Not Allowed");
    //     $response = ['status' => 'error', 'message' => 'Invalid request method'];
    // }

    header('Content-Type: application/json');
    echo json_encode($response);
}
login();
?>

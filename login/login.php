<?php
include "../koneksi.php";


function login() {
    global $conn;
    $response = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data["email"])) {
            $response = ['status' => 'error', 'message' => 'Please Enter Your Email Details'];
        } else if (empty($data["password"])) {
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
                    $token = \Firebase\JWT\JWT::encode($payload, $key, 'HS256'); // Tambahkan algoritma enkripsi
                    // Akhir kode pembuatan token

                    $response = [
                        'status' => 'success',
                        'message' => 'Login successful',
                        'token' => $token
                    ];
                } else {
                    $response = ['status' => 'error 409', 'message' => 'Invalid Email or Password'];
                }
            } else {
                $response = ['status' => 'error 410', 'message' => 'User not found. Please register first.'];
            }
        }
    } else {
        $response = ['status' => 'error 404', 'message' => 'Invalid request method'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}
login();

?>
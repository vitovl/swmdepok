<?php

include "../koneksi.php";

function createToken($userId, $email) {
    $expiryTime = time() + (24 * 60 * 60); // 24 jam
    $tokenPayload = json_encode([
        'id' => $userId,
        'email' => $email,
        'exp' => $expiryTime
    ]);
    return base64_encode($tokenPayload);
}

function login() {
    global $conn;
    $response = []; // Inisialisasi respon

    // Memeriksa apakah metode permintaan adalah POST
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
                // Pengguna sudah ada, lakukan login
                $user = mysqli_fetch_assoc($query);
                if (password_verify($password, $user['password'])) {
                    $token = createToken($user['id'], $user['email']);
                    $response = [
                        'status' => 'success',
                        'message' => 'Login successful',
                        'token' => $token
                    ];
                } else {
                    $response = ['status' => 'error', 'message' => 'Invalid Email or Password'];
                }
            } else {
                // Pengguna belum ada, lakukan pendaftaran
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sqlregister = "INSERT INTO user_login (email, password) VALUES ('$email', '$hashedPassword')";
                $query = mysqli_query($conn, $sqlregister);

                if ($query) {
                    $user_id = mysqli_insert_id($conn);
                    $token = createToken($user_id, $email);
                    $response = [
                        'status' => 'success',
                        'message' => 'Registration successful',
                        'token' => $token
                    ];
                } else {
                    $response = ['status' => 'error', 'message' => 'Registration failed'];
                }
            }
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Invalid request method'];
    }

    header('Content-Type: application/json');
    return json_encode($response);
}
?>

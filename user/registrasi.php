<?php
include "../koneksi.php";

function isPasswordStrong($password) {
    // Memeriksa apakah password memiliki minimal satu karakter besar, satu angka, dan minimal delapan karakter panjang
    return preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

function register() {
    global $conn;
    $response = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data["email"]) || !filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            $response = ['status' => 'error', 'message' => 'Please Enter a valid Email'];
        } else if (!preg_match('/@gmail\.com$/', $data["email"])) {
            $response = ['status' => 'error', 'message' => 'Please Enter a Gmail Email'];
        } else if (empty($data["password"]) || !isPasswordStrong($data["password"])) {
            $response = ['status' => 'error', 'message' => 'Password should contain at least one uppercase letter, one digit, and minimum length of 8 characters'];
        } else {
            $email = mysqli_real_escape_string($conn, $data["email"]);
            $password = $data["password"];

            $sqlCheckUser = "SELECT * FROM user_login WHERE email = '$email'";
            $queryCheckUser = mysqli_query($conn, $sqlCheckUser);

            if (mysqli_num_rows($queryCheckUser) > 0) {
                $response = ['status' => 'error', 'message' => 'User already exists. Please login instead.'];
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sqlRegisterUser = "INSERT INTO user_login (email, password) VALUES ('$email', '$hashedPassword')";
                $queryRegisterUser = mysqli_query($conn, $sqlRegisterUser);

                if ($queryRegisterUser) {
                    $user_id = mysqli_insert_id($conn);
                    // Pembuatan token
                    $key = "example_key"; // Ganti dengan kunci rahasia Anda
                    $expiryTime = time() + (24 * 60 * 60); // 24 jam
                    $payload = [
                        'id' => $user_id,
                        'email' => $email,
                        'exp' => $expiryTime
                    ];
                    $token = \Firebase\JWT\JWT::encode($payload, $key, 'HS256'); // Tambahkan argumen ketiga 'HS256'
                    // Akhir pembuatan token

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
    echo json_encode($response);
}
register();

?>
    
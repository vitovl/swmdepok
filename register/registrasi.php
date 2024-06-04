<?php
include "../koneksi.php";

function isPasswordStrong($password) {
    // Memeriksa apakah password memiliki minimal satu karakter besar, satu angka, dan minimal delapan karakter panjang
    return preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

function register() {
    global $conn;
    $response = [];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
                header("HTTP/1.0 400 Bad Request Error");
                $response = ['status' => 'error', 'message' => 'User already exists. Please login instead.'];
                echo json_encode($response);
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sqlRegisterUser = "INSERT INTO user_login (email, password) VALUES ('$email', '$hashedPassword')";
                $queryRegisterUser = mysqli_query($conn, $sqlRegisterUser);

                if ($queryRegisterUser) {
                    header("HTTP/1.0 201 Created");
                    header('Content-Type: application/json');
                    $response = [
                        'status' => 'success',
                        'message' => 'Registration successful. Your account has been created. Please login.'
                    ];
                    echo json_encode($response);
                } else {
                    header("HTTP/1.0 409");
                    header('Content-Type: application/json');
                    $response = ['status' => 'error 409', 'message' => 'Registration failed'];
                    echo json_encode($response);
                }
            }
        }
    } else {
        $response = ['status' => 'error 404', 'message' => 'Invalid request method'];
    }

    
}

?>

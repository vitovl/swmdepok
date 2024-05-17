<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Accept, Content-Type, ngrok-skip-browser-warning');
header('Access-Control-Allow-Method: GET, POST, PUT, DELETE');

include './login.php';
// include './token.php';

$reqMethod = $_SERVER["REQUEST_METHOD"];

if ($reqMethod === "POST") {
    // Jika metode adalah POST, lakukan login
    $loginData = login();
    echo $loginData;
} else {
    // Jika metode bukan POST, kirimkan respon metode tidak diizinkan
    $data = [
        'status' => 405,
        'message' => $reqMethod . ' Method not allowed!'
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

?>

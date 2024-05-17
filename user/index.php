<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Accept, Content-Type, ngrok-skip-browser-warning, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
require '../vendor/autoload.php';
include './login.php';
include './registrasi.php';
include './token.php';
$reqMethod = $_SERVER["REQUEST_METHOD"];

if ($reqMethod === "POST") {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);

    if ((isset($uri[2]) && $uri[2] == 'login')) {
        include './login.php';
    } elseif ((isset($uri[2]) && $uri[2] == 'register')) {
        include './registrasi.php';
    } elseif ((isset($uri[2]) && $uri[2] == 'verify-token')) {
        include './token.php';
    } else {
        $data = [
            'status' => 404,
            'message' => 'Endpoint not found!'
        ];
        header("HTTP/1.0 404 Not Found");
        echo json_encode($data);
    }
} else {
    $data = [
        'status' => 405,
        'message' => $reqMethod . ' Method not allowed!'
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

?>

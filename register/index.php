<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Accept, Content-Type, ngrok-skip-browser-warning, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
require '../vendor/autoload.php';

include './registrasi.php';
$reqMethod = $_SERVER["REQUEST_METHOD"];

if ($reqMethod === "POST") {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);

    if ((isset($uri[3]) && $uri[3] == 'registrasi')) {
        include './registrasi.php';
    } 
    
} else {
    $data = [
        "status" => 405,
        "message" => $reqMethod . ' Method not allowed!'
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
    exit; // tambahkan exit di sini
}
?>

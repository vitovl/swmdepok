<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Accept, Content-Type, ngrok-skip-browser-warning');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// require '../vendor/autoload.php';
include_once './lokasiservice.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Directly call the getLokasiDevice function without token validation
    getLokasiDevice();
} else {
    $data = [
        "status" => 405,
        "message" => "Invalid request method"
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    header('Content-Type: application/json');
    echo json_encode($data);
}
?>

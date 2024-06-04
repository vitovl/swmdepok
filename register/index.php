<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Accept, Content-Type, ngrok-skip-browser-warning');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

require '../vendor/autoload.php';
include_once './registrasi.php';


// Modifikasi logika untuk memanggil fungsi register() saat metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    register();
} else {
    // Tambahkan respons jika metode selain POST
    $data = [
        "status" => 405,
        "message" => "Invalid request method"
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    header('Content-Type: application/json');
    echo json_encode($data);
}
?>

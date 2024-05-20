<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Accept, Content-Type, ngrok-skip-browser-warning, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

require '../vendor/autoload.php';
include './chartservice.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function verifyToken($token) {
    $key = "fjaodanifeiraifanneiona3937582";
    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        error_log("Token verification failed: " . $e->getMessage()); // Log the error message
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => 400,
            "message" => "Authorization header missing"
        ]);
        exit;
    }

    $authHeader = $headers['Authorization'];
    $tokenParts = explode(" ", $authHeader);
    if (count($tokenParts) !== 2 || $tokenParts[0] !== 'Bearer') {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => 400,
            "message" => "Invalid token format"
        ]);
        exit;
    }
    $token = $tokenParts[1];

   // Setelah mendapatkan token
// Setelah mendapatkan token
$user = verifyToken($token);

if ($user) {
    // Token valid, lanjutkan dengan mendapatkan data grafik
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
    $skip = isset($_GET['skip']) ? $_GET['skip'] : 0;
    $allChartData = getAllDataGraphics($limit, $skip);
    echo $allChartData;
} else {
    // Token tidak valid, berikan respons yang sesuai
    $response = [
        "status" => 401,
        "message" => "Invalid or expired token"
    ];
    header("HTTP/1.0 401 Unauthorized");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // tambahkan exit untuk menghentikan eksekusi kode
}
}
?>

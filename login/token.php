<?php

include "../koneksi.php";
require '../vendor/autoload.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $user = verifyToken($token);

    if ($user) {
        $response = [
            "status" => 200,
            "message" => "Token is valid",
            "data" => $user
        ];
        header("HTTP/1.0 200 OK");
    } else {
        $response = [
            "status" => 401,
            "message" => "Invalid or expired token"
        ];
        header("HTTP/1.0 401 Unauthorized");
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    $data = [
        "status" => 405,
        "message" => "Invalid request method"
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

?>
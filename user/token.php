<?php

include "../koneksi.php";
require '../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function verifyToken($token) {
    $key = "example_key"; // Ganti dengan kunci rahasia Anda
    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Authorization header missing']);
        exit;
    }

    // Authorization header bisa berupa "Bearer <token>"
    $authHeader = $headers['Authorization'];
    $tokenParts = explode(" ", $authHeader);
    if (count($tokenParts) !== 2 || $tokenParts[0] !== 'Bearer') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid token format']);
        exit;
    }
    $token = $tokenParts[1];

    $user = verifyToken($token);

    if ($user) {
        $response = ['status' => 'success', 'message' => 'Token is valid', 'data' => $user];
    } else {
        $response = ['status' => 'error', 'message' => 'Invalid or expired token'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

?>

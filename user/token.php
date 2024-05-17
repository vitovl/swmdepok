<?php

include "../koneksi.php";

function verifyToken($token) {
    $decodedPayload = base64_decode($token);
    $tokenData = json_decode($decodedPayload, true);

    if ($tokenData['exp'] < time()) {
        return false; // Token telah kadaluarsa
    }
    return $tokenData;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Ubah GET menjadi POST
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Authorization header missing']);
        exit;
    }

    $token = $headers['Authorization'];
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

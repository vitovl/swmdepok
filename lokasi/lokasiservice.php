<?php
include '../koneksi.php';

function getLokasiDevice(){
    global $conn;

    $queryLocate = "SELECT device_name, latitude, longitude
                    FROM lokasi_device_depok";
    $resultLocate = mysqli_query($conn, $queryLocate);

    if ($resultLocate) {
        $data = [];
        while ($row = mysqli_fetch_assoc($resultLocate)) {
            $data[] = [
                "device_name" => $row['device_name'],
                "latitude" => $row['latitude'],
                "longitude" => $row['longitude'],
            ];
        }

        return json_encode($data); // Return the JSON data instead of echoing
    } else {
        $response = [
            "status" => 500,
            "message" => "Internal server error"
        ];
        header("HTTP/1.0 500 Server Error");
        header('Content-Type: application/json');
        return json_encode($response); // Return the JSON response
    }
}


?>

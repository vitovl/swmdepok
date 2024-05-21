<?php
include '../koneksi.php';

function getLokasiDevice() {
    set_time_limit(0); // Ini biasanya tidak diperlukan, hanya jika prosesnya memakan waktu lama.
    global $conn;

    // Pastikan kolom yang di-SELECT dan di-JOIN sudah diindeks.
    $queryLocate = "SELECT 
        dd.serial_number, 
        hpd.signalStatus, 
        ldp.latitude, 
        ldp.longitude FROM lokasi_device_depok ldp 
        INNER JOIN device_depok dd ON dd.serial_number = ldp.device_name 
        INNER JOIN hasil_parsed_depok hpd ON dd.id = hpd.id_device_depok";

    $resultLocate = mysqli_query($conn, $queryLocate);
    
    if ($resultLocate) {
        $data = [];
        while ($row = mysqli_fetch_assoc($resultLocate)) {
            $data[] = [
                "serial_number" => $row['serial_number'],
                "signalStatus" => $row['signalStatus'],
                "latitude" => $row['latitude'],
                "longitude" => $row['longitude']
            ];
        }
    
        header("HTTP/1.0 200 OK");
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        $response = [
            "status" => 500,
            "message" => "Internal server error"
        ];
        header("HTTP/1.0 500 Server Error");
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}
?>
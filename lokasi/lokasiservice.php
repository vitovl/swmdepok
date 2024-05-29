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
        ldp.longitude,
        ldp.alamat 
    FROM lokasi_device_depok ldp 
    INNER JOIN device_depok dd ON dd.serial_number = ldp.device_name 
    INNER JOIN (
        SELECT id_device_depok, signalStatus
        FROM hasil_parsed_depok
        WHERE (id_device_depok, timestamp) IN (
            SELECT id_device_depok, MAX(timestamp)
            FROM hasil_parsed_depok
            GROUP BY id_device_depok
        )
    ) hpd ON dd.id = hpd.id_device_depok
    GROUP BY dd.serial_number";

    $resultLocate = mysqli_query($conn, $queryLocate);
    
    if ($resultLocate) {
        $res = [];
        while ($row = mysqli_fetch_assoc($resultLocate)) {
            $res[] = [
                "serial_number" => $row['serial_number'],
                "signalStatus" => $row['signalStatus'],
                "latitude" => $row['latitude'],
                "longitude" => $row['longitude'],
                "alamat" => $row['alamat']
            ];
        }
        header("HTTP/1.0 200 OK");
        $data = [
            "status" => 200,
            "message" => "Get all data location is success",
            "data" => $res,
        ];
        return json_encode($data);
    } else {
        $response = [
            "status" => 500,
            "message" => "Internal server error"
        ];
        header("HTTP/1.0 500 Server Error");
        header('Content-Type: application/json');
        return json_encode($response);
    }
}
// getLokasiDevice();
?>

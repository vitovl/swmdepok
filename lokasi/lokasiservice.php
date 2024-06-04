<?php
include '../koneksi.php';

function getLokasiDevice() {
    set_time_limit(0); // Ini biasanya tidak diperlukan, hanya jika prosesnya memakan waktu lama.
    global $conn;

    $queryLocate = "SELECT 
        dd.serial_number, 
        hpd.signalStatus, 
        ldp.latitude, 
        ldp.longitude,
        ldp.alamat,
        hpd.timestamp 
        FROM lokasi_device_depok ldp 
        INNER JOIN device_depok dd ON dd.serial_number = ldp.device_name 
        INNER JOIN (
        SELECT id_device_depok, signalStatus, timestamp
        FROM hasil_parsed_depok
        WHERE (id_device_depok, timestamp) IN (
            SELECT id_device_depok, MAX(timestamp)
            FROM hasil_parsed_depok
            GROUP BY id_device_depok
        )
    ) hpd ON dd.id = hpd.id_device_depok
    GROUP BY dd.serial_number";

    $resultLocate = mysqli_query($conn, $queryLocate);

    function getStatusConnection($timestamp) {
        $currentDate = new DateTime();
        $dataDate = new DateTime($timestamp);

        $interval = $currentDate->diff($dataDate)->days;

        if ($dataDate->format('Y-m-d') === $currentDate->format('Y-m-d')) {
            return "Connect";
        } elseif ($interval <= 2) {
            return "Connect";
        } else {
            return "Disconnect";
        }
    }

    if ($resultLocate) {
        $res = [];
        while ($row = mysqli_fetch_assoc($resultLocate)) {
            $statusConnection = getStatusConnection($row['timestamp']);
            $res[] = [
                "serial_number" => $row['serial_number'],
                "signalStatus" => $row['signalStatus'],
                "latitude" => $row['latitude'],
                "longitude" => $row['longitude'],   
                "alamat" => $row['alamat'],
                "statusConnection" => $statusConnection
            ];
        }
        header("HTTP/1.0 200 OK");
        header('Content-Type: application/json');
        return json_encode([
            "status" => 200,
            "message" => "Get all data location is success",
            "data" => $res
        ]);
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        header('Content-Type: application/json');
        return json_encode([
            "status" => 500,
            "message" => "Internal server error"
        ]);
    }
}
?>

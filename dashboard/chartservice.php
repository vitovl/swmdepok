<?php

// header('Access-Control-Allow-Headers: Accept, Content-Type');
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Method: GET, POST, PUT, DELETE');
// header('Content-Type: application/json');

include "../koneksi.php";

function getAllDataGraphics($limit, $skip) {
    global $conn;

    $sql = "SELECT 
        dp.serial_number, 
        hp.signalStatus, 
        hp.flowMeter, 
        hp.batteryStatus, 
        hp.timestamp,
        IFNULL(
            ROUND((hp.flowMeter - LAG(hp.flowMeter) OVER (PARTITION BY dp.serial_number ORDER BY hp.timestamp)) * 24 / 
            TIMESTAMPDIFF(HOUR, LAG(hp.timestamp) OVER (PARTITION BY dp.serial_number ORDER BY hp.timestamp), hp.timestamp), 2),
            0
        ) AS rateDataFlow
    FROM 
        hasil_parsed_depok hp 
    INNER JOIN 
        device_depok dp ON hp.id_device_depok = dp.id 
    ORDER BY 
        hp.timestamp DESC
    LIMIT $limit OFFSET $skip";

    $queryGetAllData = mysqli_query($conn, $sql);

    if ($queryGetAllData) {
        $res = [];
        $latestTimestamps = []; // Menyimpan timestamp terbaru untuk setiap serial number
        while ($row = mysqli_fetch_assoc($queryGetAllData)) {
            $serial_number = $row['serial_number'];
            // Jika serial number belum ada di latestTimestamps atau timestamp lebih baru dari yang ada
            if (!isset($latestTimestamps[$serial_number]) || $row['timestamp'] > $latestTimestamps[$serial_number]) {
                // Perbarui data untuk serial number ini
                $latestTimestamps[$serial_number] = $row['timestamp'];
                $responseData = [
                    "serial_number" => $row['serial_number'],
                    "signalStatus" => $row['signalStatus'],
                    "rateDataFlow" => $row['rateDataFlow'],
                    "batteryStatus" => $row['batteryStatus'],
                    "timestamp" => $row['timestamp']
                ];
                $res[] = $responseData;
            }
        }

        $data = [
            "status" => 200,
            "message" => "Get all data is success",
            "data" => $res,
        ];
        header("HTTP/1.0 200 OK");
        return json_encode($data);
    } else {
        $data = [
            "status" => 500,
            "message" => "Internal server error",
        ];
        header("HTTP/1.0 500 Server Error");
        return json_encode($data);
    }
}

// Get limit and skip from request, default to limit 20 and skip 0 if not provided
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$skip = isset($_GET['skip']) ? (int)$_GET['skip'] : 0;

// $allChartData = getAllDataGraphics($limit, $skip);
// echo $allChartData;
?>

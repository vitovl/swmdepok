<?php
include "../../koneksi.php";

function getAllDataGraphics() {
    global $conn;

    $sql = "SELECT 
                dp.serial_number, 
                hp.signalStatus, 
                hp.flowMeter, 
                hp.batteryStatus, 
                hp.timestamp, 
                IFNULL(
                    (hp.flowMeter - LAG(hp.flowMeter) OVER (PARTITION BY dp.serial_number, DATE(hp.timestamp) ORDER BY hp.timestamp)) / TIMESTAMPDIFF(HOUR, LAG(hp.timestamp) OVER (PARTITION BY dp.serial_number, DATE(hp.timestamp) ORDER BY hp.timestamp), hp.timestamp)* 24,
                    0
                ) AS rateDataFlow
            FROM 
                hasil_parsed_depok hp 
            INNER JOIN 
                device_depok dp ON hp.id_device_depok = dp.id 
            ORDER BY 
                hp.timestamp DESC";

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
                    //"flowMeter" => $row['flowMeter'],
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

//getAllDataGraphics();
?>

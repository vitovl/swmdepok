<?php


include "../koneksi.php";

function getAllDataGraphics($limit = 0, $skip = 0) {
    global $conn;

    // Jika limit = 0, tidak ada limit pada query, jika tidak tambahkan limit dan offset pada query
    $limitQuery = $limit > 0 ? " LIMIT " . intval($limit) . " OFFSET " . intval($skip) : "";

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
        $limitQuery
    ";

    $queryGetAllData = mysqli_query($conn, $sql);

    if ($queryGetAllData) {
        $res = [];
        $latestTimestamps = []; // Menyimpan timestamp terbaru untuk setiap serial number
        $today = date("Y-m-d"); // Mendapatkan tanggal hari ini

        while ($row = mysqli_fetch_assoc($queryGetAllData)) {
            $serial_number = $row['serial_number'];
            $timestamp = $row['timestamp'];
            $date = date("Y-m-d", strtotime($timestamp));

            // Jika serial number belum ada di latestTimestamps atau timestamp lebih baru dari yang ada
            if (!isset($latestTimestamps[$serial_number]) || $timestamp > $latestTimestamps[$serial_number]) {
                $latestTimestamps[$serial_number] = $timestamp;

                // Menentukan status koneksi
                $dateDifference = (strtotime($today) - strtotime($date)) / (60 * 60 * 24);
                $statusConnection = $date === $today ? "Connect" : ($dateDifference <= 5 ? "Connect" : "Disconnect");

                $responseData = [
                    "serial_number" => $serial_number,
                    "signalStatus" => $row['signalStatus'],
                    "rateDataFlow" => $row['rateDataFlow'],
                    "batteryStatus" => $row['batteryStatus'],
                    "timestamp" => $timestamp,
                    "statusConnection" => $statusConnection
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

// Menangani parameter limit dan skip dari URL
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
$skip = isset($_GET['skip']) ? intval($_GET['skip']) : 0;

// Panggil fungsi dengan parameter limit dan skip
// echo getAllDataGraphics($limit, $skip);
?>

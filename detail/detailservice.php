<?php

include "../koneksi.php";

function getDetailDevice() {
    global $conn;

    // Periksa apakah ada parameter serial number yang diberikan dalam URL
    if(isset($_GET['serial_number'])) {
        $serial_number = $_GET['serial_number'];

        $sql = "SELECT 
                    hp.RSSI,
                    hp.SNR, 
                    hp.flowMeter,
                    hp.batteryValue, 
                    hp.timestamp
                FROM 
                    hasil_parsed_depok hp 
                INNER JOIN 
                    device_depok dp ON hp.id_device_depok = dp.id 
                WHERE
                    dp.serial_number = '$serial_number'
                ORDER BY 
                    hp.timestamp DESC
                LIMIT 30"; // Ambil 30 data terbaru untuk serial number tertentu

        $queryGetDetailDevice = mysqli_query($conn, $sql);
        
        if ($queryGetDetailDevice) {
            $data = [];
            while ($row = mysqli_fetch_assoc($queryGetDetailDevice)) {
                $data[] = $row;
            }

            // Format respons sesuai dengan serial number
            $response = [
                $serial_number => $data
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($response);
        } else {
            $response = [
                "status" => 500,
                "message" => "Internal server error"
            ];
            header("HTTP/1.0 500 Server Error");
            return json_encode($response);
        }
    } else {
        // Jika parameter serial number tidak diberikan, maka tampilkan semua data dengan batasan 30 data terbaru untuk setiap serial number
        $sql = "SELECT 
                    dp.serial_number, 
                    hp.RSSI,
                    hp.SNR, 
                    hp.flowMeter,
                    hp.batteryValue, 
                    hp.timestamp
                FROM 
                    hasil_parsed_depok hp 
                INNER JOIN 
                    device_depok dp ON hp.id_device_depok = dp.id 
                INNER JOIN (
                    SELECT 
                        id_device_depok, 
                        MAX(timestamp) AS max_timestamp
                    FROM 
                        hasil_parsed_depok
                    GROUP BY 
                        id_device_depok
                ) max_hp ON hp.id_device_depok = max_hp.id_device_depok AND hp.timestamp = max_hp.max_timestamp
                ORDER BY 
                    dp.serial_number ASC";

        $queryGetDetailDevice = mysqli_query($conn, $sql);
        
        if ($queryGetDetailDevice) {
            $groupedData = []; // Array untuk menyimpan data yang sudah dikelompokkan
            while ($row = mysqli_fetch_assoc($queryGetDetailDevice)) {
                $serial_number = $row['serial_number'];
                unset($row['serial_number']); // Hapus serial number dari data

                // Jika serial number belum ada dalam groupedData, buat array baru untuknya
                if (!isset($groupedData[$serial_number])) {
                    $groupedData[$serial_number] = [];
                }
                // Tambahkan data ke dalam array untuk serial number tersebut
                $groupedData[$serial_number][] = $row;
            }

            // Ambil hanya 30 data terbaru untuk setiap serial number
            $limitedData = [];
            foreach ($groupedData as $serial_number => $data) {
                $limitedData[$serial_number] = array_slice($data, 0, 30); // Ambil 30 data terbaru
            }

            $response = [
                "status" => 200,
                "message" => "Get all data is success",
                "data" => $limitedData
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($response);
        } else {
            $response = [
                "status" => 500,
                "message" => "Internal server error"
            ];
            header("HTTP/1.0 500 Server Error");
            return json_encode($response);
        }
    }
}

//getDetailDevice();
?>

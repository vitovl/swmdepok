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
                LIMIT 30"; // Hanya ambil 3 data terbaru

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
        // Jika parameter serial number tidak diberikan, maka tampilkan semua data dengan batasan 3 data terbaru untuk setiap serial number
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
                ORDER BY 
                    dp.serial_number ASC, 
                    hp.timestamp DESC";

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

            // Ambil hanya 3 data terbaru untuk setiap serial number
            $limitedData = [];
            foreach ($groupedData as $serial_number => $data) {
                $limitedData[$serial_number] = array_slice($data, 0, 3); // Ambil 3 data terbaru
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

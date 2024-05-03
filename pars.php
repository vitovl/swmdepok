<?php

include 'koneksi.php';

function getSerialNumbersFromNewTable() {
    global $conn;
    $serialNumbers = [];
    // Query untuk mendapatkan serial number dari tabel baru di database
    $query = "SELECT serial_number FROM device_depok";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "Error: " . mysqli_error($conn);
        return [];
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $serialNumbers[] = $row['serial_number'];
    }
    return $serialNumbers;
}

function saveDataAntaresByDeviceId() {
    global $conn;
    $serialNumbers = getSerialNumbersFromNewTable();
    
    foreach ($serialNumbers as $serialNumber) {
        $deviceUrl = "https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/$serialNumber/la";
        $headers = [
            'X-M2M-Origin: 22d7ebb917b00bc8:b65db7ab728a0929',
            'Content-Type: application/json;ty=4',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $deviceUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
    
        if ($error) {
            echo "Error fetching device data from Antares: $error";
            continue; // Skip to the next device if there's an error
        }
    
        $deviceDataParsed = json_decode($response, true);
    
        if (isset($deviceDataParsed['m2m:cin']) && isset($deviceDataParsed['m2m:cin']['con'])) {
            
            $conParsed = json_decode($deviceDataParsed['m2m:cin']['con'], true);
            $payloadValue = $conParsed['data'];
            $devEuiValue = $conParsed['devEui'];
            $radio = $conParsed['radio']['hardware'];
            $RSSI = $radio['rssi'];
            $SNR = $radio['snr'];
            $timestamp = convertAntaresTimeToTimestamp($deviceDataParsed['m2m:cin']['ct']);
        

            $checkQuery = "SELECT COUNT(*) AS count FROM paylaod_device_depok WHERE serial_number = '$serialNumber'";
            $checkResult = mysqli_query($conn, $checkQuery);
            $checkRow = mysqli_fetch_assoc($checkResult);
            $dataExists = $checkRow['count'] > 0;

            if ($dataExists) {
                // Jika data sudah ada, update data di database
                $updateQuery = "UPDATE paylaod_device_depok SET payload = '$payloadValue', devEUI = '$devEuiValue', rssi = '$RSSI', snr = '$SNR', timestamp = '$timestamp' WHERE serial_number = '$serialNumber'";
                $updateSql = mysqli_query($conn, $updateQuery);

                if ($updateSql) {
                    echo "Data successfully updated in the database for device $serialNumber.\n";
                } else {
                    echo "Error updating data in the database for device $serialNumber: " . mysqli_error($conn) . "\n";
                }
            } else {
                // Jika data belum ada, insert data baru ke dalam database
                $insertQuery = "INSERT INTO paylaod_device_depok (serial_number, payload, devEUI, rssi, snr, timestamp) VALUES ('$serialNumber', '$payloadValue', '$devEuiValue', '$RSSI', '$SNR', '$timestamp')";
                $insertSql = mysqli_query($conn, $insertQuery);

                if ($insertSql) {
                    echo "New data successfully saved to database for device $serialNumber.\n";
                } else {
                    echo "Error saving new data to database for device $serialNumber: " . mysqli_error($conn) . "\n";
                }
            }
        }
    }
}

function convertAntaresTimeToTimestamp($antaresTime) {
    // Format waktu Antares: YYYYMMDDTHHMMSS
    $year = substr($antaresTime, 0, 4);
    $month = substr($antaresTime, 4, 2);
    $day = substr($antaresTime, 6, 2);
    $hour = substr($antaresTime, 9, 2);
    $minute = substr($antaresTime, 11, 2);
    $second = substr($antaresTime, 13, 2);

    // Mengonversi ke timestamp
    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

    return date('Y-m-d H:i:s', $timestamp); // Mengembalikan timestamp dalam format yang sesuai
}   

function getSignalStatus($RSSI, $SNR) {
    if ($RSSI > -115 && $SNR > -2) {
        return "bagus";
    } elseif ($RSSI > -118 && $RSSI <= -115 && $SNR > -5 && $SNR <= -2) {
        return "sedang";
    } elseif ($RSSI > -120 && $RSSI <= -118 && $SNR <= -5) {
        return "buruk";
    } else {
        return "Tidak dapat menentukan";
    }
}

function saveDataAntaresByPayload() {
    global $conn;

    // Mengambil data payload, serial number, timestamp, RSSI, dan SNR dari tabel payload_device_depok
    $query = "SELECT serial_number, payload, timestamp, rssi, snr FROM paylaod_device_depok";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "Error: " . mysqli_error($conn);
        return;
    }

    // Looping melalui hasil query
    while ($row = mysqli_fetch_assoc($result)) {
        $serialNumber = $row['serial_number'];
        $payloadValue = $row['payload'];
        $timestamp = $row['timestamp'];
        $RSSI = $row['rssi'];
        $SNR = $row['snr'];

        // Memeriksa apakah data untuk perangkat dengan serial number tersebut sudah ada dalam database
        $check_query = "SELECT COUNT(*) AS total FROM hasildata_depok WHERE serial_number = '$serialNumber'";
        $check_result = mysqli_query($conn, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);

        // Jika data sudah ada, panggil fungsi updateDataAntares
        if ($check_row['total'] > 0) {
            updateDataAntares($serialNumber, $payloadValue, $timestamp, $RSSI, $SNR);
            continue;
        }

        // Memisahkan data dari payload
        $data2 = substr($payloadValue, 16, 8);
        $data3 = strtoupper(substr($payloadValue, 54, 2));

        $hex_data2 = implode(' ', array_map(function ($item) {
            return sprintf("%02X", hexdec($item));
        }, str_split($data2, 2)));

        $data2_reversed = implode(' ', array_map(function ($item) {
            return sprintf("%02X", hexdec($item));
        }, array_reverse(str_split($data2, 2))));

        $decimal_data2 = hexdec(str_replace(' ', '', $data2_reversed)) / 1000;
        $decimal_data3 = hexdec($data3) / 10;

        // Konversi nilai tegangan ke rentang 0% hingga 100%
        if ($decimal_data3 >= 3.6) {
            $percentage = 100;
        } elseif ($decimal_data3 <= 2.8) {
            $percentage = 0;
        } else {
            $percentage = (($decimal_data3 - 2.8) / (3.6 - 2.8)) * 100; // Menghitung persentase
        }

        // Menentukan status baterai
        if ($percentage == 0) {
            $status = "Drop";
        } elseif ($percentage == 100) {
            $status = "Stabil";
        }

        // Mendapatkan status sinyal dari fungsi getSignalStatus
        $signalStatus = getSignalStatus($RSSI, $SNR);

        // Simpan data ke tabel hasildata_depok
        $insertSql = "INSERT INTO hasildata_depok (serial_number, payload, signal_status, rateDataFlow, batteryStatus, lastUpdate) VALUES ('$serialNumber', '$payloadValue', '$signalStatus', '$decimal_data2', '$status', '$timestamp')";

        $sqlInsert = mysqli_query($conn, $insertSql);
        if ($sqlInsert) {
            echo "Data berhasil disimpan ke database hasildata_depok\n";
        } else {
            echo "Error saving new data to database for device $serialNumber: " . mysqli_error($conn) . "\n";
        }
    }
}

function updateDataAntares($serialNumber, $payloadValue, $timestamp, $RSSI, $SNR) {
    global $conn;

    // Memisahkan data dari payload
    $data2 = substr($payloadValue, 16, 8);
    $data3 = strtoupper(substr($payloadValue, 54, 2));

    $hex_data2 = implode(' ', array_map(function ($item) {
        return sprintf("%02X", hexdec($item));
    }, str_split($data2, 2)));

    $data2_reversed = implode(' ', array_map(function ($item) {
        return sprintf("%02X", hexdec($item));
    }, array_reverse(str_split($data2, 2))));

    $decimal_data2 = hexdec(str_replace(' ', '', $data2_reversed)) / 1000;
    $decimal_data3 = hexdec($data3) / 10;

    // Konversi nilai tegangan ke rentang 0% hingga 100%
    if ($decimal_data3 >= 3.6) {
        $percentage = 100;
    } elseif ($decimal_data3 <= 2.8) {
        $percentage = 0;
    } else {
        $percentage = (($decimal_data3 - 2.8) / (3.6 - 2.8)) * 100; // Menghitung persentase
    }

    // Menentukan status baterai
    if ($percentage == 0) {
        $status = "Drop";
    } elseif ($percentage == 100) {
        $status = "Stabil";
    }

    // Mendapatkan status sinyal dari fungsi getSignalStatus
    $signalStatus = getSignalStatus($RSSI, $SNR);

    // Query untuk memperbarui data di tabel hasildata_depok
    $updateQuery = "UPDATE hasildata_depok SET payload = '$payloadValue', signal_status = '$signalStatus', rateDataFlow = '$decimal_data2', batteryStatus = '$status', lastUpdate = '$timestamp' WHERE serial_number = '$serialNumber'";
    $updateResult = mysqli_query($conn, $updateQuery);

    if ($updateResult) {
        echo "Data successfully updated in the database for device $serialNumber.\n";
    } else {
        echo "Error updating data in the database for device $serialNumber: " . mysqli_error($conn) . "\n";
    }
}

// Panggil fungsi untuk menyimpan data ke database
saveDataAntaresByDeviceId();

// Panggil fungsi untuk menyimpan data baru
saveDataAntaresByPayload();

?>
    
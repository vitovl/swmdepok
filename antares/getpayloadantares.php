<?php
include '../koneksi.php';

function getSerialNumbersFromNewTable() {
    global $conn;
    $serialNumbers = [];
    // Query untuk mendapatkan semua serial number dari tabel baru di database
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

function isDeviceExist($deviceId) {
    global $conn;

    $query = "SELECT COUNT(*) AS count FROM payload_device_depok WHERE id_device_depok='$deviceId'";
    $checkResult = mysqli_query($conn, $query);
    $checkRow = mysqli_fetch_assoc($checkResult);

    return $checkRow['count'] > 0;
}

function saveDataAntaresByDeviceId() {
    global $conn;
    $mh = curl_multi_init();
    $curlHandles = [];
    $serialNumbers = getSerialNumbersFromNewTable();
    $headers = [
        'X-M2M-Origin: 22d7ebb917b00bc8:b65db7ab728a0929',
        'Content-Type: application/json;ty=4',
        'Accept: application/json'
    ];

    $chunkSize = 100; // Menentukan ukuran batch
    $chunks = array_chunk($serialNumbers, $chunkSize); // Membagi data menjadi batch

    foreach ($chunks as $chunk) {
        foreach ($chunk as $serialNumber) {
            $ch = curl_init();
            $deviceUrl = "https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/$serialNumber/la";

            curl_setopt($ch, CURLOPT_URL, $deviceUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch);
            $curlHandles[$serialNumber] = $ch; // Simpan handle cURL dengan kunci nomor seri perangkat
        }

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($chunk as $serialNumber) {
            $ch = $curlHandles[$serialNumber]; // Dapatkan handle cURL dari array berdasarkan nomor seri perangkat
            $response = curl_multi_getcontent($ch);
            $error = curl_error($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if ($error) {
                echo "Error fetching device data from Antares for device $serialNumber: $error\n";
                continue; // Skip to the next device if there's an error
            }

            $deviceDataParsed = json_decode($response, true);

            if ($response && isset($deviceDataParsed['m2m:cin']) && isset($deviceDataParsed['m2m:cin']['con'])) {
                $conParsed = json_decode($deviceDataParsed['m2m:cin']['con'], true);
                $payloadValue = $conParsed['data'];

                // Cek apakah payload dimulai dengan '6f' dan tidak berisi 'NO PAYLOAD'
                if (strpos($payloadValue, '6f') !== 0 || strpos($payloadValue, 'NO PAYLOAD') !== false) {
                    echo "Invalid payload for device $serialNumber: $payloadValue\n";
                    continue; // Skip to the next device if payload is invalid
                }

                $devEuiValue = $conParsed['devEui'];
                $radio = $conParsed['radio']['hardware'];
                $RSSI = $radio['rssi'];
                $SNR = $radio['snr'];
                $timestamp = convertAntaresTimeToTimestamp($deviceDataParsed['m2m:cin']['ct']);

                $getId = "SELECT id FROM device_depok WHERE serial_number='$serialNumber'";
                $res = mysqli_query($conn, $getId);
                $deviceId = mysqli_fetch_assoc($res)['id']; // get id device depok

                // Memeriksa apakah data sudah ada di database dan timestamp lebih baru
                $checkQuery = "SELECT COUNT(*) AS count, MAX(timestamp) AS max_timestamp FROM payload_device_depok WHERE id_device_depok = '$deviceId'";
                $checkResult = mysqli_query($conn, $checkQuery);
                $checkRow = mysqli_fetch_assoc($checkResult);
                $dataExists = isDeviceExist($deviceId);
                $existingTimestamp = strtotime($checkRow['max_timestamp']);
                $newTimestamp = strtotime($timestamp);

                // Jika data tidak ada di database atau timestamp lebih baru, maka data akan dimasukkan ke database
                if (!$dataExists || $newTimestamp > $existingTimestamp) {
                    $insertQuery = "INSERT INTO payload_device_depok (id_device_depok, payload, devEUI, rssi, snr, timestamp) VALUES ('$deviceId', '$payloadValue', '$devEuiValue', '$RSSI', '$SNR', '$timestamp')";
                    $insertSql = mysqli_query($conn, $insertQuery);
                    if ($insertSql) {
                        echo "New data payload successfully saved to database for device $serialNumber.\n";
                    } else {
                        echo "Error saving new data to database for device $serialNumber: " . mysqli_error($conn) . "\n";
                    }
                } else {
                    echo "Data payload for device $serialNumber is up to date.\n";
                }
            }
        }
    }
    echo "Total serial numbers processed: " . count($serialNumbers) . "\n";
    curl_multi_close($mh);
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

// Jalankan kode secara terus menerus dengan interval 10 menit
// while (true) {
//     // Panggil fungsi untuk menyimpan data ke database
saveDataAntaresByDeviceId();
    
//     // Tunggu selama 10 menit sebelum menjalankan kembali
//     sleep(60); // 600 detik = 10 menit
// }
?>

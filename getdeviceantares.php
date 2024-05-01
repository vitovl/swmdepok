<?php
include 'koneksi.php';

function getSerialNumbersFromNewTable() {
    global $conn;
    $serialNumbers = [];

    // Query untuk mendapatkan semua serial number dari tabel device_depok
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
    $headers = [
        'X-M2M-Origin: 22d7ebb917b00bc8:b65db7ab728a0929',
        'Content-Type: application/json;ty=4',
        'Accept: application/json'
    ];

    // Initiate curl multi handle
    $mh = curl_multi_init();
    $ch = curl_init();
    // Array to hold individual curl handles
    $curlHandles = [];

    foreach ($serialNumbers as $serialNumber) {
        

        $deviceUrl = "https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/$serialNumber/la";

        curl_setopt($ch, CURLOPT_URL, $deviceUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Add handle to multi handle
        curl_multi_add_handle($mh, $ch);

        // Store the handle for later use
        $curlHandles[] = $ch;
    }

    // Execute all queries simultaneously
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);

    // Process each handle
    foreach ($curlHandles as $ch) {
        $response = curl_multi_getcontent($ch);
        $error = curl_error($ch);
        curl_multi_remove_handle($mh, $ch);

        if ($error) {
            echo "Error fetching device data from Antares: $error";
            continue; // Skip to the next device if there's an error
        }

        $deviceDataParsed = json_decode($response, true);

        if (isset($deviceDataParsed['m2m:cin']) && isset($deviceDataParsed['m2m:cin']['con'])) {
            // Process and save data to database
            processDataAndSaveToDatabase($conn, $serialNumber, $deviceDataParsed);
        }
    }

    // Close multi handle
    curl_multi_close($mh);
}

function processDataAndSaveToDatabase($conn, $serialNumber, $deviceDataParsed) {
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

// Panggil fungsi untuk menyimpan data ke database
saveDataAntaresByDeviceId();
?>

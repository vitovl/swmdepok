<?php 
include 'koneksi.php';

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

    $query = "SELECT COUNT(*) AS count FROM detail_depok WHERE id_device_depok='$deviceId'";
    $checkResult = mysqli_query($conn, $query);
    $checkRow = mysqli_fetch_assoc($checkResult);

    return $checkRow['count'] > 0;
}

function isPayloadExist($payloadVal) {
  global $conn;

  $query = "SELECT  COUNT(*) AS count FROM detail_depok WHERE payload='$payloadVal'";
  $checkResult = mysqli_query($conn, $query);
  $checkRow = mysqli_fetch_assoc($checkResult);
  // print_r($checkRow);

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

    $ch = curl_init();
    $chunkSize = 200; // Menentukan ukuran batch
    $chunks = array_chunk($serialNumbers, $chunkSize); // Membagi data menjadi batch
    // print_r($chunks);
    foreach ($chunks as $chunk) {
        foreach ($chunk as $serialNumber) {
            $deviceUrl = "https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/$serialNumber/la?fu=1&drt=2&ty=4&lim=30";

            curl_setopt_array($ch, [
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_URL => $deviceUrl,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_HTTPHEADER => $headers,
            ]);
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
            // print_r($deviceDataParsed);
            if(isset($deviceDataParsed["m2m:list"])) {
              foreach($deviceDataParsed['m2m:list'] as $device) {
                // print_r($device);
                if($response && isset($device['m2m:cin']) && isset($device['m2m:cin']['con'])){
                  $conParsed = json_decode($device['m2m:cin']['con'], true);
                  // print_r($conParsed);

                  $payloadValue = $conParsed['data'];
                  $devEuiValue = $conParsed['devEui'];
                  $radio = $conParsed['radio']['hardware'];
                  $RSSI = $radio['rssi'];
                  $SNR = $radio['snr'];
                  $timestamp = convertAntaresTimeToTimestamp($device['m2m:cin']['ct']);
                  
                  $getId = "SELECT id FROM device_depok WHERE serial_number='$serialNumber'";
                  $res = mysqli_query($conn, $getId);
                  $deviceId = mysqli_fetch_assoc($res)['id']; // get id device depok

                  $dataExists = isDeviceExist($deviceId);
                  $payloadExist = isPayloadExist($payloadValue);
                  print($payloadExist);
                  if ($dataExists && $payloadExist) {
                    // Jika data sudah ada, update data di database
                    $updateQuery = "UPDATE detail_depok SET payload = '$payloadValue', devEUI = '$devEuiValue', rssi = '$RSSI', snr = '$SNR', timestamp = '$timestamp' WHERE id_device_depok = '$deviceId'";
                    $updateSql = mysqli_query($conn, $updateQuery);

                    if ($updateSql) {
                        echo "Data successfully updated in the database for device $serialNumber.\n";
                    } else {
                        echo "Error updating data in the database for device $serialNumber: " . mysqli_error($conn) . "\n";
                    }
                  } else {
                    // Jika data belum ada, insert data baru ke dalam database
                    $insertQuery = "INSERT INTO detail_depok (id_device_depok, payload, devEUI, rssi, snr, timestamp) VALUES ('$deviceId', '$payloadValue', '$devEuiValue', '$RSSI', '$SNR', '$timestamp')";
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
        }
    }

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

// Panggil fungsi untuk menyimpan data ke database secara asynchronous
saveDataAntaresByDeviceId();
?> 
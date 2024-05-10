

// $i = 1;

// function delay($ms) {
//     usleep($ms * 1000);
// }

// function fetchDeviceData($waterMeterID) {
//     global $i;
//     global $conn;
//     $deviceUrl = "https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/$waterMeterID/la";
//     $headers = [
//         'X-M2M-Origin: 22d7ebb917b00bc8:b65db7ab728a0929',
//         'Content-Type: application/json',
//         'Accept: application/json'
//     ];

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $deviceUrl);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     $response = curl_exec($ch);
//     $error = curl_error($ch);
//     curl_close($ch);

//     if ($error) {
//         echo "Error fetching device data from Antares: $error";
//         return;
//     }

//     $deviceDataParsed = json_decode($response, true);
//     if (isset($deviceDataParsed['m2m:cin']) && isset($deviceDataParsed['m2m:cin']['con'])) {
//         $conParsed = json_decode($deviceDataParsed['m2m:cin']['con'], true);
//         if (isset($conParsed['type'])) {
//             $typeValue = $conParsed['type'];
        
//             echo "==========device SN========= : $waterMeterID\n";
//             echo "Counter: $i\n";
//             $i = $i + 1;
//             echo "Type: $typeValue\n";

//             if ($typeValue == "downlink") {
//                 // Lakukan sesuatu jika type adalah downlink
//             } else if ($typeValue == "uplink") {
//                 $dataValue = $conParsed['data'];
//                 $devEuiValue = $conParsed['devEui'];
//                 $radio = $conParsed['radio']['hardware'];
//                 $RSSI = $radio['rssi'];
//                 $SNR = $radio['snr'];

//                 echo "Data: $dataValue\n";
//                 echo "DevEui: $devEuiValue\n";
//                 echo "RSSI : $RSSI\n";
//                 echo "SNR : $SNR\n";

//                 // Simpan data ke database
//                 $query = "INSERT INTO watermeterdepok_db (watermeterID, payload, deveui, rssi, snr) VALUES ('$waterMeterID', '$dataValue', '$devEuiValue', '$RSSI', '$SNR')";
//                 $sql = mysqli_query($conn, $query);

//                 if ($sql) {
//                     echo "Data successfully saved to database.\n";
//                 } else {
//                     echo "Error saving data to database.\n";
//                 }
//             } else {
//                 echo "hai\n";
//             }
//         } else {
//             echo "Parameter type tidak ditemukan dalam data con.\n";
//         }
//     } else {
//         echo "Data con tidak ditemukan atau struktur data tidak sesuai ekspektasi.\n";
//     }
// }










    
// Check if the data already exists in the database
//             $checkQuery = "SELECT COUNT(*) AS count FROM paylaod_device_depok WHERE devEui = '$devEuiValue'";
//             $checkResult = mysqli_query($conn, $checkQuery);
//             $checkRow = mysqli_fetch_assoc($checkResult);
//             $dataExists = $checkRow['count'] > 0;
    
//             if (!$dataExists) {
//                 // If the data doesn't   exist, insert it into the database
// // Add the serial number retrieved from the device_depok table to the paylaod_device_depok database
//             $query = "INSERT INTO paylaod_device_depok (serial_number, payload, devEUI, rssi, snr) VALUES ('$serialNumber', '$payloadValue', '$devEuiValue', '$RSSI', '$SNR')";                $sql = mysqli_query($conn, $query);
    
//                 if ($sql) {
//                     echo "Data successfully saved to database for device $serialNumber.\n";
//                 } else {
//                     echo "Error saving data to database for device $serialNumber: " . mysqli_error($conn) . "\n";
//                 }
//             } else {
//                 // If the data already exists, skip insertion and display a message
//                 echo "Data for device $serialNumber already exists in the database, skipping.\n";
//             }
//          else {
//             echo "Data structure not as expected for device $serialNumber.\n";
//         }
//     
// 

<!-- <
$last_data = 0;
$data_baterai = array();

while($row = $result->fetch_assoc()) {
    $data_baterai[] = $row['data_baterai'];
    $last_data = $row['data_baterai'];
}

$diff_sum = 0;
for($i = max(0, count($data_baterai) - 30); $i < count($data_baterai) - 1; $i++) {
    $diff = $data_baterai[$i + 1] - $data_baterai[$i];
    $diff_sum += $diff;
}
//echo "Total Baterai: " . $diff_sum;

if($diff_sum >= -0.2) {
    echo "Stabil";
} elseif($diff_sum < -0.2) {
    echo "Drop";
}

?> -->
<?php

include 'koneksi.php';
include 'getdeviceantares.php';
include 'getpayloadantares.php';

$status = '';
$headerSerialNumber = array('620', '702', '602', '682', '692');

function getSignalStatus($RSSI, $SNR)
{
    if ($RSSI > -115 || $SNR > -2) {
        return "bagus";
    } elseif ($RSSI > -118 || $RSSI <= -115 || $SNR > -5 || $SNR <= -2) {
        return "sedang";
    } elseif ($RSSI > -120 || $RSSI <= -118 || $SNR <= -5) {
        return "buruk";
    } else {
        return "Tidak dapat menentukan";
    }
}

function saveDataAntaresByPayload()
{
    global $conn, $headerSerialNumber;

    $query = "SELECT serial_number, payload, timestamp, rssi, snr FROM payload_device_depok";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "Error: " . mysqli_error($conn);
        return;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        if (isset($row['id_device_depok'])) { // Check if 'id_device_depok' key exists
            $deviceId = $row['id_device_depok'];

        } if(isset($row['id'])){
            $idPayload = $row['id'];
        
        }    else {
            echo "Error: 'id_device_depok' key does not exist in the result.\n";
            continue; // Skip this iteration if key does not exist
        }
        $payloadValue = $row['payload'];
        $timestamp = $row['timestamp'];
        $RSSI = $row['rssi'];
        $SNR = $row['snr'];

        // Mendapatkan ID perangkat dari tabel device_depok
        $getIdQuery = "SELECT id FROM device_depok WHERE id ='$deviceId'";
        $getIdResult = mysqli_query($conn, $getIdQuery);
        if ($getIdResult && mysqli_num_rows($getIdResult) > 0) {
            $deviceRow = mysqli_fetch_assoc($getIdResult);
            $deviceId = $deviceRow['id']; // Mengambil ID perangkat depok
        } else {
            echo "Error: Device with serial number $deviceId does not exist.\n";
            continue;
        }

        // Mendapatkan ID payload dari tabel payload_device_depok
        $getIdPayloadQuery = "SELECT id FROM payload_device_depok WHERE id = '$idPayload'";
        $getIdPayloadResult = mysqli_query($conn, $getIdPayloadQuery);
        if ($getIdPayloadResult && mysqli_num_rows($getIdPayloadResult) > 0) {
            $payloadRow = mysqli_fetch_assoc($getIdPayloadResult);
            $idPayload = $payloadRow['id']; // Mengambil ID payload_device_depok
        } else {
            echo "Error: Payload with ID $idPayload does not exist.\n";
            continue;
        }

        // Cek keberadaan data pada tabel hasil_parsed_depok
        $checkQuery = "SELECT COUNT(*) AS total FROM hasil_parsed_depok WHERE id_device_depok = '$deviceId' AND timestamp = '$timestamp'";
        $checkResult = mysqli_query($conn, $checkQuery);
        $checkRow = mysqli_fetch_assoc($checkResult);
        $dataExists = $checkRow['total'] > 0;

    // // Cek apakah data dengan timestamp tersebut ada di tabel payload_device_depok
    // $check_payload_query = "SELECT COUNT(*) AS total FROM payload_device_depok WHERE id = '$idPayload'  id_device_depok = '$deviceId' AND timestamp = '$timestamp'";
    // $check_payload_result = mysqli_query($conn, $check_payload_query);
    // $check_payload_row = mysqli_fetch_assoc($check_payload_result);
    // $payload_exists = $check_payload_row['total'] > 0;

    // if ($payloadExists && $dataExists) {
        // Masukkan data hanya jika belum ada di tabel hasil_parsed_depok
        // $check_query = "SELECT COUNT(*) AS total FROM hasil_parsed_depok WHERE id_payload = $id , id_device_depok = '$deviceId' AND timestamp = '$timestamp'";
        // $check_result = mysqli_query($conn, $check_query);
        // $check_row = mysqli_fetch_assoc($check_result);
        // $dataExists = $check_row['total'] > 0;

        if (!$dataExists) {
            // Jika data tidak ada di database, maka data akan dimasukkan ke database
            if (in_array(substr($deviceId, 0, 3), $headerSerialNumber)) {
                // Variabel $statusBattery didefinisikan di sini
                $statusBattery = "";
                if (in_array(substr($deviceId, 0, 3), array('620', '702', '602'))) {
                    $forwardFlow = substr($payloadValue, 16, 8);
                    $battery = strtoupper(substr($payloadValue, 54, 2));

                    $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                        return sprintf("%02X", hexdec($item));
                    }, array_reverse(str_split($forwardFlow, 2))));

                    $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;

                  
                    $batteryValue = hexdec($battery) / 10;

                    // Hitung perubahan nilai baterai dari 3 timestamp terbaru
                    $batteryChangeQuery = "SELECT batteryValue FROM hasil_parsed_depok WHERE id_device_depok = '$deviceId' ORDER BY timestamp DESC LIMIT 3";
                    $batteryChangeResult = mysqli_query($conn, $batteryChangeQuery);
                    $batteryValues = [];
                    while ($batteryChangeRow = mysqli_fetch_assoc($batteryChangeResult)) {
                        $batteryValues[] = $batteryChangeRow['batteryValue'];
                    }
    
                    // Periksa perubahan nilai baterai
                    if (count($batteryValues) == 3) {
                        $batteryChange = abs(max($batteryValues) - min($batteryValues));
                        if ($batteryChange >= 0.2) {
                            $statusBattery = "Drop";
                        } else {
                            $statusBattery = "Stabil";
                        }
                    } else {
                        $statusBattery = ($batteryValue >= 3.4) ? "Stabil" : "Drop";
                    }
                } else if (in_array(substr($deviceId, 0, 3), array('682', '692'))) {
                    $forwardFlow = substr($payloadValue, 12, 8);
                    $battery = strtoupper(substr($payloadValue, 76, 2));
                    $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                        return sprintf("%02X", hexdec($item));
                    }, array_reverse(str_split($forwardFlow, 2))));

                    $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;
                    $batteryValue = hexdec($battery) / 10;

                    // Hitung perubahan nilai baterai dari 3 timestamp terbaru
                    $batteryChangeQuery = "SELECT batteryValue FROM hasil_parsed_depok WHERE id_device_depok = '$deviceId' ORDER BY timestamp DESC LIMIT 3";
                    $batteryChangeResult = mysqli_query($conn, $batteryChangeQuery);
                    $batteryValues = [];
                    while ($batteryChangeRow = mysqli_fetch_assoc($batteryChangeResult)) {
                        $batteryValues[] = $batteryChangeRow['batteryValue'];
                    }
    
                    // Periksa perubahan nilai baterai
                    if (count($batteryValues) == 3) {
                        $batteryChange = abs(max($batteryValues) - min($batteryValues));
                        if ($batteryChange >= 0.2) {
                            $statusBattery = "Drop";
                        } else {
                            $statusBattery = "Stabil";
                        }
                    } else {
                        $statusBattery = ($batteryValue >= 3.4) ? "Stabil" : "Drop";
                    }
                }

                // Masukkan data baru ke dalam tabel hasil_parsed_depok
                $signalStatus = getSignalStatus($RSSI, $SNR);
                $insertSql = "INSERT INTO hasil_parsed_depok (id_device_depok, id_payload, RSSI , SNR, signalStatus, flowMeter, batteryValue, batteryStatus, timestamp) VALUES ('$deviceId', '$idPayload', '$RSSI', '$SNR', '$signalStatus', '$forwardFlowValue', '$batteryValue', '$statusBattery', '$timestamp')";
                $insertQuery = mysqli_query($conn, $insertSql);

                if ($insertQuery) {
                    echo "New data PARSING successfully saved to database for device $deviceId.\n";
                } else {
                    echo "Error saving new data to database for device $deviceId: " . mysqli_error($conn) . "\n";
                }
            }
        } else {
            echo "Data for device $deviceId with timestamp $timestamp already exists in hasil_parsed_depok.\n";
        }
    }
}

while (true) {
    // Panggil fungsi untuk menyimpan data ke database
    //saveDataAntaresByDeviceId();
    saveDataAntaresByPayload(); // Uncomment this line to execute saveDataAntaresByPayload()
    sleep(60); // 600 detik = 10 menit
}

?>

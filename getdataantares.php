<?php

include 'koneksi.php';
// include 'getdeviceantares.php';
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
        $serialNumber = $row['serial_number'];
        $payloadValue = $row['payload'];
        $timestamp = $row['timestamp'];
        $RSSI = $row['rssi'];
        $SNR = $row['snr'];

        // Cek apakah data dengan timestamp tersebut ada di tabel payload_device_depok
        $check_payload_query = "SELECT COUNT(*) AS total FROM payload_device_depok WHERE serial_number = '$serialNumber' AND timestamp = '$timestamp'";
        $check_payload_result = mysqli_query($conn, $check_payload_query);
        $check_payload_row = mysqli_fetch_assoc($check_payload_result);
        $payload_exists = $check_payload_row['total'] > 0;

        if ($payload_exists) {
            // Masukkan data hanya jika belum ada di tabel hasil_parsed_depok
            $check_query = "SELECT COUNT(*) AS total FROM hasil_parsed_depok WHERE serial_number = '$serialNumber' AND timestamp = '$timestamp'";
            $check_result = mysqli_query($conn, $check_query);
            $check_row = mysqli_fetch_assoc($check_result);
            $dataExists = $check_row['total'] > 0;

            if (!$dataExists) {
                // Jika data tidak ada di database, maka data akan dimasukkan ke database
                if (in_array(substr($serialNumber, 0, 3), $headerSerialNumber)) {
                    // Variabel $statusBattery didefinisikan di sini
                    $statusBattery = "";
                    if (in_array(substr($serialNumber, 0, 3), array('620', '702', '602'))) {
                        $forwardFlow = substr($payloadValue, 16, 8);
                        $battery = strtoupper(substr($payloadValue, 54, 2));

                        $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                            return sprintf("%02X", hexdec($item));
                        }, array_reverse(str_split($forwardFlow, 2))));

                        $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;

                      
                        $batteryValue = hexdec($battery) / 10;

                        // Hitung perubahan nilai baterai dari 3 timestamp terbaru
                        $batteryChangeQuery = "SELECT batteryValue FROM hasil_parsed_depok WHERE serial_number = '$serialNumber' ORDER BY timestamp DESC LIMIT 3";
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
                    } else if (in_array(substr($serialNumber, 0, 3), array('682', '692'))) {
                        $forwardFlow = substr($payloadValue, 12, 8);
                        $battery = strtoupper(substr($payloadValue, 76, 2));
                        $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                            return sprintf("%02X", hexdec($item));
                        }, array_reverse(str_split($forwardFlow, 2))));

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
        $insertSql = "INSERT INTO hasildata_depok (serial_number, payload, signal_status, rateDataFlow, batteryStatus, lastUpdate) VALUES ('$serialNumber', '$payloadValue', '$signalStatus', '$decimal_data2', '$decimal_data3', '$timestamp')";

        $sqlInsert = mysqli_query($conn, $insertSql);
        if ($sqlInsert) {
            echo "Data berhasil disimpan ke databasee hasildata_depok\n";
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
    // if ($decimal_data3 >= 3.6) {
    //     $percentage = 100;
    // } elseif ($decimal_data3 <= 2.8) {
    //     $percentage = 0;
    // } else {
    //     $percentage = (($decimal_data3 - 2.8) / (3.6 - 2.8)) * 100; // Menghitung persentase
    // }

    // // Menentukan status baterai
    // if ($percentage == 0) {
    //     $status = "Drop";
    // } elseif ($percentage == 100) {
    //     $status = "Stabil";
    // }

    // Mendapatkan status sinyal dari fungsi getSignalStatus
    $signalStatus = getSignalStatus($RSSI, $SNR);

    // Query untuk memperbarui data di tabel hasildata_depok
    $updateQuery = "UPDATE hasildata_depok SET payload = '$payloadValue', signal_status = '$signalStatus', rateDataFlow = '$decimal_data2', batteryStatus = '$decimal_data3', lastUpdate = '$timestamp' WHERE serial_number = '$serialNumber'";
    $updateResult = mysqli_query($conn, $updateQuery);

    if ($updateResult) {
        echo "Data successfully updated in the databaseeeee for device $serialNumber.\n";
    } else {
        echo "Error updating data in the database for device $serialNumber: " . mysqli_error($conn) . "\n";
    }
}


// Panggil fungsi untuk menyimpan data ke tabel payload_device_depok
// getAllDevicesData();
// saveDataAntaresByDeviceId();
saveDataAntaresByPayload(); // Panggil fungsi untuk menyimpan data baru

?>

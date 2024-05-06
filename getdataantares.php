<?php

include 'koneksi.php';
// include 'getdeviceantares.php';
// include 'getpayloadantares.php';

$status= '';

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
        $batteryValue = hexdec($data3) / 10;

        // Konversi nilai tegangan ke rentang 0% hingga 100%
        if ($batteryValue >= 3.6) {
            $percentage = 100;
        } elseif ($batteryValue <= 2.8) {
            $percentage = 0;
        } else {
            $percentage = (($batteryValue - 2.8) / (3.6 - 2.8)) * 100; // Menghitung persentase
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
        $insertSql = "INSERT INTO hasildata_depok (serial_number, payload, signal_status, rateDataFlow, batteryStatus, lastUpdate) VALUES ('$serialNumber', '$payloadValue', '$signalStatus', '$decimal_data2', '$batteryValue', '$timestamp')";

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
saveDataAntaresByPayload(); // Panggil fungsi untuk menyimpan data baru

?>
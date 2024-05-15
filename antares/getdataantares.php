<?php

include '../koneksi.php';
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

function saveDataAntaresByPayload($chunk)
{
    global $conn, $headerSerialNumber;

    foreach ($chunk as $row) {
        $deviceId = $row['id_device_depok']; // Mengambil nomor seri perangkat sebagai ID
        $idPayload = $row['id'];
        $payloadValue = $row['payload'];
        $timestamp = $row['timestamp'];
        $RSSI = $row['rssi'];
        $SNR = $row['snr'];

        // Mendapatkan ID perangkat dan serial number dari tabel device_depok dengan menggunakan JOIN
        $getIdQuery = "SELECT dd.id, dd.serial_number FROM device_depok dd JOIN payload_device_depok pd ON dd.id = pd.id_device_depok WHERE pd.id_device_depok = '$deviceId' AND pd.payload = '$payloadValue'";
        $getIdResult = mysqli_query($conn, $getIdQuery);
        if ($getIdResult && mysqli_num_rows($getIdResult) > 0) {
            $deviceRow = mysqli_fetch_assoc($getIdResult);
            $deviceId = $deviceRow['id']; // Mengambil ID perangkat depok
            $serialNumber = $deviceRow['serial_number'];
        } else {
            echo "Error: Device with serial number $deviceId does not exist.\n";
            continue;
        }

        // Cek keberadaan data pada tabel hasil_parsed_depok
        $checkQuery = "SELECT COUNT(*) AS total FROM hasil_parsed_depok WHERE id_device_depok = '$deviceId' AND timestamp = '$timestamp'";
        $checkResult = mysqli_query($conn, $checkQuery);
        $checkRow = mysqli_fetch_assoc($checkResult);
        $dataExists = $checkRow['total'] > 0;
        if (!$dataExists) {
            // Jika data tidak ada di database, maka data akan dimasukkan ke database
            if (in_array(substr($serialNumber, 0, 3), $headerSerialNumber)) {
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
                    $batteryChangeQuery = "SELECT batteryValue FROM hasil_parsed_depok WHERE id_device_depok = '$deviceId' ORDER BY timestamp DESC LIMIT 3";
                    $batteryChangeResult = mysqli_query($conn, $batteryChangeQuery);
                    $batteryValues = [];
                    while ($batteryChangeRow = mysqli_fetch_assoc($batteryChangeResult)) {
                        $batteryValues[] = $batteryChangeRow['batteryValue'];
                    }

                    // Periksa perubahan nilai baterai
                    if (count($batteryValues) == 3) {
                        $batteryChange = abs(max($batteryValues) - min($batteryValues));
                        $statusBattery = ($batteryChange >= 0.2) ? "Drop" : "Stabil";
                    } else {
                        $statusBattery = ($batteryValue >= 3.4) ? "Stabil" : "Drop";
                    }
                } else if (in_array(substr($serialNumber, 0, 3), array('682', '692'))) {
                    $forwardFlow = substr($payloadValue, 12, 8);
                    $battery = strtoupper(substr($payloadValue, 76, 2));
                    $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                        return sprintf("%02X", hexdec($item));
                    }, array_reverse(str_split($forwardFlow, 2))));

                    $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;
                    $batteryValue = hexdec($battery) / 10;

                    // Hitung perubahan nilai baterai dari 3 timestamp terbaru
                    $batteryChangeQuery = "SELECT batteryValue FROM hasil_parsed_depok WHERE id_device_depok = '$deviceId' ORDER BY timestamp DESC LIMIT 30";
                    $batteryChangeResult = mysqli_query($conn, $batteryChangeQuery);
                    $batteryValues = [];
                    while ($batteryChangeRow = mysqli_fetch_assoc($batteryChangeResult)) {
                        $batteryValues[] = $batteryChangeRow['batteryValue'];
                    }

                    // Periksa perubahan nilai baterai
                    if (count($batteryValues) == 3) {
                        $batteryChange = abs(max($batteryValues) - min($batteryValues));
                        $statusBattery = ($batteryChange >= 0.2) ? "Drop" : "Stabil";
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

// Panggil fungsi untuk menyimpan data ke database dan Antares secara terus-menerus
while (true) {
    // Panggil fungsi untuk menyimpan data ke database
    $query = "SELECT pd.id, pd.id_device_depok, pd.payload, pd.timestamp, pd.rssi, pd.snr 
              FROM payload_device_depok pd 
              JOIN device_depok dd ON pd.id_device_depok = dd.id
              ORDER BY pd.id_device_depok ASC, pd.timestamp DESC"; // Urutkan berdasarkan id_device_depok dan timestamp

    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "Error fetching data from database: " . mysqli_error($conn) . "\n";
        exit(); // Jika terjadi error, hentikan eksekusi
    }

    $previousDeviceId = null;
    $chunk = array();
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['id_device_depok'] !== $previousDeviceId && !empty($chunk)) {
            // Proses semua data yang telah dikumpulkan untuk serial number sebelumnya
            saveDataAntaresByPayload($chunk);
            $chunk = array(); // Kosongkan kembali array chunk
        }
        $chunk[] = $row; // Tambahkan data ke dalam chunk
        $previousDeviceId = $row['id_device_depok'];
    }

    // Proses sisa data yang tersisa di chunk
    if (!empty($chunk)) {
        
        saveDataAntaresByPayload($chunk);
    }

    getAllDevicesData();
    saveDataAntaresByDeviceId();
    // Tunggu 30 detik sebelum mengambil data kembali
    sleep(30); // 30 detik = 0,5 menit
}

?>

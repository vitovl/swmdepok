<?php

include 'koneksi.php';
// include 'getdeviceantares.php';
include 'getpayloadantares.php';

$status= '';
$headerSerialNumber = array('620','702','602','682','692');

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
    global $conn , $headerSerialNumber;

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

        $check_query = "SELECT COUNT(*) AS total FROM hasil_parsed_depok WHERE serial_number = '$serialNumber'";
        $check_result = mysqli_query($conn, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);

        if ($check_row['total'] > 0) {
            updateDataAntares($serialNumber, $payloadValue, $timestamp, $RSSI, $SNR);
            continue;
        }

        if (in_array(substr($serialNumber, 0, 3), $headerSerialNumber)) {
            if (in_array(substr($serialNumber, 0, 3), array('620', '702', '602'))) {
                $forwardFlow = substr($payloadValue, 16, 8);
                $battery = strtoupper(substr($payloadValue, 54, 2));

                $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                    return sprintf("%02X", hexdec($item));
                }, array_reverse(str_split($forwardFlow, 2))));

                $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;
                $batteryValue = hexdec($battery) / 10;

                if ($batteryValue >= 3.6) {
                    $percentage = 100;
                } elseif ($batteryValue <= 2.8) {
                    $percentage = 0;
                } else {
                    $percentage = (($batteryValue - 2.8) / (3.6 - 2.8)) * 100;
                }

                if ($percentage == 0) {
                    $statusBattery = "Drop";
                } elseif ($percentage == 100) {
                    $statusBattery = "Stabil";
                }
            } else if (in_array(substr($serialNumber, 0, 3), array('682', '692'))) {
                $forwardFlow = substr($payloadValue, 12, 8);
                $battery = strtoupper(substr($payloadValue, 76, 2));

                $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                    return sprintf("%02X", hexdec($item));
                }, array_reverse(str_split($forwardFlow, 2))));

                $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;
                $batteryValue = hexdec($battery) / 10;

                if ($batteryValue >= 3.6) {
                    $percentage = 100;
                } elseif ($batteryValue <= 2.8) {
                    $percentage = 0;
                } else {
                    $percentage = (($batteryValue - 2.8) / (3.6 - 2.8)) * 100;
                }

                if ($percentage == 0) {
                    $statusBattery = "Drop";
                } elseif ($percentage == 100) {
                    $statusBattery = "Stabil";
                }
            }
        }

        $signalStatus = getSignalStatus($RSSI, $SNR);

        $insertSql = "INSERT INTO hasil_parsed_depok (serial_number, payload, RSSI , SNR, signalStatus, flowMeter, batteryValue, batteryStatus, lastUpdate) VALUES ('$serialNumber', '$payloadValue', '$RSSI', '$SNR', '$signalStatus', '$forwardFlowValue', '$batteryValue', '$statusBattery', '$timestamp')";

        $sqlInsert = mysqli_query($conn, $insertSql);
        if ($sqlInsert) {
            echo "Parsing Data berhasil disimpan ke databasee hasildata_depok\n";
        } else {
            echo "Error saving new data to database for device $serialNumber: " . mysqli_error($conn) . "\n";
        }

        updateDataAntares($serialNumber, $payloadValue, $timestamp, $RSSI, $SNR); // Panggil fungsi updateDataAntares
    }
}

function updateDataAntares($serialNumber, $payloadValue, $timestamp, $RSSI, $SNR) {
    global $conn, $headerSerialNumber;

    if (in_array(substr($serialNumber, 0, 3), $headerSerialNumber)) {
        if (in_array(substr($serialNumber, 0, 3), array('620', '702', '602'))) {
            $forwardFlow = substr($payloadValue, 16, 8);
            $battery = strtoupper(substr($payloadValue, 54, 2));

            $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                return sprintf("%02X", hexdec($item));
            }, array_reverse(str_split($forwardFlow, 2))));

            $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;
            $batteryValue = hexdec($battery) / 10;

            if ($batteryValue >= 3.6) {
                $percentage = 100;
            } elseif ($batteryValue <= 2.8) {
                $percentage = 0;
            } else {
                $percentage = (($batteryValue - 2.8) / (3.6 - 2.8)) * 100;
            }

            if ($percentage == 0) {
                $statusBattery = "Drop";
            } elseif ($percentage == 100) {
                $statusBattery = "Stabil";
            }
        } else if (in_array(substr($serialNumber, 0, 3), array('682', '692'))) {
            $forwardFlow = substr($payloadValue, 12, 8);
            $battery = strtoupper(substr($payloadValue, 76, 2));

            $forwardFlow_reversed = implode(' ', array_map(function ($item) {
                return sprintf("%02X", hexdec($item));
            }, array_reverse(str_split($forwardFlow, 2))));

            $forwardFlowValue = hexdec(str_replace(' ', '', $forwardFlow_reversed)) / 1000;
            $batteryValue = hexdec($battery) / 10;

            if ($batteryValue >= 3.6) {
                $percentage = 100;
            } elseif ($batteryValue <= 2.8) {
                $percentage = 0;
            } else {
                $percentage = (($batteryValue - 2.8) / (3.6 - 2.8)) * 100;
            }

            if ($percentage == 0) {
                $statusBattery = "Drop";
            } elseif ($percentage == 100) {
                $statusBattery = "Stabil";
            }
        }
    }

    $signalStatus = getSignalStatus($RSSI, $SNR);

    $updateQuery = "UPDATE hasil_parsed_depok SET payload = '$payloadValue', RSSI = '$RSSI', SNR = '$SNR', signalStatus = '$signalStatus', flowMeter = '$forwardFlowValue', batteryValue = '$batteryValue', batteryStatus = '$statusBattery', lastUpdate = '$timestamp' WHERE serial_number = '$serialNumber'";
    $updateResult = mysqli_query($conn, $updateQuery);

    if ($updateResult) {
        echo "Parsing Data successfully updated in the databaseeeee for device $serialNumber.\n";
    } else {
        echo "Error updating data in the database for device $serialNumber: " . mysqli_error($conn) . "\n";
    }
}

saveDataAntaresByPayload();

?>

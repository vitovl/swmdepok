<?php

include 'koneksi.php';

function getAllDevicesData() {
    $limit = 2000;
    $offset = 0;
    $device = [];
    global $conn;
    do {
        $devicesUrl = 'https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok?fu=1&ty=3&lim=' . $limit . '&ofst=' . $offset;

        $headers = [
            'X-M2M-Origin: 22d7ebb917b00bc8:b65db7ab728a0929',
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $devicesUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "Error fetching device data from Antares: $error";
            return;
        }

        $parsedData = json_decode($response, true);
        $serialNumbers = array_map(function ($item) {
            return basename($item);
        }, $parsedData['m2m:uril']);

        $newDevices = array_diff($serialNumbers, $device); // Get only new devices

        foreach ($newDevices as $number) {
            // Check if the device already exists in the database
            $query = "SELECT COUNT(*) AS count FROM device_depok WHERE serial_number = '$number'";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            $deviceExists = $row['count'] > 0;

            if (!$deviceExists) {
                // If the device doesn't exist, insert it into the database
                $insertQuery = "INSERT INTO device_depok (serial_number) VALUES ('$number')";
                $insertResult = mysqli_query($conn, $insertQuery);
                if ($insertResult) {
                    echo "Data for device $number successfully saved to database.\n";
                } else {
                    echo "Error saving data for device $number to database.\n";
                }
            } else {
                // If the device already exists, skip insertion and display a message
                echo "Data for device $number already exists in the database, skipping.\n";
            }
        }

        $device = array_merge($device, $newDevices);

        $offset += $limit;

    } while (count($newDevices) > 0);
}

getAllDevicesData()

?>
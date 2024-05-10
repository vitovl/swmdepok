<?php
include 'koneksi.php';

$sql = "SELECT id_device_depok, RSSI, SNR, signalStatus, flowMeter, batteryValue, batteryStatus, timestamp FROM hasil_parsed_depok";
$queryGetAllData = mysqli_query($conn, $sql);

$dataArray = array(); // Array untuk menyimpan data

// Mengelompokkan data berdasarkan id_device_depok dan tanggal
while ($row = mysqli_fetch_assoc($queryGetAllData)) {
    $id_device_depok = $row['id_device_depok'];
    $tanggal = date('Y-m-d', strtotime($row['timestamp'])); // Ambil tanggal dari timestamp
    
    // Buat kunci pengelompokkan berdasarkan id_device_depok dan tanggal
    $groupKey = $id_device_depok . '_' . $tanggal;
    
    if (!isset($dataArray[$groupKey])) {
        $dataArray[$groupKey] = array(
            'id_device_depok' => $id_device_depok,
            'tanggal' => $tanggal,
            'data' => array(),
            'rateDataFlow' => 0
        );
    }
    
    $dataArray[$groupKey]['data'][] = $row;
}

// Menghitung rateDataFlow untuk setiap kelompok id_device_depok dan tanggal
foreach ($dataArray as &$group) {
    $totalChanges = 0;
    $totalIterations = count($group['data']);
    
    if ($totalIterations > 1) {
        for ($i = 1; $i < $totalIterations; $i++) {
            $totalChanges += ($group['data'][$i]['flowMeter'] - $group['data'][$i - 1]['flowMeter']);
        }
        
        $group['rateDataFlow'] = $totalChanges * 24 / ($totalIterations - 1);
    } else {
        $group['rateDataFlow'] = 0; // Jika hanya ada satu data, rateDataFlow diatur menjadi 0
    }
    
    //Mengubah array ke format JSON
   // $jsonOutput = json_encode($dataArray);

    // Output hasilnya dalam format JSON
    //echo $jsonOutput;
    // //Output hasilnya dalam bentuk array untuk setiap kelompok
    foreach ($group['data'] as $data) {
        echo 'id_device_depok: ' . $data['id_device_depok'] . "<br>";
        // echo 'RSSI: ' . $data['RSSI'] . "<br>";
        // echo 'SNR: ' . $data['SNR'] . "<br>";
        echo 'signalStatus:' . $data['signalStatus'] . "<br>";
        // echo 'flowMeter: ' . $data['flowMeter'] . "<br>";
        echo 'rateDataFlow: ' . $group['rateDataFlow'] . "<br>";
        // echo 'batteryValue: ' . $data['batteryValue'] . "<br>";
        echo 'batteryStatus:' . $data['batteryStatus'] . "<br>";
        echo 'timestamp: ' . $data['timestamp'] . "<br>";
        echo "<br>";

    }
} 

?>
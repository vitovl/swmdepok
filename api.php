<?php
include 'koneksi.php';

$sql = "SELECT id_device_depok, RSSI, SNR, flowMeter, batteryValue, timestamp FROM hasil_parsed_depok";
$queryGetAllData = mysqli_query($conn, $sql);

$dataArray = array(); // Array untuk menyimpan data

while ($row = mysqli_fetch_assoc($queryGetAllData)) {
    $data = array(
        'id_device_depok' => $row['id_device_depok'],
        'RSSI' => $row['RSSI'],
        'SNR' => $row['SNR'],
        'flowMeter' => $row['flowMeter'],
        'batteryValue' => $row['batteryValue'],
        'timestamp' => $row['timestamp']
    );
    $dataArray[] = $data;
}

// Array untuk menyimpan rateDataFlow untuk setiap id_device_depok
$rateDataFlowArray = array();

// Menghitung rateDataFlow untuk setiap id_device_depok
foreach ($dataArray as $data) {
    $id_device_depok = $data['id_device_depok'];
    $flowMeter = $data['flowMeter'];
    
    if (!isset($rateDataFlowArray[$id_device_depok])) {
        $rateDataFlowArray[$id_device_depok] = 0;
    }
    
    $totalChanges = 0;
    $totalIterations = count($dataArray);
    
    for ($i = 1; $i < $totalIterations; $i++) {
        if ($dataArray[$i]['id_device_depok'] == $id_device_depok) {
            $totalChanges += ($dataArray[$i]['flowMeter'] - $dataArray[$i - 1]['flowMeter']);
        }
    }
    
    $rateDataFlow = $totalChanges * 24 / ($totalIterations - 1); // Menghitung rata-rata perubahan data per jam
    $rateDataFlowArray[$id_device_depok] = $rateDataFlow;
}

// Output hasilnya dalam bentuk array
foreach ($dataArray as $data) {
    echo 'id_device_depok: ' . $data['id_device_depok'] . "<br>";
    echo 'RSSI: ' . $data['RSSI'] . "<br>";
    echo 'SNR: ' . $data['SNR'] . "<br>";
    echo 'flowMeter: ' . $data['flowMeter'] . "<br>";
    echo 'rateDataFlow: ' . $rateDataFlowArray[$data['id_device_depok']] . "<br>";
    echo 'batteryValue: ' . $data['batteryValue'] . "<br>";
    echo 'timestamp: ' . $data['timestamp'] . "<br>";
    echo "<br>";
}
?>

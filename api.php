<?php
include 'koneksi.php';

$sql = "SELECT id_device_depok, RSSI, SNR, signalStatus, flowMeter, batteryValue, batteryStatus, timestamp FROM hasil_parsed_depok ORDER BY timestamp DESC";
$queryGetAllData = mysqli_query($conn, $sql);

$dataArray = array(); // Array untuk menyimpan data

// Mengelompokkan data berdasarkan id_device_depok
while ($row = mysqli_fetch_assoc($queryGetAllData)) {
    $id_device_depok = $row['id_device_depok'];

    // Buat kunci pengelompokkan berdasarkan id_device_depok
    if (!isset($dataArray[$id_device_depok])) {
        $dataArray[$id_device_depok] = array(
            'id_device_depok' => $id_device_depok,
            'data' => array(),
            'rateDataFlow' => 0
        );
    }

    // Simpan data terbaru saja untuk setiap id_device_depok
    if (empty($dataArray[$id_device_depok]['data'])) {
        $dataArray[$id_device_depok]['data'] = $row;
    }
}

// Menghitung rateDataFlow untuk setiap id_device_depok
foreach ($dataArray as &$group) {
    $data = $group['data'];

    // Mendapatkan semua data sebelumnya pada tanggal yang sama
    $queryPrevData = "SELECT flowMeter FROM hasil_parsed_depok WHERE id_device_depok = '{$data['id_device_depok']}' AND DATE(timestamp) = DATE('{$data['timestamp']}') AND timestamp < '{$data['timestamp']}' ORDER BY timestamp DESC";
    $resultPrevData = mysqli_query($conn, $queryPrevData);
    
    $totalChanges = 0;
    $totalIterations = 0;

    // Perhitungan total perubahan flowMeter dan total iterasi
    while ($prevData = mysqli_fetch_assoc($resultPrevData)) {
        $totalChanges += ($data['flowMeter'] - $prevData['flowMeter']);
        $totalIterations++;
    }

    if ($totalIterations > 0) {
        // Perhitungan rateDataFlow
        $rateDataFlow = ($totalChanges / $totalIterations) * 24;
        $group['rateDataFlow'] = $rateDataFlow;
    } else {
        // Jika tidak ada data sebelumnya, rateDataFlow diatur menjadi 0
        $group['rateDataFlow'] = 0;
    }

    // Menampilkan data terbaru untuk setiap id_device_depok
    echo 'id_device_depok: ' . $data['id_device_depok'] . "<br>";
    //echo 'RSSI: ' . $data['RSSI'] . "<br>";
    //echo 'SNR: ' . $data['SNR'] . "<br>";
    echo 'signalStatus: ' . $data['signalStatus'] . "<br>";
    //echo 'flowMeter: ' . $data['flowMeter'] . "<br>";
    echo 'rateDataFlow: ' . $group['rateDataFlow'] . " m3/hari" . "<br>";    //echo 'batteryValue: ' . $data['batteryValue'] . "<br>";
    echo 'batteryStatus: ' . $data['batteryStatus'] . "<br>";
    echo 'timestamp: ' . $data['timestamp'] . "<br>";
    echo "<br>";
} 
?>
    
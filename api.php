<?php
include 'koneksi.php';

$route = $_GET['Dashboard'] ?? null;

// Menentukan tindakan berdasarkan rute yang diberikan
switch ($route) {
    case 'DashboardAnalysisMonitoringDevice':
        $sql = "SELECT dp.serial_number, hp.RSSI, hp.SNR, hp.signalStatus, hp.flowMeter, hp.batteryValue, hp.batteryStatus, hp.timestamp FROM hasil_parsed_depok hp INNER JOIN device_depok dp ON hp.id_device_depok = dp.id ORDER BY hp.timestamp DESC";
        $queryGetAllData = mysqli_query($conn, $sql);

        $dataArray = array(); // Array untuk menyimpan data

        // Mengelompokkan data berdasarkan serial_number
        while ($row = mysqli_fetch_assoc($queryGetAllData)) {
            $serial_number = $row['serial_number'];

            // Buat kunci pengelompokkan berdasarkan serial_number
            if (!isset($dataArray[$serial_number])) {
                $dataArray[$serial_number] = array(
                    'serial_number' => $serial_number,
                    'data' => array(),
                    'rateDataFlow' => 0
                );
            }

            // Simpan data terbaru saja untuk setiap serial_number
            if (empty($dataArray[$serial_number]['data'])) {
                $dataArray[$serial_number]['data'] = $row;
            }
        }

        // Menghitung rateDataFlow untuk setiap serial_number
        foreach ($dataArray as &$group) {
            $data = $group['data'];

            // Mendapatkan semua data sebelumnya pada tanggal yang sama
            $queryPrevData = "SELECT flowMeter FROM hasil_parsed_depok WHERE id_device_depok = (SELECT id FROM device_depok WHERE serial_number = '{$data['serial_number']}') AND DATE(timestamp) = DATE('{$data['timestamp']}') AND timestamp < '{$data['timestamp']}' ORDER BY timestamp DESC";
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
                // Pembulatan menjadi 2 angka di belakang koma
                $rateDataFlow = round($rateDataFlow, 2);
                $group['rateDataFlow'] = $rateDataFlow;
            } else {
                // Jika tidak ada data sebelumnya, rateDataFlow diatur menjadi 0
                $group['rateDataFlow'] = 0;
            }
        }

        // Menghasilkan output dalam format JSON dengan format yang rapi dan diatur ke bawah
        echo json_encode($dataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    default:
        // Jika rute tidak dikenali, kirimkan respons error dalam format JSON
        echo json_encode(['error' => 'Invalid route']);
        break;
}
?>

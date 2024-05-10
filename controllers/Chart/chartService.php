<?php
include "../../koneksi.php";
function getAllDataGraphics() {
    global $conn;

    $sql = "SELECT dp.serial_number, hp.RSSI, hp.SNR, hp.signalStatus, hp.flowMeter, hp.batteryValue, hp.batteryStatus, hp.timestamp FROM hasil_parsed_depok hp INNER JOIN device_depok dp ON hp.id_device_depok = dp.id ORDER BY hp.timestamp DESC";
    $queryGetAllData = mysqli_query($conn, $sql);

    $dataArray = array(); // Array untuk menyimpan data
    $countData = mysqli_num_rows($queryGetAllData);
    if($countData > 0){
        $res = mysqli_fetch_all($queryGetAllData, MYSQLI_ASSOC);
        $data = [
            "status" => 200,
            "message" => "Get all data is success",
            "data" => $res,
        ];
        header("HTTP/1.0 200 OK");
        return json_encode($data);
    } else if ($countData == 0){
        $data = [
            "status" => 404,
            "message" => "Not found",
        ];
        header("HTTP/1.0 404 Not Found");
        return json_encode($data);
    } else {
        $data = [
            "status" => 500,
            "message" => "Internal server error",
        ];
        header("HTTP/1.0 500 Server Error");
        return json_encode($data);
    }
}
    getAllDataGraphics();
?>

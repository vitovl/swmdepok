<?php // belum terpakai
    include 'koneksi.php';
    include 'getdeviceantares.php';
    include 'getpayloadantares.php';

    $query = "SELECT * FROM paylaod_device_depok";
    $sql = mysqli_query($conn, $query);

    // if(isset($_POST['save_update'])) {  
    // echo "CEK";
        // getAllDevicesData();
        saveDataAntaresByDeviceId(); // Fetch data from getdataantares.php
    // }
?>

<?php
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'watermeter_db';

    $conn = mysqli_connect($host, $user, $pass, $db) or die(mysqli_error($conn));
  
    if ($conn) {
        mysqli_select_db($conn, $db);
    }
?>
<?php
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'watermeter_db';
    $conn = mysqli_connect($host, $user, $pass, $db);

    if ($conn) {
        mysqli_select_db($conn, $db);
        echo "success!. <br>" ;   
    }

    if(!$conn) {
        echo "fail! . <br>";
        die("Connection failed: " . mysqli_connect_error());
    }
    
?>
<?php
    include 'getpayloadantares.php';
    $sql = mysqli_query($conn, "SELECT * FROM payload_device_depok");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabel Data</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #dddddd;
            text-align: center;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<div class="table-responsive mt-3">
    <table class="table table-bordered table-hover border">
        <thead>
            <tr>
                <th>No</th>
                <th>Watermeter ID</th>
                ///
                <th>Data</th>
                <th>DevEUI</th>
                <th>RSSI</th>
                <th>SNR</th>
                ///
            </tr>
        </thead>
        <tbody>
            
            <?php
                $counter = 0; // Initialize $counter variable
                while ($result = mysqli_fetch_assoc($sql)){
                    $counter++; // Increment $counter for each row
            ?>
            <tr>
                <td><?php echo $counter; ?></td>
                <td><?php echo $result['serial_number']; ?></td>
                ///
                <td><?php echo $result['payload']; ?></td>
                <td><?php echo $result['deveui']; ?></td>
                <td><?php echo $result['rssi']; ?></td>
                <td><?php echo $result['snr']; ?></td>
                ///
            </tr>
            <?php
            }
            ?>
        </tbody>
        </tbody>
    </table>
</div>

</body>
</html>
       
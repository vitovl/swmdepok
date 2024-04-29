<?php
$b = $_POST["6f04180822682e60030000000000485f0300d05e03009e5e03002b3c000d04151701e80710002450"];
$data0 = strtoupper(substr($b, 0, 58));
$data1 = strtoupper(substr($b, 0, 2));
$data2 = substr($b, 2, 12);
$data3 = substr($b, 12, 20);
$data4 = strtoupper(substr($b, 20, 28));
$data5 = substr($b, 28, 36);
$data6 = substr($b, 36, 44);
$data7 = substr($b, 44, 52);

$formatted_data2 = implode(' ', str_split($data2, 2));

$data3_reversed = implode(' ', array_map('strtoupper', str_split(strrev($data3), 2)));
$data4_reversed = implode(' ', array_map('strtoupper', str_split(strrev($data4), 2)));
$data5_reversed = implode(' ', array_map('strtoupper', str_split(strrev($data5), 2)));
$data6_reversed = implode(' ', array_map('strtoupper', str_split(strrev($data6), 2)));
$data7_reversed = implode(' ', array_map('strtoupper', str_split(strrev($data7), 2)));

$decimal_data3 = hexdec(str_replace(' ', '', $data3_reversed)) / 1000;
$decimal_data4 = hexdec(str_replace(' ', '', $data4_reversed)) / 1000;
$decimal_data5 = hexdec(str_replace(' ', '', $data5_reversed)) / 1000;
$decimal_data6 = hexdec(str_replace(' ', '', $data6_reversed)) / 1000;
$decimal_data7 = hexdec(str_replace(' ', '', $data7_reversed)) / 1000;

echo "start_identifier : $data1\n";
echo "S/N 6822084405 : $formatted_data2\n";
echo "Forward flow : $decimal_data3 m3\n";
echo "Reversed flow : $decimal_data4 m3\n";
echo "Historical forward flow1 : $decimal_data5 m3\n";
echo "Historical forward flow2 : $decimal_data6 m3\n";
echo "Historical forward flow3 : $decimal_data7 m3\n";
?>
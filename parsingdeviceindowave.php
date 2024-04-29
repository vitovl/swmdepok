<?php
$status ='';
$b = "6f011009216006a1823c0f00000000002b0229051d04e807000000240d";

// Mengambil data1
$data1 = strtoupper(substr($b, 0, 2)); // Mengonversi huruf 'f' menjadi huruf kapital
$data2 = substr($b, 16, 8);
$data3 = strtoupper(substr($b, 54, 2));

$formatted_data3 = implode(' ', str_split($data3, 2));

$hex_data2 = implode(' ', array_map(function($item) {
    return sprintf("%02X", hexdec($item));
}, str_split($data2, 2)));

$data2_reversed = implode(' ', array_map(function($item) {
    return sprintf("%02X", hexdec($item));
}, array_reverse(str_split($data2, 2))));

$decimal_data2 = hexdec(str_replace(' ', '', $data2_reversed)) / 1000;
$decimal_data3 = hexdec($data3) / 10;


// Konversi nilai tegangan ke rentang 0% hingga 100%`
if ($decimal_data3 >= 3.6) {
    $percentage = 100;
} else if ($decimal_data3 <= 2.8) {
    $percentage = 0;
} else {
    $percentage = (($decimal_data3 - 2.8) / (3.6 - 2.8)) * 100; // Menghitung persentase
}

if ($decimal_data3 >= 2.8 && $decimal_data3 <= 3.6) {
    $status = "Stabil";
} else if ($decimal_data3 < 2.8) {
    $status = "Drop";
}


echo "Forward flow: " . $decimal_data2 . " m3\n";
echo "Battery (V) : " . $decimal_data3 . " Volt\n";
echo "Battery : " . $percentage . "% \n";
echo "Status Baterai : " .  $status . "\n";
?>
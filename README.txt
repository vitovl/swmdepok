1. bikin 1 fungsi mengambil data antares berdasarkan device id 
- table device_depok mempunyai 1 coloun deviceID *DONE
- buat table payload_device_depok dengan coloum (payload, devEUI, snr , rssi)
- fungsi dengan url https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/{{deviceId, from db saya (device_depok.serial_number)/la
- buat query untuk simpan ke table payload_device_depok berdasarkan response body dr payload diatas


example:

ikuti langkah2 diatas:


saveDataAntaresByDeviceId(deviceId), mempunyai query untuk mengambil seluruh serial_number di device_depok


for (index = 0; index < totalDevice; index++) {
	saveDataAntaresByDeviceId(totalDevice[index]);
}

2. parsing payload 
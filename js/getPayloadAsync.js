const mysql = require(`mysql-await`);
// const { getPayload } = require("./getPayload");

(async () => {
  const connection = mysql.createConnection({
    host: "localhost",
    user: "root",
    password: "",
    database: "watermeter_db",
  });

  const serialNumbers = await connection.awaitQuery("SELECT serial_number FROM device_depok");
  console.log(serialNumbers)
  setTimeout(async () => {
    for (const iterator of serialNumbers) {
      try {
        const serialNumber = Object.values(iterator).toString();
        const payloadRes = await fetch(`https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/${serialNumber}/la`, {
            method: "GET",
            headers: {
                "X-M2M-Origin": "22d7ebb917b00bc8:b65db7ab728a0929",
                "Content-Type": "application/json;ty=4",
                "Accept": "application/json",
            }
        });
        const payloadData = await payloadRes.json() || "";
        if(payloadData['m2m:cin'] && payloadData['m2m:cin']['con']) {
          // console.log(payloadData['m2m:cin']['con'])
          const rawPayload = JSON.parse(payloadData['m2m:cin']['con']);
          const data = rawPayload["data"];
          const dev = rawPayload["devEui"];
          const radio = rawPayload["radio"];
          const RSSI = radio["hardware"]['rssi'];
          const SNR = radio["hardware"]['snr'];
          // console.info(inspect(radio, { depth: true }))
          console.log(RSSI)
          const timeStamp = payloadData['m2m:cin']['ct'];
          // console.log(timeStamp)
          let date = new Date(timeStamp);
  
          const checkDevices = await connection.awaitQuery(`SELECT * FROM paylaod_device_depok WHERE serial_number = '${serialNumber}'`)
          if(checkDevices) {
            const res = await connection.awaitQuery(`UPDATE paylaod_device_depok SET payload = '${data}', devEUI = '${dev}', rssi = '${RSSI}', snr = '${SNR}' WHERE serial_number = '${serialNumber}'`)
            if(res) {
              console.log(`Data successfully updated 2 in the database for device ${serialNumber}.`)
            } else {
              console.log(`Error updating data in the database for device ${serialNumber}: `)
            }
          }
        }
      } catch (error) {
        console.log("Error fetching device data from Antares: ", error);
        continue;
      }
    }
    connection.awaitEnd();
  }, 30000)
})();
// getPayload();
console.log("test");
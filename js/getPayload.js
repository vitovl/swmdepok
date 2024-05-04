// const mysql = require('mysql');
// const { inspect } = require('util');

// function getSerialNum(conn){
  
//   const query =  "SELECT serial_number FROM device_depok";
//   return new Promise((resolve, reject) => {
//     conn.query(query, (err, res, fields) => {
//       if (err) return reject(err);
//       return resolve(res);
//     });
//   });
// }

// const conn = mysql.createConnection({
//   host: "localhost",
//   user: "root",
//   password: "",
//   database: "watermeter_db",
// });

// let arr = [];
// conn.connect(async function(err) {
  
//   const serialNumbers = await getSerialNum(conn);
//   for (const iterator of serialNumbers) {
//     if (err) {
//       console.log("Error fetching device data from Antares: ", err);
//       continue; // Skip to the next device if there's an error
//     }
//     try {
//       const serialNumber = Object.values(iterator).toString();
//       const payloadRes = await fetch(`https://platform.antares.id:8443/~/antares-cse/antares-id/SmartWaterMeter_Depok/${serialNumber}/la`, {
//           method: "GET",
//           headers: {
//               "X-M2M-Origin": "22d7ebb917b00bc8:b65db7ab728a0929",
//               "Content-Type": "application/json;ty=4",
//               "Accept": "application/json",
//           }
//       });
//       const payloadData = await payloadRes.json() || "";
//       if(payloadData['m2m:cin'] && payloadData['m2m:cin']['con']) {
//         // console.log(payloadData['m2m:cin']['con'])
//         const rawPayload = JSON.parse(payloadData['m2m:cin']['con']);
//         const data = rawPayload["data"];
//         const dev = rawPayload["devEui"];
//         const radio = rawPayload["radio"];
//         const RSSI = radio['rssi'];
//         const SNR = radio['snr'];
//         // console.info(inspect(radio, { depth: true }))
//         const timeStamp = payloadData['m2m:cin']['ct'];
//         // console.log(timeStamp)
//         let date = new Date(timeStamp);

//         conn.query(`SELECT * FROM paylaod_device_depok WHERE serial_number = '${serialNumber}'`, (err, res) => {
//           if (err) throw err;
//           if(res) {
//             conn.query(`UPDATE paylaod_device_depok SET payload = '${data}', devEUI = '${dev}', rssi = '${RSSI}', snr = '${SNR}' WHERE serial_number = '${serialNumber}'`, (err, res) => {
//               if (err) {
//                 console.log(`Error updating data in the database for device ${serialNumber}: `)
//                 throw err;
//               }
//               console.log(`Data successfully updated 2 in the database for device ${serialNumber}.`)
//             })
//           }
//         })
//         // console.log(test);
//       // console.log(date)
//       }
// //       {
// //         "type":"uplink",
// //         "port":8,
// //         "data":"6f6110092160060144470b00000000002b1129060205e807000000248c",
// //         "counter":14000,
// //         "devEui":"8cf95720000739a2",
// //         "radio":{
// //           "gps_time":1398617235199,
// //           "hardware":{
// //             "snr":5.8,"rssi":-104
// //           },
// //           "datarate":4,
// //           "modulation":{
// //             "bandwidth":125000,"spreading":8},"delay":0.
// // 07692718505859375,"freq":921.6,"size":42}}
//     } catch (error) {
//       console.log(error);
//       continue;
//     }
//   }
//   console.log(arr);
//   conn.end();
// });

const mysql = require(`mysql-await`);

const getPayload = async () => {
  const connection = mysql.createConnection({
    host: "localhost",
    user: "root",
    password: "",
    database: "watermeter_db",
  });

  const serialNumbers = await connection.awaitQuery("SELECT serial_number FROM device_depok");
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
            console.log(`Data successfully updated 1 in the database for device ${serialNumber}.`)
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
}

console.log("test");

module.exports = { getPayload }
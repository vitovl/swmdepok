<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Accept, Content-Type, ngrok-skip-browser-warning');
header('Access-Control-Allow-Method: GET, POST, PUT, DELETE');
// header('Content-Type: application/json');
// header("Access-Control-Allow-Credentials: true");

include './chartservice.php';

  // $reqMethod = $_SERVER["REQUEST_METHOD"]; // return which request method was used to access the page; e.g. 'GET', 'HEAD', 'POST', 'PUT'. 
  // // print_r($reqMethod);
  // if($reqMethod != "GET") { //if method is not GET 
  //   $data = [
  //     'status' => 405,
  //     'message' => $reqMethod . 'Method not allowed!'
  //   ];
  //   header("HTTP/1.0 405 Method Not Allowed");
  //   echo json_encode($data);
  // } else if($reqMethod == "GET"){
      //method is GET
      if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('HTTP/1.1 200 OK');
    };
    $allChartData = getAllDataGraphics();
    echo $allChartData;
  
?>

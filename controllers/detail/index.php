<?php

  // header('Access-Control-Allow-Headers: Content-Type');
  // header('Access-Control-Allow-Origin: *');
  // header('Access-Control-Allow-Method: GET, POST, PUT, DELETE');
  // header('Content-Type: application/json');

  include_once('./detailService.php');
  $reqMethod = $_SERVER["REQUEST_METHOD"]; // return which request method was used to access the page; e.g. 'GET', 'HEAD', 'POST', 'PUT'. 

  if($reqMethod != "GET") { //if method is not GET 
    $data = [
      'status' => 405,
      'message' => $reqMethod . 'Method not allowed!'
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
  };

  //method is GET
  $detailDevice = getDetailDevice();
  echo $detailDevice;
  
?>


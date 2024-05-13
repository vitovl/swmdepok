<?php

  header('Access-Control-Allow-Origin:*');
  header('Content-Type: Application/json');
  header('Access-Control-Allow-Method: GET');

  include 'chartService.php';

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
    $allChartData = getAllDataGraphics();
    echo $allChartData;
  // }

  
  
?>


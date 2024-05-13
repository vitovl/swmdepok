<?php 
  include ("../controllers/Chart/chartController.php");
  include_once("./functions.php");
  print_r($_SERVER);
  run($_SERVER['REQUEST_URI'], [
    '/chart' => function () {
       echo getAllDataChart();
    },
    '/index' => function () { 
      echo "Test";
    }
  ]);
?>

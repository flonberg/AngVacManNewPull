<?php
require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL.php';
require_once './mailLib.php';
require_once './safeSQL.php';
header("Content-Type: application/json; charset=UTF-8");
$handle = connectDB_FL();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./Alog/editCompCovError.txt");
if (strpos(getcwd(), 'dev') !== FALSE)
    $level = 'dev';
else 
    $level = 'prod';	
$fp = fopen("./log/editCompCovLog.txt", "w+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
$std = print_r($_GET, true); fwrite($fp, "\r\n GET has \r\n". $std);

<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
require_once('workdays.inc');
require_once('dosimetristList.php');
include('isHollidayLib.php');
ini_set("error_log", "./log/getCompCovErrors.txt");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$handle = connectDB_FL();
	$fp = fopen("./Alog/getCompCovLog.txt", "w+");
	$todayString =  date('Y-m-d H:i:s');
	fwrite($fp, "\r\n $todayString");
	fwrite($fp, "\r\n vidxToSee is  ". $_GET['vidx']);
    $res = Array("vidx"=> $_GET['vidx']);
    $selStr = "SELECT idx,vidx,CovererUserKey,date,deleted,accepted, physicians.LastName FROM MD_TA_Coverage
    JOIN physicians on MD_TA_Coverage.CovererUserKey=physicians.UserKey
    WHERE vidx = ".$_GET['vidx'];
    fwrite($fp, "\r\n ".$selStr);
    $row = Array();
    $dB = new getDBData( $selStr, $handle);
    while ($assoc = $dB->getAssoc()){
        $row[$assoc['idx']] = $assoc;
    }
    echo json_encode($row);
    
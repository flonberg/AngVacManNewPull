<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
ini_set("error_log", "./log/getAllCompCovErrors.txt");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$handle = connectDB_FL();

	$fp = fopen("./Alog/getAllCompCovLog.txt", "w+");
	$todayString =  date('Y-m-d H:i:s');
	fwrite($fp, "\r\n $todayString");
    $selStr = "SELECT TOP(200) idx,vidx,CovererUserKey,date,deleted,accepted 
        FROM  MD_TA_Coverage 
        ORDER BY idx DESC";
   
    fwrite($fp, "\r\n ".$selStr);
    $row = Array();
    $i = 0;
    $dB = new getDBData( $selStr, $handle);
    while ($assoc = $dB->getAssoc()){
        $row[$assoc['vidx']] = $assoc;
    }
  //  echo "<pre>"; print_r($row); echo "</pre>";
    echo json_encode($row);
   // echo "<br> hellow";
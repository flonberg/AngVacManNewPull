<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
ini_set("error_log", "./Alog/getMDServiceError.txt");
$handle = connectDB_FL();
$fp = fopen("./Alog/getMDtAsLog.txt", "w+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
    $selStr = "SELECT * FROM mdservice order by service";
    $dB = new getDBData($selStr, $handle);
    $i = 1;
    while ($assoc = $dB->getAssoc())
        $row[$assoc['idx']] = $assoc;
  //  sort($row);    
    $jData = json_encode($row);
    echo $jData;    
?>
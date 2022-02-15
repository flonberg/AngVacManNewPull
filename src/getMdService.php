<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';

$handle = connectDB_FL();
$fp = fopen("./Alog/getMDtAsLog.txt", "w+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
    $selStr = "SELECT * FROM mdservice";
    $dB = new getDBData($selStr, $handle);
    while ($assoc = $dB->getAssoc())
        $row[$assoc['idx']] = $assoc['service'];
    $jData = json_encode($row);
    echo $jData;    
?>
<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';

$handle = connectDB_FL();
	$fp = fopen("./Alog/getMDsByServiceLog.txt", "w+");
	$todayString =  date('Y-m-d H:i:s');
	fwrite($fp, "\r\n $todayString");
	$s =  print_r($_GET, true); fwrite($fp, $s); 
	$selStr = "SELECT UserKey, service, LastName FROM physicians WHERE Rank ='0' ORDER BY LastName";
	$dB = new getDBData($selStr, $handle);
	$i = 0;
	while ($assoc = $dB->getAssoc()){
			$selStr2 = "SELECT UserID from users WHERE UserKey ='".$assoc['UserKey']."'";
			$assoc['UserID'] = getSingle($selStr2, "UserID", $handle);
			$row[$i++] = $assoc;
	}
	$jData = json_encode($row);
	fwrite($fp, "\r\n ". $jData);
	echo $jData;
//echo "<pre>"; print_r($row); echo "</pre>";	




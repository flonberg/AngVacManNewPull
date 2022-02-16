<?php

require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
$handle = connectDB_FL();
//$fp = fopen("./Alog/getLoogedInUserKey.txt", "a+");$todayString =  date('Y-m-d H:i:s');fwrite($fp, "\r\n $todayString");
$selStr = "SELECT UserKey FROM users WHERE UserID ='".$_GET['userid']."'";
fwrite($fp, "\r\n $selStr \r\n");
$loggedInUserKey = getSingle($selStr, 'UserKey',$handle);
$data = array('LoggedInUserKey'=>$loggedInUserKey);
$jData = json_encode($data);
echo $jData;
exit();
?>
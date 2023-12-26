<?php

require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
$handle = connectDB_FL();
$handleBB = connectBB();
$debug = isset($_GET['debug']) ? true : false;
$fp = fopen("./Alog/getLoggedInUserKey.txt", "a+");$todayString =  date('Y-m-d H:i:s');fwrite($fp, "\r\n $todayString");
$selStr = "SELECT UserKey FROM users WHERE UserID ='".$_GET['userid']."'";
fwrite($fp, "\r\n $selStr \r\n");
$loggedInUserKey = getSingle($selStr, 'UserKey',$handle);
$selStr2 = "SELECT UserKey FROM MDtimeAwayManagers WHERE UserID ='".$_GET['userid']."'";
fwrite($fp, "\r\n $selStr2 \r\n");
$TAmanagerUserKey = getSingle($selStr2, 'UserKey',$handleBB);
$data['LoggedInUserKey']=$loggedInUserKey;
if (isset($TAmanagerUserKey) && $TAmanagerUserKey > 0){
  $data['TAman'] = $TAmanagerUserKey;
  $data['isManager'] = true;
}
else
  $data['isManager'] = false;
$jData = json_encode($data);
echo $jData;
exit();
?>
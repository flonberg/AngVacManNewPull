<?php
require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL.php';
require_once './mailLib.php';
require_once './mailLib2.php';
require_once './safeSQL.php';
header("Content-Type: application/json; charset=UTF-8");
$handle = connectDB_FL();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./Alog/editParamError.txt");
if (strpos(getcwd(), 'dev') !== FALSE)
    $level = 'dev';
else 
    $level = 'prod';	
$fp = fopen("./Alog/editParamLog.txt", "w+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
$std = print_r($_GET, true); fwrite($fp, "\r\n GET has \r\n". $std);
$updateStr = "UPDATE TOP(1) MDtimeAway SET ".$_GET['name']."='".$_GET['value']."' WHERE vidx = ".$_GET['vidx'];
fwrite($fp, "\r\n $updateStr");
$res = sqlsrv_query($handle, $updateStr);
if( $res === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($fp, $dtr);}
else 
    fwrite($fp, '\r\n Update suceeded');///
enterInMD_TimeAwayChanges();
$vidxParams = getVidxParams($_GET['vidx']);
//   new StaffEmailClass($vidxParams, $vidxParams->vidx, $handle, 2);
exit();
function enterInMD_TimeAwayChanges(){
    global $handle, $fp, $level;
    if ($level == 'prod')
        $prod = 1; 
    else
        $prod = 0; 
    $insStr = "INSERT INTO MD_TimeAwayChanges (vidx,ColChanged,newColVal, EmailSent,prod, date) values (".$_GET['vidx'].",'".$_GET['name']."','".$_GET['value']."',0,$prod,GETDATE())";
    fwrite($fp, "\r\n $insStr \r\n");
    $res = sqlsrv_query($handle, $insStr);
if( $res === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($fp, $dtr);}
else 
    fwrite($fp, '\r\n INSERT suceeded \r\n');
}
function getMDlastName($userkey){
    global $handle;
   $selStr = "SELECT LastName FROM physicians WHERE UserKey = $userkey";
   return getSingle($selStr, 'LastName', $handle );
}
function getVidxParams($vidx){
    global $handle, $fp;
    $selStr = "SELECT m.vidx, m.userid, m.userkey, m.endDate, m.startDate, p.LastName
    FROM MDtimeAway m
    LEFT JOIN physicians p
    on p.UserKey = m.userkey
    WHERE m.vidx = ".$_GET['vidx'];
    
    fwrite($fp, "\r\n $selStr");
    $stmt = sqlsrv_query($handle, $selStr);
    $obj = sqlsrv_fetch_object( $stmt);
    $obj->goAwayerUserKey = $obj->userkey;
    $obj->goAwayerLastName = $obj->LastName;
    $obj->startDate = $obj->startDate->format("Y-m-d");
    $obj->endDate = $obj->endDate->format("Y-m-d");

    ob_start(); var_dump($obj);$data = ob_get_clean();fwrite($fp, "\r\n data is  ". $data);
    return $obj;
}
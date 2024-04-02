<?php
require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL.php';
require_once './mailLib2.php';
$handle = connectDB_FL();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./Alog/emailChangeError.txt");
if (strpos(getcwd(), 'dev') !== FALSE)
    $level = 'dev';
else 
    $level = 'prod';	
$fp = fopen("./Alog/emailChangeLog.txt", "w+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
$std = print_r($_GET, true); fwrite($fp, "\r\n GET has \r\n". $std);
$mode = 0;                                                          // Dr. ___ is going away
$vidxParams = getVidxParams($_GET['vidx']);
new StaffEmailClass($vidxParams, $vidxParams->vidx, $handle, 1); 
$newCoverageA = isCoverageChange($vidxParams->vidx);
  
function getVidxParams($vidx){
    global $handle, $fp;
    $selStr = "SELECT m.vidx, m.userid, m.userkey, m.endDate, m.startDate, m.coverageA, p.LastName
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
function isCoverageChange($vidx){
    global $handle, $fp;
    $selStr = "SELECT * FROM MD_TimeAwayChanges WHERE vidx = $vidx AND ColChanged = 'coverageA' AND EmailSent = 0";
    fwrite($fp, "\r\n $selStr \r\n");
    $res = sqlsrv_query($handle, $selStr);
    if( $res === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($fp, $dtr);}    
    $obj = sqlsrv_fetch_object( $res);
    if (!is_object($obj))
        return $obj;
    else
        return false;       
}
function getCoverageA_Params($userkey){
    global $handle, $fp; 
    $selStr = "SELECT LastName, Email FROM physicians WHERE UserKey = $userkey";
    $res = sqlsrv_query($handle, $selStr);
    if( $res === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($fp, $dtr);} 
    $obj = sqlsrv_fetch_object( $res);
    if (!is_object($obj))
        return $obj;     
}
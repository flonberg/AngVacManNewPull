<?php
require_once("./mailLib2.php");
require_once 'H:\inetpub\lib\esb\_dev_\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./log/enterAngVacError.txt");
$handle = connectDB_FL();
$mailfp = openLogFile();
var_dump($mailfp);
$msg = Array("paragraph 1", "paragraph2");
//$test1 = new basicHTMLMail("flonberg@mgh.harvard.edu","Physician Time Away", $msg, $handle);
//$test2 = new CovererEmail("Hong", "flonberg@mgh.harvard.edu","Chan",'chan','2024-02-02','2024-02-10', 24);
$selStr = "SELECT vidx, userkey, startDate, endDate, coverageA, physicians.LastName FROM MDtimeAway
    INNER JOIN physicians on MDtimeAway.coverageA=physicians.UserKey WHERE vidx= ".$_GET['vidx'];
    $stmt = sqlsrv_query( $handle, $selStr);
    if( $stmt === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($mailfp, $dtr);}
$level = strpos(getcwd(), 'dev') !== FALSE ? 1 : 0;                         // level == 1 is DEV
var_dump($level);

$dB = new getDBData($selStr, $handle);
$assoc = $dB->getAssoc();
var_dump($assoc);
$goAwayerAddress = getSingle("SELECT Email FROM physicians WHERE UserKey =".$assoc['userkey'], 'Email', $handle);
if ($level == 1)
    $goAwayerAddress = 'flonberg@mgh.harvard.edu';
$ml = new CoverageNotAcceptedEmail( $assoc['startDate']->format('Y-m-d'), $assoc['endDate']->format('Y-m-d'), $assoc['LastName'],$goAwayerAddress, $handle);
exit();
function openLogFile(){
    $today = date('Y-m-d');
    $in = 0;
    do {																			// put index in case of permission failure
        $fp = fopen("./Alog/mailLib2Log".$today."_".$in.".txt", "w+");			
        if ($in++ > 5)
            break;
        }
	while ($fp ===FALSE);
    return $fp; 
}
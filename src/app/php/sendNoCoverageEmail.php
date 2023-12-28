<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL_dev_.php';
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./log/sendNoCoverageError.txt");

if (strpos(getcwd(), 'dev') !== FALSE)
	$level = 'dev';
else 
	$level = 'prod';	
$fp = makeLogFile();
$handle = connectDB_FL();
$handleBB = connectBB();
$debug = $_GET['debug'] == '1' ? true : false;
$dates = makeLast2Weeks();
getNoCovTAs($dates);
exit();
/**
 * make dates for go back 2 weeks
 */
function makeLast2Weeks(){
    global $fp;
    $today = new DateTime(); $todayString = $today->format('Y-m-d');
    $TwoWeeksAhead=date_create();
    date_add($TwoWeeksAhead,date_interval_create_from_date_string("2 weeks"));
    $TwoWeeksAheadString =  date_format($TwoWeeksAhead,"Y-m-d");
    $dates =  array($TwoWeeksAheadString,$todayString);
    var_dump($dates);
    $dstr = print_r($dates, true); fwrite($fp, "\r\n dates are \r\n". $dstr);
    return $dates;
}
function makeLogFile(){
    $in = 0;
    $today = new DateTime(); $todayString = $today->format('Y-m-d');
    do {																			// put index in case of permission failure
        $fp = @fopen("./log/TwoWeeksBackLog".$todayString."_".$in.".txt", "w+");			
        if ($in++ > 5)
            break;
        }
        while ($fp ===FALSE);
    return $fp;    
}
function getNoCovTAs($dates){
    global $handle;
    $selStr = "SELECT startDate, endDate, userid, coverageA FROM MDtimeAway WHERE startDate > '".$dates[1]."' AND startDate < '".$dates[0]."' AND reasonIdx < 9 AND coverageA = '0'";
    $dB = new getDBData($selStr, $handle);
    $i = 0;
    while ($assoc = $dB->getAssoc()){
        $row[$i++] = $assoc;
        $selStr2 = "SELECT physicians.Email, physicians.LastName,physicians.UserKey, users.UserID 
            FROM physicians
            LEFT JOIN users on users.UserKey = physicians.UserKey WHERE users.UserID = '".$assoc['userid']."'";
        echo "<br> 5555 $selStr2 <br>";    
        $dB2 = new getDBData( $selStr2, $handle);
        $assoc2 = $dB2->getAssoc();
    echo "<br> 5858 <br>"; var_dump($assoc2);    

    }
    var_dump($row);
    return $row;
    
}
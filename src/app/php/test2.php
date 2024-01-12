<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./log/test2.txt");
require_once 'H:\inetpub\lib\sqlsrvLibFL_dev_.php';
$fp = makeLogFile();
$handle = connectDB_FL();
$numWeeks = 1;
$debug = FALSE; if (isset($_GET['debug'])) $debug = TRUE;
if (isset($_GET['numWeeks']))
    $numWeeks = $_GET['numWeeks'];
$remColName = "OneWeekReminder";                                           
if ($numWeeks == 2)
        $remColName = "TwoWeekReminder";
$oneWeekTAs = getTAs($numWeeks, $remColName);
foreach ($oneWeekTAs as $key => $val){
    sendEmails($val);
    updateMDtimeAway($val, $remColName);
}
function updateMDtimeAway($TBDtA, $remColName){
    global $handle, $fp;
    $updateStr = "UPDATE TOP(1) MDtimeAway SET $remColName = 1 WHERE vidx = '".$TBDtA['vidx']."'";
    echo "<br> $updateStr";
    try { 
        $res = sqlsrv_query($handle, $updateStr);
        } 	catch(Exception $e) {
                fwrite($fp, "Exception is ". $e);
            }
    if ($res !== FALSE){
        fwrite($fp, "\r\n Sucess SQL \r\n". $updateStr);
    }
    else {
        fwrite($fp, "\r\n update failed for $updateStr");	
        $errs = print_r( sqlsrv_errors(), true); fwrite($fp, $errs);
    }        
}
/**
 * Get the tAs 
 */
function getTAs($numWeeks, $remColName){
    global $handle;
    $date    = new DateTime();                                                  // Creates new DatimeTime for today
    $today = $date->format("Y-m-d");
    $Weeks = $date->modify( '+ '.$numWeeks.' weeks' );                          // go ahead $numWeeks weeks
    $WeeksFormat = $Weeks->format('Y-m-d');

    $selStr = "SELECT vidx,userkey,startDate, userid, coverageA, CovAccepted, OneWeekReminder, TwoWeekReminder FROM MDtimeAway WHERE startDate <= '".$WeeksFormat."' 
        AND startDate > '".$today."' AND ".$remColName ." < 1 AND coverageA = 0 AND reasonIdx < 90";
    $dB = new getDBData($selStr, $handle);
    $i = 0;
    while ($assoc = $dB->getAssoc()){
        $selStr2 = "SELECT LastName, Email FROM physicians WHERE UserKey = ".$assoc['userkey'];
        $dB2 = new getDBData($selStr2, $handle);
        $assoc2 = $dB2->getAssoc();
        if (is_array($assoc2))
            $row[$i++] = array_merge($assoc, $assoc2);
    }
    echo "<pre>"; print_r($row); echo "</pre>";
    return $row;
}
function sendEmails($TBDtAs){
    global $fp, $debug;
    $link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?vidxToSee=".$TBDtAs['vidx']."&userid=".$TBDtAs['userid'];	
    $mailAddress = $TBDtAs['Email'];								
	$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
	$subj = "Coverage for Time Away";
    $msg = "Dr. ".$TBDtAs['LastName'] ."<br>";
    $msg .= "<p>You have scheduled a Time Away starting on ".$TBDtAs['startDate']->format('Y-m-d')."</p>";
    $msg .= "<p> The coverage for this Time Away is TBD </p>";
    $msg .= "<p> Please nominate a coverer as soon as possible useing the below link </p>";
    $message = '
    <html>
        <head>
             <title> Time Away Coverage </title>
             <body>
             <p>
             '. $msg .'
             </p>
             <p>
             <a href='.$link .'> Nominate Coverer </a>
         </body>
     </head>	
    </html>
     '; 
	$sendMail = new sendMailClassLib($mailAddress,  $subj, $message);
    if (!$debug)
        $sendMail->send();	
}
function makeLogFile(){
    $in = 0;
    $today = new DateTime(); $todayString = $today->format('Y-m-d');
    do {																			// put index in case of permission failure
        $fp = @fopen("./log/sendCoverageTBDLog".$todayString."_".$in.".txt", "w+");			
        if ($in++ > 5)
            break;
        }
        while ($fp ===FALSE);
    return $fp;    
}
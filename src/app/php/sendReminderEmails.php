<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./Alog/sendReminderError.txt");
require_once 'H:\inetpub\lib\sqlsrvLibFL_dev_.php';
require_once './mailLib2.php';
$fp = makeLogFile();
$handle = connectDB_FL();
$vidx = 266;
if ($_GET['vidx'])
    $vidx = $_GET['vidx'];
$goAwayerParams =getGoAwayerParamsFromVidx($vidx);
//$TaParams = getTimeAwayParams($vidx);
$TaParams = getTimeAwayParamsObj($vidx);
//$startDate = $TaParams['startDate']->format('m-d-Y');
//$endDate = $TaParams['endDate']->format('m-d-Y');
$startDate = $TaParams->startDate->format('m-d-Y');
$endDate = $TaParams->endDate->format('m-d-Y');
//$CovererParams = getCoverParams(($TaParams['coverageA']));
$CovererParams = getCoverParams(($TaParams->coverageA));//
echo "<pre>  22 CovererParams "; print_r($CovererParams); echo "</pre>";
//reSendSimpleCoverageRequest($vidx,$goAwayerParams,$TaParams,$CovererParams,$startDate,$endDate);
// sendToStaff($vidx);
reSendCompoundCoverage($vidx);
exit();

/*$numWeeks = 2;
$debug = FALSE; if (isset($_GET['debug'])) $debug = TRUE;
if (isset($_GET['numWeeks']))
    $numWeeks = $_GET['numWeeks'];
$remColName = "OneWeekReminder";                                           
if ($numWeeks == 2)
        $remColName = "TwoWeekReminder";
$oneWeekTAs = getTAs($numWeeks, $remColName);
if (is_array($oneWeekTAs))
    foreach ($oneWeekTAs as $key => $val){
        sendEmails($val);
        updateMDtimeAway($val, $remColName);
    }
exit();  
*/
function sendToStaff($vidx){
    global $handle;
    $TaParams = getTimeAwayParams($vidx);
    new StaffEmailClass($TaParams, $vidx, $handle, 0);
}
function reSendSimpleCoverageRequest($vidx, $goAwayerParams,$TaParams,$CovererParams, $startDate, $endDate){
    global $handle;
    $object = new stdClass;
    $object->CovererLastName = $CovererParams['LastName'];
    $object->goAwayerLastName = $TaParams['LastName'];
    $object->startDate = $startDate;
    $object->endDate = $endDate;
    $object->CovererEmail = $CovererParams['Email'];
    echo "<pre> 56  CovererParams : "; print_r($object); echo "</pre>";
    $CovererEmail = new CovererEmail($object, $vidx, $handle);
}
function reSendCompoundCoverage($vidx){
    global $handle, $fp;
    $selStr = "SELECT CovererUserKey FROM MD_TA_Coverage where vidx = $vidx";
    $i = 0;
    $stmt = sqlsrv_query( $handle, $selStr);
    if( $stmt === false ) 
         {$errs = print_r( sqlsrv_errors(), true); fwrite($fp, $errs);}
    else {
        while ($assoc =  sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC)){
            $row[$assoc['CovererUserKey']] = $assoc['CovererUserKey'];
        }
    }  
    foreach ($row as $key=>$val){
        $selStr = "SELECT p.UserKey,  p.Email,p.LastName, u.UserID FROM physicians p
        LEFT JOIN users u
        ON p.UserKey = u.UserKey
        WHERE p.UserKey = $val";
        $stmt = sqlsrv_query($handle, $selStr);
        $row2[$val] = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
    echo "<pre>  79 "; print_r($row2); echo "</pre>";
}




function sendCoverageNotAccepted($StartDate,$CovererLastName, $CovererEmail, $handle){
    $pars[0] = "Hello;";
    $pars[1] = "Dr. ".$CovererLastName ." has not accepted coverage for your Time Away starting on ". $StartDate .".";
    $bHM = new basicHTMLMail($CovererEmail, "Time Away Coverage",$pars, "Coverage for Physician Time Away","CoverageNotAccepted", $handle);
    $bHM->send();
}
function getGoAwayerParamsFromVidx($vidx){
    global $handle, $fp;
    $selStr = "SELECT ta.userkey,ta.vidx, p.UserKey, p.LastName, p.Email
        FROM MDtimeAway ta 
        LEFT JOIN physicians p on ta.userkey = p.UserKey
        WHERE ta.vidx = $vidx";
    $stmt = sqlsrv_query($handle, $selStr);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row;
   // fwrite($fp, $selStr);    
}
function getCoverParams($CovererUserKey){
    global $handle;
    $selStr = "SELECT p.UserKey,  p.Email,p.LastName, u.UserID FROM physicians p
    LEFT JOIN users u
    ON p.UserKey = u.UserKey
    WHERE p.UserKey = $CovererUserKey";
    echo "<br> 56 <br> $selStr";
    $stmt = sqlsrv_query($handle, $selStr);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    return $row;
}
function getTimeAwayParams($vidx){
    global $handle;
    $selStr = "SELECT ta.userkey, ta.coverageA, ta.startDate, ta.endDate, p.LastName, p.Email
    FROM MDtimeAway ta
    LEFT JOIN physicians p on ta.userkey= p.UserKey
    WHERE ta.vidx = $vidx";
    $stmt = sqlsrv_query($handle, $selStr);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row;
}
function getTimeAwayParamsObj($vidx){
    global $handle;
    $selStr = "SELECT ta.userkey, ta.coverageA, ta.startDate, ta.endDate, p.LastName, p.Email
    FROM MDtimeAway ta
    LEFT JOIN physicians p on ta.userkey= p.UserKey
    WHERE ta.vidx = $vidx";
    $stmt = sqlsrv_query($handle, $selStr);
    $row = sqlsrv_fetch_object($stmt);
    $row->goAwayerUserKey = $row->userkey;
    return $row;
}

/**
 * Update OneWeekReminder of TwoWeekReminder -> 1 for vidx
 */  
function updateMDtimeAway($TBDtA, $remColName){
    global $handle, $fp;
    $updateStr = "UPDATE TOP(1) MDtimeAway SET $remColName = 1 WHERE vidx = '".$TBDtA['vidx']."'";
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
echo "<br> numWeeks is ". $numWeeks;    
    $today = $date->format("Y-m-d");
    $Weeks = $date->modify( '+ '.$numWeeks.' weeks' );                          // go ahead $numWeeks weeks
    $WeeksFormat = $Weeks->format('Y-m-d');
    $selStr = "SELECT vidx,userkey,startDate, userid, coverageA, CovAccepted, OneWeekReminder, TwoWeekReminder FROM MDtimeAway WHERE startDate <= '".$WeeksFormat."' 
        AND startDate > '".$today."' AND ".$remColName ." < 1 AND coverageA = 0 AND reasonIdx < 90";
    echo "<br>". $selStr;    
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
    if (isset($row))
        return $row;
}
function sendEmails($TBDtAs){
    global $fp, $debug;
    $link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?vidxToSee=".$TBDtAs['vidx']."&userid=".$TBDtAs['userid']."&TBDtoNom=1";	
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
   // if (!$debug)
    $sendMail->send();	
}
function makeLogFile(){
    $in = 0;
    $today = new DateTime(); $todayString = $today->format('Y-m-d');
    do {																			// put index in case of permission failure
        $fp = @fopen("./Alog/sendReminderLog".$in.".txt", "a+");			
        if ($in++ > 5)
            break;
        }
        while ($fp ===FALSE);
    fwrite($fp, "\r\n $todayString \r\n");    
    return $fp;    
}
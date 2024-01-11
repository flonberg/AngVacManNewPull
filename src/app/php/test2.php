<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./log/test2.txt");
require_once 'H:\inetpub\lib\sqlsrvLibFL_dev_.php';
$handle = connectDB_FL();

$oneWeekTAs = getTAs(1);
foreach ($oneWeekTAs as $key => $val)
    sendEmails($val);

/**
 * Get the tAs 
 */
function getTAs($numWeeks){
    global $handle;
    $date    = new DateTime();                                                  // Creates new DatimeTime for today
    $today = $date->format("Y-m-d");
    $Weeks = $date->modify( '+ '.$numWeeks.' weeks' );                          // go ahead $numWeeks weeks
    $WeeksFormat = $Weeks->format('Y-m-d');
    $selStr = "SELECT vidx,userkey,startDate, userid, coverageA, CovAccepted, OneWeekReminder, TwoWeekReminder FROM MDtimeAway WHERE startDate <= '".$WeeksFormat."' 
        AND startDate > '".$today."' AND OneWeekReminder < 1 AND coverageA = 0 AND reasonIdx < 90";
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
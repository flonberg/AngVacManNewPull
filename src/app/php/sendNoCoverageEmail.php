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
$debug = FALSE; if (isset($_GET['debug'])) $debug = TRUE;
$fp = makeLogFile();
$handle = connectDB_FL();
$handleBB = connectBB();
$debug = $_GET['debug'] == '1' ? true : false;
$dates = makeLast2Weeks();
$covTBDtAs = getNoCovTAs($dates);
foreach($covTBDtAs as $key=>$val){
    sendEmails($val);
    }
exit();

function getNoCovTAs($dates){
    global $handle, $fp;
    $selStr = "SELECT vidx, CovTBDemail, startDate, endDate, userid, coverageA, CovTBDemail FROM MDtimeAway WHERE startDate > '".$dates[1]."' AND startDate < '".$dates[0]."' AND reasonIdx < 9 AND coverageA = '0'";
    $dB = new getDBData($selStr, $handle);
    $i = 0;
    while ($assoc = $dB->getAssoc()){
        $row[$i] = $assoc;                                                          // get the tA
        $selStr2 = "SELECT physicians.Email, physicians.LastName,physicians.UserKey, users.UserID   
            FROM physicians
            LEFT JOIN users on users.UserKey = physicians.UserKey WHERE users.UserID = '".$assoc['userid']."'"; 
        $dB2 = new getDBData( $selStr2, $handle);                                   // get Email, LastName, and UserKey of goAwayer
        $assoc2 = $dB2->getAssoc();
        $result = array_merge($row[$i], $assoc2);
    }
    fwrite($fp, "\r\n Data for email to goAwayer is \r\n");
    $dstr = print_r($result, true); fwrite($fp, $dstr);
    if (!isset($result['CovTBDemail']))                             // check is email has already been sent
        $toWrite = 1;                                               // record that email IS NOW being sent    
    else if ($result['CovTBDemail']== '1')                          // if first email has been sent
        $toWrite = 2;                                               // record that SECOND email has been sent
    $updateStr = "UPDATE TOP(1) MDtimeAway SET CovTBDemail = '".$toWrite."' WHERE vidx = ".$result['vidx'];
    fwrite($fp, "\r\n $updateStr \r\n");
    var_dump($result);
    return $result;
}

function sendEmails($TBDtAs){
    global $fp, $debug;
    $link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?vidxToSee=".$TBDtAs['vidx']."&userid=".$TBDtAs['userid']."&TBDtoNum=1";	
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
function isWithin1Week($date){
    $testDate=date_create();
    date_add($testDate,date_interval_create_from_date_string("1 weeks"));
    $tst = $date > $testDate;
    echo "<br> test <br>". $test->format("Y-m-d"); var_dump($tst);
}
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
    $dstr = print_r($dates, true); fwrite($fp, "\r\n 2 Week dates are \r\n". $dstr);
    return $dates;
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

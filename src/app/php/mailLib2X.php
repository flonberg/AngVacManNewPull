<?php
require_once 'H:\inetpub\lib\esb\_dev_\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./log/enterAngVacError.txt");
$handle = connectDB_FL();
$fp = openLogFile();
$msg = Array("paragraph 1", "paragraph2");
//$test1 = new basicHTMLMail("flonberg@mgh.harvard.edu","Physician Time Away", $msg);
//$test2 = new CovererEmail("Hong", "flonberg@mgh.harvard.edu","Chan",'chan','2024-02-02','2024-02-10', 24);
$selStr = "SELECT vidx, userkey, startDate, endDate, coverageA, physicians.LastName FROM MDtimeAway
    INNER JOIN physicians on MDtimeAway.coverageA=physicians.UserKey WHERE vidx= ".$_GET['vidx'];
    $stmt = sqlsrv_query( $handle, $selStr);
    if( $stmt === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($fp, $dtr);}

$dB = new getDBData($selStr, $handle);
$assoc = $dB->getAssoc();
var_dump($assoc);
$goAwayerAddress = getSingle("SELECT Email FROM physicians WHERE UserKey =".$assoc['userkey'], 'Email', $handle);
$goAwayerAddress = 'flonberg@mgh.harvard.edu';
$ml = new CoverageNotAcceptedEmail( $assoc['startDate']->format('Y-m-d'), $assoc['endtDate']->format('Y-m-d'), $assoc['LastName'],$goAwayerAddress);
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
//$test1->send();
class basicHTMLMail
{
	var $address;
	var $subject;
	var $msg;
	var $headers;
	var $logFp;
	var $message;
    var $title;
	public function __construct($address, $subject, array $msg, $messageType, $title=null){
        $this->openLogFile();
        $this->title = $title;
		$this->address = $address;
		$this->subject = $subject; 
		$this->msg = $msg;
		$this->headers="";
		$this->headers = 'MIME-Version: 1.0' . "\r\n";
		$this->headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	 	$this->headers .= 'From: whiteboard@partners.org' . "\r\n";
	 	$this->headers .= 'Cc: flonberg@mgh.harvard.edu' . "\r\n";
        $this->createMessage();
	}
	private function openLogFile(){
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$fp = fopen("./Alog/MD_VacMan_Mail".$todayStr.".txt", "a+");
		$nowString = $now->format("Y-m-d H:i:s");
		fwrite($this->logFp, "\r\n $nowString");
	}
    public function addHeaders($addr){
        $this->headers.= 'Cc: '. $addr;
    }
    private function createMessage(){
        $this->message = "
		<html>
			<head>";
            if (isset($this->title)){
                $this->message .= "<title> ". $this->title ."</title>";
            }
		    $this->message .="
				<body";
                    foreach ($this->msg as $key => $val){
                        $this->message.="<p>" .$val ."</p>";
                    }    
                    $this->message .= "
                </body>
            </head>	
        </html>";    
    }
    public function send(){
        insStr = "INSERT INTO MD_TimeAwayMail (address,date,messageType) 
                values ($this->address,GETDATE(), $this->messageType)"
        mail($this->address,$this->subject,$this->message, $this->headers);
   }
}
class CovererEmail 
{
    public function __construct($GoAwayerLastName,$covererAddress,$CovererLastName,$CovererUserId,$StartDate,$EndDate,$vidx){
        $pars[0] = "Hello Dr. ". $CovererLastName.";";
        $pars[1] = "Dr. ".$GoAwayerLastName ." is going to be away from ".$StartDate. " to ".$EndDate ." and would like you to cover.";
        $pars[2] = "If you can cover, please click this link to see details of the Time Away and accept the coverage";
        $pars[3] = '<a href="https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?userid='.$CovererUserId.'&vidxToSee='.$vidx.'&acceptor=1" target="_blank">Accept Coverage</a>';
        $bHM = new basicHTMLMail($covererAddress, "Time Away Coverage",$pars, "Coverage for Physician Time Away", "CoverageRequested");
        $bHM->send();
    }   
}
class StaffEmail
{
    public function __construct($GoAwayerLastName, $StartDate, $EndDate, $staff, $vidx){
        $pars[0] = "Greetings;";
        $pars[1] = "Dr. ".$GoAwayerLastName ." is going away from ". $StartDate ." to  ". $EndDate;
        $pars[2] = "To see the details of thie Time Away click on the below link.";
        $pars[3] = '<a href="https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?&vidxToSee='.$vidx.' target="_blank">Accept Coverage</a>';
        $bHM = new basicHTMLMail($staff[0], "Time Away Coverage",$pars, "Coverage for Physician Time Away", "StaffEmail");
        foreach ($staff as $key => $val){

        }
    }
}
class CoverageNotAcceptedEmail
{
    public function __construct($StartDate, $EndDate, $CovererLastName, $CovererEmail){
        $pars[0] = "Hello;";
        $pars[1] = "Dr. ".$CovererLastName ." has not accepted coverage for your Time Away from ". $StartDate ." to  ". $EndDate;
        $bHM = new basicHTMLMail($CovererEmail, "Time Away Coverage",$pars, "Coverage for Physician Time Away","CoverageNotAccepted");
        $bHM->send();
    }
}

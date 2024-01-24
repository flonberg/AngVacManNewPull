<?php
function sendStaffLib( $newTa, $mode){
    global $fp, $handleBB, $handle, $debug;
    $dB = getStaff($newTa['goAwayerUserKey']);
	$i = 0;
	$link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?vidxToSee=".$newTa['vidx'];		
	while ($assoc = $dB->getAssoc()){
		$link = "\n https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?userid=".$assoc['UserID']."&vidxToSee=".$newTa['vidx'];	
	//	$mailAddress = $assoc['Email'];
		$mailAddress = 'flonberg@mgh.harvard.edu';
		$subj = "Time Away for Dr. ". $newTa['goAwayerLastName'];
		$msg = "<p> Hi ".$assoc['FirstName']."<p>";
        if ($mode == 0)
		    $msg.= "<p>Dr. ". $newTa->goAwayerLastName ." is going to be away from ". $newTa->startDate ." through ". $newTa['endDate'] ."</p>";
        if ($mode == 1 || $mode ==2 || $mode == 3){
            $msg.= "<p>With reference to Dr. ". $newTa['goAwayerLastName'] ." Time Away starting ". $newTa['dBstartDate']->format('Y-m-d') ."</p>";
		    if ($mode == 1)
				$msg.= "<p>Dr. ". $newTa['CovererLastName'] ." has accepted coverage. </p>";
			if ($mode == 2)
				$msg.= "<p>Dr. ". $newTa['CovererLastName'] ." has declined coverage. </p>";
			if ($mode == 3)
				$msg.= "The parameters for this Time Away have changed";
        }
		$msg .= "<p> To see details of this Time Away click on the below link. </p>";
		$msg .= '<p>
			<a href='.$link .'>View Time Away. </a>
			</p>';
		$sendMail = new sendMailClassLibLoc($mailAddress,  $subj, $msg, $link);	
		//if (!$debug)
			$sendMail->send();	  
        }
    }
function sendDeleteEmail2($newTa){
	$dB = getStaff($newTa['goAwayerUserKey']);
	while ($assoc = $dB->getAssoc()){	
	//	$mailAddress = $assoc['Email'];
		$mailAddress = 'flonberg@mgh.harvard.edu';
		$link = "";
		$subj = "Time Away for Dr. ". $newTa['goAwayerLastName'];
		$msg = "<p> Hi ".$assoc['FirstName']."<p>";
		$msg.= "<p> Dr. ". $newTa['goAwayerLastName'] ." has canceled their Time Away starting ". $newTa['dBstartDate']->format('Y-m-d') ."</p>";
		$sendMail = new sendMailClassLibLoc($mailAddress,  $subj, $msg, $link);	
		$sendMail->send();	 
	} 
	//$mailAddress = $newTa['CovererEmail'];
	$mailAddress = "flonberg@mgh.harvard.edu";
	//$msg ="<p> Dr. ".$newTa['CovererLastName'] ."</p>";
	$msg = "<p> Dr. ". $newTa['goAwayerLastName'] ." has canceled their Time Away starting on ". $newTa['dBstartDate']->format('Y-m-d') ."</p>";
	$sendMail = new sendMailClassLibLoc($mailAddress,  $subj, $msg, $link);	
	$sendMail->send();	 
}	
function sendToGoAwayer($newTa, $mode){
	global $handle, $fp;
	$choice = 'accepted';
	if ($mode ==2)
		$choice = 'declined';
	$mailAddress = $newTa['goAwayerEmail'];
	$mailAddress = 'flonberg@mgh.harvard.edu';
	$link = "\n https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?userid=".$newTa['userid']."&vidxToSee=".$newTa['vidx'];
	$subj = "Time Away for Dr. ". $newTa['goAwayerLastName'];
	$msg = "<p>Dr. ". $newTa['goAwayerLastName']."</p>";
	$msg.= "<p>Dr. ". $newTa['CovererLastName'] ." has $choice coverage for yourTime Away starting ". $newTa['dBstartDate']->format('Y-m-d') ."</p>";
	$msg .= "<p> To select a new coverer, please use below link. </p>";
	$sendMail = new sendMailClassLibLoc($mailAddress,  $subj, $msg, $link);	
	$sendMail->send();
	fwrite($fp, "\r\n 434343 \r\n");	
}	
function getStaff($goAwayerUserKey){
        global $handle, $fp;
        $selStr = "SELECT * from MD_TimeAway_Staff WHERE MD_UserKey = ". $goAwayerUserKey;
        $dB = new getDBData($selStr, $handle);
        $assoc = $dB->getAssoc();
        $selStr = "SELECT other.FirstName, other.LastName, other.Email, other.UserKey, users.UserID 
        FROM other LEFT JOIN users on other.UserKey=users.UserKey WHERE other.UserKey IN (";
        foreach ($assoc as $key=>$val){                                     
            if ($val > 0)
                $selStr .= " $val,";                        // add staff memeber to selStr
        }
        $selStr = substr($selStr, 0, -1);
        $selStr .= ")";
fwrite($fp, "\r\n 787878 getStaff selStr \r\n". $selStr);		
        $dB = new getDBData($selStr, $handle);
        return $dB;
    }
class sendMailClassLibLoc
{
	var $link;
	var $address;
	var $subject;
	var $msg;
	var $headers;
	var $logFp;
	var $message;
	public function __construct($address, $subject, $msg, $link){
		$this->link = $link;
		$this->address = $address;
		$this->subject = $subject; 
		$this->msg = $msg;
		$this->headers="";
		$this->headers = 'MIME-Version: 1.0' . "\r\n";
		$this->headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	 	$this->headers .= 'From: whiteboard@partners.org' . "\r\n";
	 	$this->headers .= 'Cc: flonberg@partners.org'. "\r\n";
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$this->logFp = fopen("H:\\inetpub\\esblogs\\_dev_\\sendMail".$todayStr.".log", "a+");
		$nowString = $now->format("Y-m-d H:i:s");   fwrite($this->logFp, "\r\n $nowString");
		$this->message = '
		<html>
			<head>
				<title> Physician Time Away </title>
				<body>
					<p>
					'. $msg .'
					</p>';
		if (strlen($link > 5))			
			$this->message .=			
						'<p>
							<a href='.$link .'>View Time Away. </a>
						</p>';
		$this->message .='			
				</body>
			</head>	
		</html>
		'; 
	}
	public function setHeaders($headers){
		$this->headers = $headers;
	}
	public function send(){
		 mail($this->address,$this->subject,$this->message, $this->headers);
	}
	public function setSubject($subject){
		$this->subject = $subject; 
	}
	public function addToHeader($txt){
		$this->headers .= $txt ."\r\n";
	}
}

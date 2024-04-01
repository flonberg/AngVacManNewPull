<?php
class basicHTMLMail
{
	var $address;
	var $subject;
	var $msg;
	var $headers;
	var $logFp;
	var $message;
    var $messageType;
    var $title;
    var $handle;
    var $fp;
	public function __construct($address, $subject, array $msg, $title=null, $messageType, $handle){
        $this->fp = $this->openLogFile();
        $this->title = $title;
	//	$this->address = $address;
    //    if (strpos(getcwd(), 'dev') !== FALSE)
            $this->address = 'flonberg@mgh.harvard.edu';
		$this->subject = $subject; 
        $this->messageType = $messageType;
        $this->handle = $handle;
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
		fwrite($fp, "\r\n $nowString");
        return $fp;
	}
    public function addHeaders($addr){
        $this->headers.= ', flonberg@mgh.harvard.edu';
       // $this->headers.= ','. $addr;
        $str = print_r($this->headers, true); fwrite($this->fp, $str);
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
        fwrite($this->fp, "\r\n address is ".$this->address);
        mail($this->address,$this->subject,$this->message, $this->headers);
        $insStr = "INSERT INTO MD_TimeAwayMail (address, date, messageType) values ('".$this->address."', GETDATE(), '".$this->messageType."')";
        $stmt = sqlsrv_query( $this->handle, $insStr);
      //  echo "<br> $insStr <br>";
        if( $stmt === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($this->fp, $dtr); echo "<br> $dtr <br>";}
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
class StaffEmailClass
{
    var $fp;
    var $data;                                                   // data from MD_TimeAway table
    var $vidx;
    var $handle;
    public function __construct($data,$vidx, $handle){
        $this->data = $data;
        $this->vidx = $vidx;
        $this->handle = $handle;
        $this->fp = $this->openLogFile();
        $dstr = print_r($this->data, true); fwrite($this->fp, "\r\n input data is ". $dstr); fwrite($this->fp, "\r\n vidx is ". $this->vidx);
        $this->composeStaffAddresses();
     /*   $pars[0] = "Greetings;";
        $pars[1] = "Dr. ".$GoAwayerLastName ." is going away from ". $StartDate ." to  ". $EndDate;
        $pars[2] = "To see the details of this Time Away click on the below link.";
        $pars[3] = '<a href="https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?&vidxToSee='.$vidx.' target="_blank">Accept Coverage</a>';
    */
        //  $bHM = new basicHTMLMail($staff[0], "Time Away Coverage",$pars, "Coverage for Physician Time Away", "Staff");

    }
    private function openLogFile(){
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$fp = fopen("./Alog/MD_VacManStaffEmail".$todayStr.".txt", "a+");
		$nowString = $now->format("Y-m-d H:i:s");
		fwrite($fp, "\r\n $nowString");
        return $fp;
	}
    function composeStaffAddresses(){
        $selStr = "SELECT * from MD_TimeAway_Staff WHERE MD_UserKey = ". $this->data>['goAwayerUserKey'];							// get all staff UserKeys for the MD GoAwayer
        $SQL = new SQL($this->handle);
        /*	$dB = new getDBData($selStr, $this->handle);
		$assoc = $dB->getAssoc();                                   // get the data from MD_TimeAwayStaff
        $selStr2 = "SELECT other.FirstName, other.LastName, other.Email, other.UserKey, users.UserID 					
		FROM other LEFT JOIN users on other.UserKey=users.UserKey WHERE other.UserKey IN (";                        // form first part of SELECT Qurey to get LastName and Emails of Staff
        foreach ($assoc as $key=>$val){																						// add the UserKey of each Staff
                if ($val > 0)
                    $selStr2 .= " $val,";
            }
        $selStr2 = substr($selStr2, 0, -1);		$selStr2 .= ")"; 										            // elim the trailing comma and finish the SELECT String
        $staffMembersEmail = Array();
        $dB = new getDBData($selStr2, $this->handle);
        $i = 0;
        $address= Array();
            while ($assoc = $dB->getAssoc()){																					// load address, comma seperated
                if ($i++ == 0)																									// First address
                    $address = $assoc['Email'];
                else
                    $address .= ", ". $assoc['Email'];	
            }	 
            */    
    }
}
class CoverageNotAcceptedEmail
{
    public function __construct($StartDate, $EndDate, $CovererLastName, $CovererEmail, $handle){
        $pars[0] = "Hello;";
        $pars[1] = "Dr. ".$CovererLastName ." has not accepted coverage for your Time Away from ". $StartDate ." to  ". $EndDate;
        $bHM = new basicHTMLMail($CovererEmail, "Time Away Coverage",$pars, "Coverage for Physician Time Away","CoverageNotAccepted", $handle);
        $bHM->send();
    }
}
class SQL {
    var $handle;
    var $fp;
    public function __construct($handle){
        $this->handle = $handle;
    }
    public function getData($selStr, $handle){
        $this->openLogFile();

    }
    private function openLogFile(){
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$fp = fopen("./Alog/SQL_log".$todayStr.".txt", "a+");
		$nowString = $now->format("Y-m-d H:i:s");
		fwrite($fp, "\r\n $nowString");
        return $fp;
	}
}
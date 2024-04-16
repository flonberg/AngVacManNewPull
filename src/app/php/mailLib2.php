<?php
require_once "./SQL_class.php";
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
    var $docidx;
    var $handle;
    var $fp;
	public function __construct($address, $subject, array $msg, $title=null, $messageType, $handle, $docidx = 0){
        $this->fp = $this->openLogFile('Mail2');
        fwrite($this->fp, "\r\n Prod Adresses are ". $address);
        $this->docidx = $docidx;
        $this->title = $title;
		$this->address = $address;                                          
        if (strpos(getcwd(), 'dev') !== FALSE)                            // don't use real addresses if in DEV
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
	public function openLogFile($name){
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$fp = fopen("./Alog/".$name."_".$todayStr.".txt", "a+");
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
        $insStr = "INSERT INTO MD_TimeAwayMail (address, date, messageType, docidx) values ('".$this->address."', GETDATE(), '".$this->messageType."',".$this->docidx.")";
        $stmt = sqlsrv_query( $this->handle, $insStr);
        if( $stmt === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($this->fp, $dtr); echo "<br> $dtr <br>";}
   }
   private function updateMD_TimeAwayChanges($vidx){
        if ($this->subject == 'Time Away Coverage')
            $ColChanged = 'coverageA';
        $updateStr = "UPDATE TOP(4) MD_TimeAwayChanges SET EmailSent WHERE vidx = $vidx AND ColChanged = '".$ColChanged."'";
        $res = sqlsrv_query($this->handle, $updateStr);
        if( $res === false )  {  $dtr =  print_r( sqlsrv_errors(), true); fwrite($this->fp, $dtr);} 
   }
}
class CovererEmail 
{
    public function __construct($newTA,$vidx, $handle){
        $fp = $this->openLogFile();
        ob_start(); var_dump($newTA);$data = ob_get_clean();fwrite($fp, "\r\n 75757\r\n ". $data);
            $pars[0] = "Hello Dr. ". $newTA->CovererLastName.";";
            $pars[1] = "Dr. ".$newTA->goAwayerLastName ." is going to be away from ".$newTA->startDate. " to ".$newTA->endDate ." and would like you to cover.";
            if ($newTA->CompoundCoverage == 1)
                $pars[1] = "Dr. ".$newTA->goAwayerLastName ." is going to be away from ".$newTA->startDate. " to ".$newTA->endDate ." and would like you to cover part of this Time Away";
         //   if ($newTA->WTM_self == 0)
         //       $pars[2] = "You are also being asked to cover WTM for this Time Away";
         //   else
         //       $par[2] = "";
            $pars[3] = "If you can cover, please click this link to see details of the Time Away and accept the coverage";
            $pars[4] = '<a href="https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?userid='.$newTA->CovererUserId.'&vidxToSee='.$vidx.'&acceptor=1" target="_blank">Accept Coverage</a>';
            $prodAddress = $newTA->CovererEmail;
            if (strpos(getcwd(), 'dev') !== FALSE)                            // don't use real addresses if in DEV
                $prodAddress = 'flonberg@mgh.harvard.edu';
            if (strpos($newTA->goAwayerLastName, 'Suit') !== FALSE)          // if it is Test MD send to me
                $prodAddress = 'flonberg@mgh.harvard.edu';    
           // $devAddress = 'flonberg@mgh.harvard.edu';
            $bHM = new basicHTMLMail($prodAddress, "Time Away Coverage",$pars, "Coverage for Physician Time Away", "CoverageRequested", $handle, $vidx);
            $fp = $bHM->openLogFile('AskForCoverage');
            fwrite($fp, "\r\n prodAddress is ". $prodAddress);
            ob_start(); var_dump($newTA);$data = ob_get_clean();fwrite($fp, "\r\n 75757\r\n ". $data);
            $bHM->send();
    }  
    private function openLogFile(){
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$fp = fopen("./Alog/compoundCov".$todayStr.".txt", "a+");
		$nowString = $now->format("Y-m-d H:i:s");
		fwrite($fp, "\r\n $nowString");
        return $fp;
	} 
}
class StaffEmailClass
{
    var $fp;
    var $data;                                                   // data from MD_TimeAway table
    var $vidx;
    var $handle;
    var $SQL;
   
    public function __construct($data,$vidx,$handle, $mode){          // mode=0 => ENTER TA  mode=1 => EDIT TA
        if (!is_object($data))                                          // some function invocations pass ARRAY
            $this->data = json_decode(json_encode($data), FALSE);       // so convert to OBJECT
        else
            $this->data = $data;
        $this->handle = $handle;
        $this->fp = $this->openLogFile();
        $dstr = print_r($this->data, true); fwrite($this->fp, "\r\n input data is ". $dstr);
        fwrite($this->fp, "\r\n mode is $mode \r\n");
        $this->SQL = new SQL($handle);
        if (isset($data->goAwayerUserKey))
            $selStr = "SELECT * from MD_TimeAway_Staff WHERE MD_UserKey = ". $data->goAwayerUserKey;	
        else 
            $selStr = "SELECT * from MD_TimeAway_Staff WHERE MD_UserKey = 258";
        $this->SQL->doSQL($selStr);
        $staffByUserKey = $this->SQL->getAssoc();
        $selStr2 = $this->composeStaffAddresses($staffByUserKey[0]);
        fwrite($this->fp, "\r\n 9898 \r\n ". $selStr2);
        $this->SQL->doSQL($selStr2);
        $staff = $this->SQL->getAssoc();
        $addresses = "";
        foreach ($staff as $key=>$val){                             // build comma seperated list of addresses
            if ($key == 0)
                $addresses = $val['Email'];
            else
                $addresses .= ", ".$val['Email'];
        }
        fwrite($this->fp, "\r\n 109 ProdAddresses are  $addresses \r\n");
        if (strpos(getcwd(), 'dev') !== FALSE)                            // don't use real addresses if in DEV
            $addresses = 'flonberg@mgh.harvard.edu';
        if (strpos($data->goAwayerLastName, 'Suit') !== FALSE)          // if it is Test MD send to me
            $addresses = 'flonberg@mgh.harvard.edu';  
        $pars[0] = "Greetings;";
        $pars[1] = "Dr. ".$data->goAwayerLastName ." is going away from ". $data->startDate ." to  ". $data->endDate;
        if ($mode == 3)
         $pars[1] = "Dr. ".$data->goAwayerLastName ." Time Away from  ". $data->startDate ." to  ". $data->endDate ." has been deleted";
        if ($mode == 1)
            $pars[1]= "Parameters for Dr.  ".$data->goAwayerLastName ." Time Away have changed "; 
        if ($mode == 2)
            $pars[1]= "Coverage for Dr.  ".$data->goAwayerLastName ." Time Away has changed "; 
        $pars[2] = "To see the details of this Time Away click on the below link.";
        $pars[3] = '<a href="https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?&vidxToSee='.$vidx.' target="_blank">See Details</a>';
        if ($mode == 3){
            $pars[2] = "";
            $pars[2] = "";
        }
        ob_start(); var_dump($pars);$data1 = ob_get_clean();fwrite($this->fp, "\r\n   8989 \r\n ". $data1);
        $bHM = new basicHTMLMail($addresses, "Time Away Coverage",$pars, "Coverage for Physician Time Away", "Staff Coverage", $this->handle, $vidx);
        $bHM->send();

    }
    private function openLogFile(){
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$fp = fopen("./Alog/MD_VacManStaffEmail".$todayStr.".txt", "w+");
		$nowString = $now->format("Y-m-d H:i:s");
		fwrite($fp, "\r\n $nowString");
        return $fp;
	}
    function composeStaffAddresses($userKeys){
        ob_start(); var_dump($userKeys);$data = ob_get_clean();fwrite($this->fp, "\r\n 117 \r\n". $data);
        $roles = Array('NP','NP2','Nurse1','Nurse2','PSC','Admin');
        $selStr2 = "SELECT other.FirstName, other.LastName, other.Email, other.UserKey, users.UserID 						
		FROM other LEFT JOIN users on other.UserKey=users.UserKey WHERE other.UserKey IN (";
        foreach ($roles as $key=>$val)
            if ($userKeys[$val] > 0)
                $selStr2 .= $userKeys[$val].",";
        $selStr2 = substr($selStr2, 0, -1);																					// elim the trailing comma
        $selStr2 .= ")";
        return $selStr2;
    }
}
class CoverageNotAcceptedEmail
{
    public function __construct($StartDate,$CovererLastName, $CovererEmail, $handle){
        $pars[0] = "Hello;";
        $pars[1] = "Dr. ".$CovererLastName ." has not accepted coverage for your Time Away starting on ". $StartDate .".";
        $bHM = new basicHTMLMail($CovererEmail, "Time Away Coverage",$pars, "Coverage for Physician Time Away","CoverageNotAccepted", $handle);
        $bHM->send();
    }
}
function sendInsertFailedMail($insStr){ 
    global $handle;  
    $par = Array();
    $par[0] = 'Insert Time Away failed for ';
    $par[1] = $insStr;
    $subject = "Insert Time Away Failed";
    $messageType = "Insert tA failed";
    $bHTMNM = new basicHTMLMail('flonberg@mgh.harvard.edu',$subject, $par, $messageType, $handle, 0);
    $bHTMNM->send();
}


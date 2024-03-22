<?php
$msg = Array("paragraph 1", "paragraph2");
$test1 = new basicHTMLMail("flonberg@mgh.harvard.edu","Physician Time Away", $msg);
$test2 = new CovererEmail("Hong", "flonberg@mgh.harvard.edu","Chan",'chan','2024-02-02','2024-02-10', 24);

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
	public function __construct($address, $subject, array $msg, $title=null){
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
        $bHM = new basicHTMLMail($covererAddress, "Time Away Coverage",$pars, "Coverage for Physician Time Away");
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
        $bHM = new basicHTMLMail($staff[0], "Time Away Coverage",$pars, "Coverage for Physician Time Away");
        foreach ($staff as $key => $val){

        }
    }
}

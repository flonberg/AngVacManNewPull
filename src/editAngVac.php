<?php
require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
$handle = connectDB_FL();
ini_set("error_log", "./Alog/editAngVacError.txt");
$IAP = new InsertAndUpdates();

	$fp = fopen("./Alog/editAngVacLog.txt", "w+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");

 	$body = @file_get_contents('php://input');     	$data = json_decode($body, true);       // Get parameters from calling cURL POST;
	 $s = print_r($data, true);    fwrite($fp, "\r\n data \r\n ");fwrite($fp, $s);
	$data = getNeededParams($data);															// get additional param needed
	

	if (isset($data['accepted']) && $data['accepted'] == 0){								// Coverer has DECLINED coverage
		sendDeclineEmail($data);
		fwrite($fp, "\r\n Send	ing Decline email");
		exit();
	}

	$upDtStr = "UPDATE TOP(1) MDtimeAway SET ";
	if ( isset( $data['startDate'] ) && strlen($data['startDate']) > 2  ){
		$upDtStr .= "startDate = '". $data['startDate']."',";
		$upDtStr .= "CovAccepted = '0',";
		sendTaChangedMail($data);
	}
	if (isset( $data['endDate'] ) &&   strlen($data['endDate']) > 2  ){
		$upDtStr .= "endDate = '". $data['endDate']."',";
		$upDtStr .= "CovAccepted = '0',";
		sendTaChangedMail($data);
	}
	if (isset( $data['reasonIdx'] ) &&   $data['reasonIdx'] >= 1)
		$upDtStr .= "reasonIdx = '". $data['reasonIdx']."',";
	if ( isset( $data['note'] ) &&    strlen($data['note']) > 1)
		$upDtStr .= "note = '". $data['note']."',";
	if (isset( $data['accepted'] )  && strlen($data['accepted']) >= 0)
	{
		$upDtStr .= "CovAccepted = '". $data['accepted']."',";
	//	$int = 0;
		sendAcc($data['vidx']);
		}
	if (isset( $data['WTMdate'] ) &&    strlen($data['WTMdate']) > 0)
		$upDtStr .= "WTMdate = '". $data['WTMdate']."',";
		if (isset( $data['WTM_self'] ))
		$upDtStr .= "WTM_self = '". $data['WTM_self']."',";	
//	$tst = strlen($data['WTMnote']);
       //	fwrite($fp, "\r\n\ strnel is $tst \r\n ");
	if (isset( $data['WTMnote'] ) &&    strlen($data['WTMnote']) > 1)
		$upDtStr .= "WTMnote = '". $data['WTMnote']."',";
	$upDtStr = substr($upDtStr, 0, -1);
	$upDtStr .= " WHERE vidx = ".$data['vidx'];
	fwrite($fp, " 59  ". $upDtStr );
	$IAP->safeSQL( $upDtStr, $handle);
	if (isset($data['reasonIdx']) && $data['reasonIdx']=='99'){
		sendDeleteTaEmail($data);
		exit();
	}		
	sendTaChangedMail($data);
	exit();

	function sendDeleteTaEmail($data){
		global $handle, $fp;
		fwrite($fp, "\r\n vidx is $vidx");
		$s = print_r($data, true);    fwrite($fp, "\r\n 64 data  \r\n ");fwrite($fp, $s);
		$startDateString = $data['dBstartDate']->format('Y-m-d');
		$mailAddress = $data->CovererEmail;								
		$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
		$subj = "Coverage for Time Away";
		$msg =    "Dr.".$data['CovererLastName'].": <br> Dr.". $data['goAwayerLastName'] ." has canceled the Time Away starting on $startDateString for which you were the coverage";
		$message = '
			<html>
				<head>
					<title> Time Away Coverage </title>
						<body>
						<p>'. $msg .'</p>
						</body>
				</head>	
			</html>
				'; 
			$headers = 'MIME-Version: 1.0' . "\r\n";
			   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: <whiteboard@partners.org>' . "\r\n";
			$headers .= 'Cc: flonberg@partners.org'. "\r\n";
			$sendMail = new sendMailClassLib($mailAddress, $subj, $message);	
			$sendMail->setHeaders($headers);	
			$sendMail->send();	

	}
	function getNeededParams($data){
		global $handle, $fp;
		$s = print_r($data, true);   fwrite($fp, "\r\n in getNeededParam data \r\n ");fwrite($fp, $s);
		$selStr = "SELECT userid, WTM_Change_Needed, WTM_self, startDate, endDate, coverageA FROM MDtimeAway WHERE vidx = '".$data['vidx']."'";
		fwrite($fp, "\r\n $selStr \r\n");
		$dB = new getDBData($selStr, $handle);
		$assoc = $dB->getAssoc();
		$data['userid'] = $assoc['userid'];
		$data['dBstartDate'] = $assoc['startDate'];
		$data['goAwayerUserKey'] =  getSingle("SELECT UserKey FROM users WHERE UserID ='". $assoc['userid']."'",  "UserKey", $handle);		
		$data['CovererUserKey'] =  $assoc['coverageA'];		// get name of GoAwayer
		$data['CovererUserId'] =  getSingle("SELECT UserID FROM users WHERE UserKey = ". $assoc['coverageA'],  "UserID", $handle);		
		$data['CovererLastName'] = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$assoc['coverageA']  ."'", "LastName", $handle);			// get name of GoAwayer
		$data['goAwayerLastName'] = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$data['goAwayerUserKey']  ."'", "LastName", $handle);			// get name of GoAwayer
		$data['Email'] = getSingle("SELECT Email FROM physicians WHERE UserKey = '".$assoc['coverageA']  ."'", "Email", $handle);			// get name of GoAwayer

		$s = print_r($data, true);   fwrite($fp, "\r\n end of  getNeededParam data \r\n ");fwrite($fp, $s);
		return $data;
	}
	function sendTaChangedMail($data){
		global $handle, $fp;
		fwrite($fp, "\r\n vidx is $vidx");
		if (is_object($data['dBstartDate']))
			$startDateString = $data['dBstartDate']->format("M-d-Y");
		$link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?userid=".$data['CovererUserId']."&vidxToSee=".$data['vidx'];	// No 8 2021
		fwrite($fp, "\r\n ". $link);
		$mailAddress = $data->CovererEmail;								
		$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
		$subj = "Coverage for Time Away";
		$msg =    "Dr.".$data['CovererLastName'].": <br> Dr.". $data['goAwayerLastName'] ."'s parameters for being away starting on $startDateString have changed. ";
		$msg .= "\r\n <br> To accept or decline this altered coverage click on the below link.";
		$message = '
			<html>
				<head>
					<title> Time Away Coverage </title>
						<body>
							<p>'. $msg .'</p>
							<p>
								<a href='.$link .'> Accept Coverage. </a>
							<p> The above link will NOT work if Internet Explorer is your default browser.  In the case copy the link to use in Chrome </p> 
						</body>
				</head>	
			</html>
				'; 
			$headers = 'MIME-Version: 1.0' . "\r\n";
			   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: <whiteboard@partners.org>' . "\r\n";
			$headers .= 'Cc: flonberg@partners.org'. "\r\n";
			$sendMail = new sendMailClassLib($mailAddress, $subj, $message);	
			$sendMail->setHeaders($headers);	
			$sendMail->send();	
	}

	function sendAcc($vidx){
		global $fp, $handle;
		$selStr = "SELECT startDate, endDate, coverageA,userid FROM MDtimeAway WHERE vidx = $vidx";
		fwrite($fp, "\r\n $selStr \r\n ");
		$dB = new getDBData($selStr, $handle);
		$assoc = $dB->getAssoc();
		$jData = json_encode($assoc);  echo $jData;
		$ss = print_r($assoc, true); fwrite($fp, $ss);
		$int = 0;
	}

	function sendDeclineEmail($data){
		global $handle;
		$toAddress =  getSingle("SELECT Email FROM physicians WHERE UserKey = ".$data['goAwayerUserKeyey'], "Email", $handle);	
		$toAddress = "flonberg@partners.org";					////// changed on 6-24-2016   \\\\\\\\\\\
		$subj = "Coverage for Time Away";
		if (is_object($data['startDate']))
			$startDateString = $data['startDate']->format("Y-m-d");
		$msg = "Dr. ". $data['CovererLastName'] ." has declined coverage for your time-away starting on ". $startDateString;
		$headers = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: <whiteboard@partners.org>' . "\r\n";
		$headers .= 'Cc: flonberg@partners.org'. "\r\n";
		$sendMail = new sendMailClassLib($toAddress,$subj, $msg);	
		$sendMail->setHeaders($headers);	
		$sendMail->send();
	
	}	

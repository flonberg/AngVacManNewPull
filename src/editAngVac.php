<?php
require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
$handle = connectDB_FL();
ini_set("error_log", "./Alog/editAngVacError.txt");
$IAP = new InsertAndUpdates();

	$fp = fopen("./Alog/editAngVacLog.txt", "a+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
	$std = print_r($_GET, true); fwrite($fp, "\r\n GET has \r\n". $std);

 	$body = @file_get_contents('php://input');     	$data = json_decode($body, true);       // Get parameters from calling cURL POST;
	$data = getNeededParams($data);															// get additional param needed
	$std = print_r($data, true); fwrite($fp, "\r\n data is \r\n". $std);
	
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
		sendTaChangedMail($data);
	}
	if (isset( $data['reasonIdx'] ) &&   $data['reasonIdx'] >= 1)
		$upDtStr .= "reasonIdx = '". $data['reasonIdx']."',";
	if ( isset( $data['note'] ) &&    strlen($data['note']) > 1)
		$upDtStr .= "note = '". $data['note']."',";
	if (isset( $data['accepted'] )  && strlen($data['accepted']) >= 0)
	{
		$upDtStr .= "CovAccepted = '". $data['accepted']."',";
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
	fwrite($fp, "\r\n  59  ". $upDtStr );
	$IAP->safeSQL( $upDtStr, $handle);
	if (isset($data['reasonIdx']) && $data['reasonIdx']=='99'){		// This is DELETE request
		if (isset($data['overlapVidx']) && $data['overlapVidx'] > 0){		// there is a Overlap tA
			$updateStr = "UPDATE TOP(1) MDtimeAway SET overlap = 0, overlapVidx = 0 WHERE vidx = '".$data['overlapVidx']."'";
			$IAP->safeSQL($updateStr, $handle);

		}
		sendDeleteTaEmail($data);
		exit();
	}	
	if ($_GET['email'] == 0)									// some edits do NOT require an email. 
		exit();
	elseif ($_GET['email'] == 1)								// tA params changed
			sendTaChangedMail($data);
	elseif ($_GET['email'] == 2)
		sendFinalEmail($data);		
	exit();

	function sendDeleteTaEmail($data){
		global $handle, $fp;
		$startDateString = $data['dBstartDate']->format('Y-m-d');
		$mailAddress = $data->CovererEmail;								
		$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
		$subj = "Time Away Deleted";
	
		$msg =    "Dr.".$data['CovererLastName'].": <br> Dr.". $data['goAwayerLastName'] ." has canceled the Time Away starting on $startDateString for which you were the coverage";
		$message = '
			<html>
				<head>
					<title> Time Away Deleted </title>
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
			$sendMail = new sendMailClassLib($mailAddress,  $subj, $message);	
			$sendMail->send();	
	}
	function getNeededParams($data){
		global $handle, $fp;
		$selStr = "SELECT userid, overlap, overlapVidx, WTM_Change_Needed, WTM_self, startDate, endDate, coverageA FROM MDtimeAway WHERE vidx = '".$data['vidx']."'";
		fwrite($fp, "\r\n $selStr \r\n");
		$dB = new getDBData($selStr, $handle);
		$assoc = $dB->getAssoc();
		$data['userid'] = $assoc['userid'];
		$data['dBstartDate'] = $assoc['startDate'];							//  used to inform the Coverer of the StartDate of the changed tA. 
		$data['goAwayerUserKey'] =  getSingle("SELECT UserKey FROM users WHERE UserID ='". $assoc['userid']."'",  "UserKey", $handle);		
		$data['CovererUserKey'] =  $assoc['coverageA'];		// get name of GoAwayer
		$data['CovererUserId'] =  getSingle("SELECT UserID FROM users WHERE UserKey = ". $assoc['coverageA'],  "UserID", $handle);		
		$data['CovererLastName'] = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$assoc['coverageA']  ."'", "LastName", $handle);			// get name of GoAwayer
		$data['goAwayerLastName'] = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$data['goAwayerUserKey']  ."'", "LastName", $handle);			// get name of GoAwayer
		$data['overlap'] = $assoc['overlap'];
		$data['overlapVidx'] = $assoc['overlapVidx'];
		$dB2 = new getDBData("SELECT adminEmail, adminUserKey, physicianUserKey FROM physicianAdmin WHERE physicianUserKey = ".$data['goAwayerUserKey'], $handle);	
		$admins = $dB2->getAssoc();
		$data['adminEmail'] = $admins['adminEmail'];
		return $data;
	}
	function sendTaChangedMail($data){
		global $handle, $fp;
		fwrite($fp, "\r\n vidx is ". $data['vidx']);
		if (is_object($data['dBstartDate']))
			$startDateString = $data['dBstartDate']->format("M-d-Y");
		$link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?userid=".$data['CovererUserId']."&vidxToSee=".$data['vidx'];	// No 8 2021
		fwrite($fp, "\r\n ". $link);
		$mailAddress = $data->CovererEmail;								
		$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
		//$mailAddress .= ",". $data->CovererEmail;	
		$subj = "Coverage for Time Away";
		$msg =    "Dr.".$data['CovererLastName'].": <br> Dr.". $data['goAwayerLastName'] ."'s parameters for being away starting on $startDateString have changed. ";
		$msg .= "<p> To accept or decline this altered coverage click on the below link.</p>";
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
			$sendMail = new sendMailClassLib($mailAddress, $subj, $message);	
	//		$sendMail->setHeaders($headers);	
			$sendMail->send();	
	}

	function sendFinalEmail($data){
		global $fp, $handle;
		$selStr = "SELECT * FROM MDtimeAway WHERE vidx = '".$data['vidx']."'";
//		fwrite($fp, "\r\n $selStr \r\n ");
		$dB = new getDBData($selStr, $handle);
		$assoc = $dB->getAssoc();
		$merged = array_merge($assoc, $data);
		$std = print_r($merged, true); fwrite($fp, $std);
		$startDateString = $merged['startDate']->format("M-d-Y");
		$endDateString = $merged['endDate']->format("M-d-Y");
		$WTM_dateString = makeDateString($merged['WTMdate']);
		$std = print_r($merged, true); fwrite($fp, "\r\n in sendAcc merged is ". $std);
		//$mailAddress = $assoCovererEmail;								
		$mailAddress = "SLBATCHIS@partners.org";					////// who else?   \\\\\\\\\\\
		$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
		$subj = " Time Away for Dr". $merged['goAwayerLastName'];
		$msg =    "Dr.".$merged['goAwayerLastName']." will be away from  $startDateString to $endDateString. ";
		$msg .= "<p>Dr. ".$merged['CovererLastName']." will be covering. </p>";
		if (isset($merged['note']) && strlen($merged['note'])>  2)
			$msg .="<p> ". $merged['note']."</p>";	
		if (isset($merged['WTMnote']) && strlen($merged['WTMnote'])>  2)
			$msg .="<p>". $merged['WTMnote']."</p>";	
		if ($merged['WTM_Change_Needed'] == '1'){
			$msg .="<p> The WTM date has been changed to ". $merged['WTMdate']."</p>";
		}
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
			$sendMail->send();	
	}
	function makeDateString($date){
		if (is_object($date))
			return $date->format("Y-m-d");
		else
			return $date;	
	}

	function sendDeclineEmail($data){
		global $handle;
		$toAddress =  getSingle("SELECT Email FROM physicians WHERE UserKey = ".$data['goAwayerUserKey'], "Email", $handle);	
		$toAddress = "flonberg@partners.org";					////// changed on 6-24-2016   \\\\\\\\\\\
		$subj = "Coverage for Time Away";
		if (is_object($data['dBstartDate']))
			$startDateString = $data['dBstartDate']->format("Y-m-d");
		$msg = "Dr. ". $data['CovererLastName'] ." has declined coverage for your time-away starting on ". $startDateString;
		$headers = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: <whiteboard@partners.org>' . "\r\n";
		$headers .= 'Cc: flonberg@partners.org'. "\r\n";
		$sendMail = new sendMailClassLib($toAddress,$subj, $msg);	
		$sendMail->setHeaders($headers);	
		$sendMail->send();
	
	}	
/**
 * Check for overlap of existing tA for the given goAwayer, used in enterAngVac.php so should be idential 
 */
function checkOverlap($data){
	global $handle, $fp, $tableName; 
	$ppr = print_r($data, true); fwrite($fp, "\r\n  9191 Checking selfOverlap for ". $ppr);
	$tString = $data->endDate;
	$necParams = array('userid', 'startDate', 'endDate');
	$selStr = "SELECT vidx, userid, startDate, endDate  FROM MDtimeAway WHERE userid = '".$data->userid."' AND reasonIdx < 9 AND (
		( startDate >= '".$data->startDate."' AND  startDate <= '".$data->endDate."')
		OR	( endDate >= '".$data->startDate."' AND  endDate <= '".$data->endDate."')
		OR (   startDate <= '".$data->startDate."' AND  endDate >= '".$data->endDate."'  )
			)";
		
	fwrite($fp, "\r\n 105  selStr is \r\n  $selStr \r\n");
	$dB3 = new getDBData($selStr, $handle);
	$assoc = $dB3->getAssoc();
		ob_start(); var_dump($assoc);$data2 = ob_get_clean();fwrite($fp, "\r\n 108 assoc is \r\n". $data2);
	if (isset($assoc))
		return 1;
	else 
		return 0; 		
}	

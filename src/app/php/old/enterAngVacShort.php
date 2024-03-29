<?php
require_once 'H:\inetpub\lib\esb\_dev_\sqlsrvLibFL.php';
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
ini_set("error_log", "./log/enterAngVacError.txt");
$handle = connectDB_FL();
$handleBB = connectBB();
$IAP = new InsertAndUpdates();
$admins = getAdmins();
$today = date('Y-m-d');
$in = 0;

	exit();

/**
 * Checks for an existing tA in the same service as the  2 B entered tA which overlaps. 
 */
function checkServiceOverLap($data){
	global $handle, $handleBB, $fp, $tableName; 
	$overlap = 0;	$i = 0;		$row = array();								// set up		
	$selStr = "SELECT vidx, userid, startDate, endDate  FROM MDtimeAway WHERE service = '".$data->service."' AND reasonIdx < 9 AND (
		( startDate >= '".$data->startDate."' AND  startDate <= '".$data->endDate."')
		OR	( endDate >= '".$data->startDate."' AND  endDate <= '".$data->endDate."')
		OR (   startDate <= '".$data->startDate."' AND  endDate >= '".$data->endDate."'  )
			)";
	fwrite($fp, "\r\n 84  selStr is \r\n  $selStr \r\n");
	$dB = new getDBData($selStr, $handleBB);
	while ($assoc = $dB->getAssoc()){
		$row[$i] = $assoc;																	// store the overlapping tA
		$row[$i++]['overlapName'] = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$assoc['userkey']."'", "LastName", $handle);	// get LastName of Overlapping tA
	}
	return $row;
}	
/**
 * Check for overlap of existing tA for the given goAwayer
 */
function checkOverlap($data){
		global $handle,$handleBB, $fp, $tableName; 
		$tString = $data->endDate;
		$necParams = array('userid', 'startDate', 'endDate');
		foreach ($necParams as $key => $val){
			if (!isset($data->$val)  && strlen($data->$val < 1)){
				fwrite($fp, "\r\n $key  -- $val datum missing");
				return false;
			}
		}
		$newStartDateTime = new DateTime($data->startDate);		$newEndDateTime = new DateTime($data->endDate);
		$newStartDateString = $data->startDate;  $newEndDateString = $data->endDate;
		$today=date_create(); $todayString =  date_format($today,"Y-m-d ");		
		$today = new DateTime(); $todayString = $today->format('Y-m-d');
		$selStr = "SELECT * FROM $tableName WHERE reasonIdx < 9  AND endDate >+ '$todayString'  AND   userid='".$data->userid."'";	// get users tAs in future
		fwrite($fp, "\r\n $selStr ");
		$dB = new getDBData( $selStr, $handleBB);
		$i = 0;
		while ($assoc = $dB->getAssoc()){													// check each found tA for overlap
			$cmpStartDate = $assoc['startDate']->format('Y-m-d'); 	$cmpEndDate = $assoc['endDate']->format('Y-m-d'); 
			fwrite($fp, "\r\n  Comparing tA startDate = $newStartDateString to be GREATER than  $cmpStartDate and LESS than  $cmpEndDate");
			$tst =  ($newStartDateTime >= $assoc['startDate'] && $newStartDateTime <= $assoc['endDate']); 				
			$tst2 =  ($newEndDateTime >= $assoc['startDate'] && $newEndDateTime <= $assoc['endDate']); 					
			if ($tst || $tst2){
				fwrite($fp, "\r\n New tA ENCLOSED an existing tA OverLap detected so NOT INSERT \r\n ");
				return 1;
			}
		}
		return 0;				// there is NOT an overlap
	}

function sendAskForCoverage($vidx, $data)
{
	global $handle, $fp, $level;
	fwrite($fp, "\r\n vidx is $vidx");
	$link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?userid=".$data->CovererUserId."&vidxToSee=".$vidx;	// No 8 2021
	fwrite($fp, "\r\n ". $link);
	$mailAddress = $data->CovererEmail;								
	$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
	$subj = "Coverage for Time Away";
	$msg =    "Dr.".$data->CovererLastName.": <br> Dr. ". $data->goAwayerLastName ." is going away from ". $data->startDate ." to ". $data->endDate ." and would like you to cover. ";
	if ($data->WTM_self == 0)															// The Coverer is the WTM Coverer
		$msg.="<p> You are also being asked to cover the WTM, so you need to select a WTM date, and perhaps also specify any additional detail concerning WTM coverage. </p>"; 	
	$msg .= "<p> To accept or decline this coverage click on the below link. </p>";
	$message = '
       	<html>
       		<head>
       			 <title> Time Away Coverage </title>
					<body>
					<p>
					'. $msg .'
					</p>
					<p>
					<a href='.$link .'> Accept Coverage. </a>
					<p> The above link will NOT work if Internet Explorer is your default browser.  In the case copy the link to use in Chrome </p> 
				</body>
			</head>	
		</html>
			'; 
		fwrite($fp, "\r\n message sent to sendMailClassLib \r\n". $message);	
		$sendMail = new sendMailClassLib($mailAddress,  $subj, $message);	
		$rData = array("result"=>"pending");
		$jData = json_encode($rData);
		echo $jData;
		$sendMail->send();	
}
/**
 * Finds the specific days of the overlap and sends email announcing them 
 * $oData is the tA which is found to be overlapping. newStartDate and newEndDate or the dates of tA  2B inserted. 
 */
function sendServiceOverlapEmail($oData, $newTa){												// $oData is ARRAY which has DateTimes
	global $handle, $fp, $level;
	$newStartDateDate = new DateTime(($newTa->startDate));										// create PHP DateTime Object
	$newEndDateDate = new DateTime(($newTa->endDate));
	$overLapDays = array();																		// make array to hold overlapDays
	$i = 0;																						// counter
		// Find Overlap Days
		do {
			if ( $newStartDateDate >= $oData['startDate'] && $newStartDateDate <= $oData['endDate'])		// day is IN OldTa
				array_push($overLapDays, $newStartDateDate->format("Y-m-d") );				// push it into array
			$newStartDateDate->modify("+ 1 day");											// go forward 1 day
			if ($i++ > 36)	break;															// safety 
			if ($newStartDateDate > $oData['endDate'])										// if beyond OldTaEndDate
				break;
			}
				while ( $newStartDateDate <= $newEndDateDate );								// until our of NewTa
		$std = print_r($overLapDays, true); fwrite($fp, "\r\n 179 overlapDays is \r\n". $std);										// 
		$count = count($overLapDays); 
		$overLapPhrase = "From ". $overLapDays[0] ." to ". $overLapDays[$count-1];  fwrite($fp, "\r\n overLapPhrease is  $overLapPhrase");
			// get the data for the Email
		$selStr = "SELECT userid, startDate, endDate, userkey, physicians.service, physicians.LastName 			
        	FROM MDtimeAway 
        	INNER JOIN physicians ON MDtimeAway.userkey = physicians.UserKey
        	WHERE MDtimeAway.vidx = '".$oData['vidx']."'";
		$dB = new getDBData($selStr, $handle);
		$assoc = $dB->getAssoc();
		$serviceName = getSingle("SELECT service FROM mdservice WHERE idx = '".$assoc['service']."'", 'service', $handle);
		$goAwayerUserKey = getSingle("SELECT UserKey FROM users WHERE UserID = '".$oData['userid']."'", "UserKey", $handle);
		$overlappererUserKey = getSingle("SELECT UserKey FROM users WHERE UserID = '".$newTa->userid."'", "UserKey", $handle);
		fwrite($fp, "\r\n goAwayerUserKey is ". $goAwayerUserKey);
	//	$dB2 = new getDBData("SELECT adminEmail, adminUserKey, physicianUserKey FROM physicianAdmin WHERE physicianUserKey = ".$goAwayerUserKey, $handle);	
		$selStr = "SELECT adminEmail, adminUserKey, physicianUserKey FROM physicianAdmin WHERE (physicianUserKey = ".$goAwayerUserKey ." OR physicianUserKey = ".$overlappererUserKey.")";	
		fwrite($fp, "\r\n SelStr for OverLapper User Keys is  \r\n".$selStr);
		$dB2 = new getDBData($selStr, $handle);	
		$i = 0; $prodMailAddress = ""; $sentUserKey = 0;									// definde default values
		$prodMailAddress = 'KOH2@mgh.harvard.edu';											// Kevin Oh always gets Service Overlap email
		while ($admins = $dB2->getAssoc()){													// Add the Admins
				if ($admins['adminUserKey'] != $sentUserKey  )								// it is different		
					$prodMailAddress .= ", ". $admins['adminEmail'];						// add the second address
			}
	
		$ppr4 = print_r($prodMailAddress, true); fwrite($fp, "\r\n Prod Overlap Mail Address is ". $ppr4);
		$mailAddress = "flonberg@partners.org";												////// for testing   \\\\\\\\\\\
		if ($level == 'prod')
			$mailAddress = $prodMailAddress;
		$subj = "Two Physicians in $serviceName Away";
		$msg =    "Dr. ".$newTa->goAwayerLastName." and Dr. ". $assoc['LastName'] ." will both be away ". $overLapPhrase;					// The Coverer is the WTM Coverer
		$msg .= "\r\n prod mail address is ". $prodMailAddress; 
		$message = '
			   <html>
				   <head>
						<body>
						<H3> Two Physicians in the '.$serviceName.' service will be away. </H3>
						<p>	'. $msg .'</p>
					</body>
				</head>	
			</html>'; 
			$sendMail = new sendMailClassLib($mailAddress,  $subj, $message);	
			$sendMail->send();	
	//	ob_start(); var_dump($assoc);$data = ob_get_clean();fwrite($fp, "\r\n assoc is \r\n". $data);
}
function sendStaff($vidx, $newTa){
	global $fp, $handleBB, $handle;
	$dstr = print_r($newTa, true); fwrite($fp, "\r\n newTa is \r\n ");fwrite($fp, $dstr);	
	$selStr = "SELECT * from MD_TimeAway_Staff WHERE MD_UserKey = ". $newTa->goAwayerUserKey;
	$dB = new getDBData($selStr, $handleBB);
	$assoc = $dB->getAssoc();
	//SELECT other.FirstName, other.LastName, other.Email, other.UserKey, users.UserID FROM other LEFT JOIN users on other.UserKey = users.UserKey WHERE other.UserKey IN ( 25, 361, 814, 928, 928) 
	$selStr = "SELECT other.FirstName, other.LastName, other.Email, other.UserKey, users.UserID FROM other LEFT JOIN users on other.UserKey=users.UserKey WHERE other.UserKey IN (";
	foreach ($assoc as $key=>$val){
		if ($val > 0)
			$selStr .= " $val,";
	}
	$selStr = substr($selStr, 0, -1);
	$selStr .= ")";
	fwrite($fp, "selsrt 9999  8888is \r\n $selStr \r\n");
	$dB = new getDBData($selStr, $handle);
	$i = 0;
	$link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?vidxToSee=".$vidx;	

	while ($assoc = $dB->getAssoc()){
		$row[$i] = $assoc;
	//	$mailAddress = $assoc['Email'];
		$mailAddress = 'flonberg@mgh.harvard.edu';
		$subj = "Time Away for Dr. ". $newTa->goAwayerLastName;
		$msg = "<p> Hi ".$row[$i++]['FirstName']."<p>";
		$msg.= "<p>Dr. ". $newTa->goAwayerLastName ." is going to be away from ". $newTa->startDate ." through ". $newTa->endDate ."</p>";
		$msg .= "<p> To see details of this Time click on the below link. </p>";
		$message = '
			<html>
				<head>
					<title> Physician Time Away </title>
					<body>
					<p>
					'. $msg .'
					</p>
					<p>
					<a href='.$link .'>View Time Away. </a>
					<p> The above link will NOT work if Internet Explorer is your default browser.  In the case copy the link to use in Chrome </p> 
					</body>
				</head>	
			</html>
			'; 
		fwrite($fp, $msg);	
		$sendMail = new sendMailClassLib($mailAddress,  $subj, $message);	
	//	$sendMail->send();		
	}
	$dstr = print_r($row, true); fwrite($fp, $dstr);
}



function getNeededParams($data){
	global $handle, $fp;
	$userid = isset($data->useridStr) ? $data->useridStr : $data->userid;
	$data->goAwayerUserKey = getSingle("SELECT UserKey FROM users WHERE UserID = '".$userid."'", "UserKey", $handle);			// get name of GoAwayer
	$data->CovererUserId =  getSingle("SELECT UserID FROM users WHERE UserKey = ". $data->coverageA,  "UserID", $handle);			// get name of GoAwayer
	$selStr = "SELECT UserKey, LastName, Email,  service FROM physicians WHERE UserKey = '".$data->goAwayerUserKey ."' OR UserKey ='".$data->coverageA ."'"; 
	fwrite($fp, "\r\n getNeededParams selStr \r\n $selStr");
	$dB = new getDBData($selStr, $handle);
	while ($assoc = $dB->getAssoc()){
		if ($assoc['UserKey'] == $data->goAwayerUserKey){
			$data->goAwayerLastName = $assoc['LastName'];
			$data->service = $assoc['service'];
		}
		if ($assoc['UserKey'] == $data->coverageA){
			$data->CovererLastName = $assoc['LastName'];
			$data->CovererEmail = $assoc['Email'];
		}	
	}
	return $data; 
}

function enterCovsInVacCov($regDuties, $dows, $userkey, $vidx)
{
	global $handle, $fp, $dosimetrist, $IAP;
	$now = date("Y-m-d h:i:s");
   		fwrite($fp, PHP_EOL );fwrite($fp, $now);  fwrite($fp, PHP_EOL ); fwrite($fp, "userkey is $userkey " );
								$dump = print_r($_GET, true);
   								fwrite($fp, PHP_EOL );fwrite($fp, $now);  fwrite($fp, PHP_EOL ); fwrite($fp, "GET is $dump " );
									///////////   this works for both NEW and EDITED  timeAways  \\\\\\\\\\\\\
		foreach ($regDuties as $key=>$val){
			if (is_array($dows)){
			foreach ($dows as $dKey=>$dVal){
				$selStr = "SELECT TOP(1) idx FROM vacCov2 WHERE vidx=".$_GET['isEdit']." AND dutyId=".$val['serviceid'] ." AND covDate = '".$dVal['wholeDate']."'"; 
   				fwrite($fp, PHP_EOL );fwrite($fp, $now);fwrite($fp, $selStr );
				$idxFound = getSingle($selStr, "idx",  $handle);
   				fwrite($fp, PHP_EOL );fwrite($fp, "idxFound is $idxFound");
				if (strlen($idxFound)  < 2){
					$insStr = "INSERT INTO vacCov2 ( covDate, vidx, dutyId, enteredWhen, goAwayerUserKey) values ( '".$dVal['wholeDate']."',$vidx, ".$val['serviceid'].",'$now', $userkey)";
	   				fwrite($fp, PHP_EOL );fwrite($fp, $now);fwrite($fp, $insStr );
					//sqlsrv_query( $handle, $insStr);
					$IAP->safeSQL( $insStr, $handle);
				}
			}
			}
		}
}

function getAdmins(){
	global $handle;
	$selStr = "SELECT adminEmail, adminUserKey, physicianUserKey FROM physicianAdmin";
	$dB = new getDBData($selStr, $handle);
	while ($assoc = $dB->getAssoc())
		$row[$assoc['adminUserKey']] = $assoc;
	return $row;	
}




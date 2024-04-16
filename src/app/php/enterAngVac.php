<?php
require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL.php';
require_once './mailLib2.php';
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./Alog/enterAngVacError.txt");
//header("Access-Control-Allow-Origin: *");	
//$handle = connectDB_FL()	;
if (strpos(getcwd(), 'dev') !== FALSE)  {   
	$level = 'dev';
	$dev = 1;																// creates tAs only visible in DEV
	$writeOrAppend = "w+";
}
else {
	$level = 'prod';	
	$dev = 0;
	$writeOrAppend = "a+";
}
$handle = connectDB_FL();
$handleBB = connectBB();
$realEmails = FALSE;
$debug = $_GET['debug'] == '1' ? true : false;
$IAP = new InsertAndUpdates();
$admins = getAdmins();
$today = date('Y-m-d');
$in = 0;
do {																			// put index in case of permission failure
	$fp = @fopen("./Alog/enterAngVacLog".$today."_".$in.".txt", "w+");			
	if ($in++ > 5)
		break;
		}
		while ($fp ===FALSE);
$today = new DateTime(); $todayString = $today->format("Y-m-d H:i:s"); fwrite($fp, "\r\n $todayString \r\n ");
$tableName = 'MDtimeAway';													// where the data is
//		$tableName = 'MDtimeAway2BB';
$body = @file_get_contents('php://input');            						// Get parameters from calling cURL POST;
$data = json_decode($body);													// get the params from the REST call
$s = print_r($data, true);   fwrite($fp, "\r\n 36 inputData is \r\n". $s);  // Create pretty form of data to log
$ret = array("result"=>"success");											// default response
	/**
	 * Check for overlap with same GoAwayer
	 */
	if (checkOverlap($data) == 1){
		$rArray = array("result"=>0);											// signal for Display Warning Message						
		$ret = array("result"=>"selfOverlap");	echo json_encode($ret);			// generate and encode response
		exit();																	// DO NOTHING ELSE

	}
	// Check is Coverage is Nominated
	if (!isset($data->coverageA)){												// if NO Coverage
		$retArray = array("test"=>"coverageA");									// compose response message array
		$jData = json_encode($retArray); echo $jData;							// retrun response
		exit();																	// DO NOTHING ELSE
	}
	$data = getNeededParams($data);												// get Aux Params for Emails
	$CompoundCovInfo = getCompoundCoverers($data);
	$dstr = print_r($CompoundCovInfo, true); fwrite($fp, "\r\n5757 CompundCovInfo  is \r\n ");fwrite($fp, $dstr);	
	$dstr = print_r($data, true); fwrite($fp, "\r\n 253 Ta with augmented parameters is \r\n ");fwrite($fp, $dstr);	
	$overlap = '0';$overlapVidx = '0';											// default values
	$theOverlap = checkServiceOverLap($data);									// the vidx of the overlapping tA
	/**
	 * Check for overlaps with MDs in SAME SERVICE
	 */
	$countOverlap = count($theOverlap);											// number of overlaps
	if ($countOverlap > 0){														// there IS an overlap
		$overlap = '1';															// set overlap in 2b Entered tA
		$overlapVidx = 	$theOverlap[0]['vidx'];									// set overlapping vidx in 2B entered tA
		sendServiceOverlapEmail($theOverlap[0], $data);							// send Email to People who need to know about overlap
		$updateStr = "UPDATE TOP(1) MDtimeAway SET overlap = '1', overlapVidx = '".$lastVidx  ."' WHERE vidx = '".$overlapVidx."'";	// Update the Existing Overlapping tA
		fwrite($fp, "\r\n updateStr for existing overlapping tA  is \r\n ". $updateStr);
		$IAP->safeSql( $updateStr, $handle);
	}																				
/**
 * INSERT the new timeAway
 */
	$userid = isset($data->useridStr) ? $data->useridStr : $data->userid;
	if (!isset($userid)){
		fwrite($fp, "\r\n No userid \r\n");
		exit();
	}
	$data->note = str_replace("'", "", $data->note);									// remove single quote
	$data->note = htmlspecialchars($data->note, ENT_QUOTES);
	$insStr = " INSERT INTO $tableName (overlapVidx, overlap, userid, service,  userkey, startDate, endDate, reasonIdx, coverageA,  note, WTM_Change_Needed, WTMdate, WTM_self,CovTBDemail,CompoundCoverage,WTM_CovererUserKey,dev, createWhen)
				values(".$overlapVidx.", $overlap,  '$userid','$data->service', '".$data->goAwayerUserKey."','".$data->startDate."', '".$data->endDate."',  ".$data->reasonIdx.",
				'".$data->coverageA."','". $data->note."', '". $data->WTMchange."','". $data->WTMdate."','". $data->WTM_self."' ,'0',$data->CompoundCoverage,$data->WTMcovererUserKey, $dev,getdate()); SELECT @@IDENTITY as id";

	if ($debug) 
		fwrite($fp, "\r\n $insStr");
	$stmt=sqlsrv_query($handle, $insStr);
	if( $stmt === false )  {  
		$dtr =  print_r( sqlsrv_errors(), true); fwrite($fp, $dtr);
		sendInsertFailedMail( $insStr);
		}
	$next_result = sqlsrv_next_result($stmt); 
	$row = sqlsrv_fetch_array($stmt);
	$lastVidx = $row['id'];
	fwrite($fp, "\r\n last vidx is $lastVidx \r\n ");
	if ($data->CompoundCoverage == 1){
		enterCompoundCoverage($data->CoverDays,$lastVidx);
		sendMultiAskForCoverage($lastVidx,$data);
	}
	$selStr = "SELECT *  FROM $tableName WHERE vidx = $lastVidx";
	$dB = new getDBData($selStr, $handle); $newTa = $dB->getAssoc();
	$StaffEmailClass = new StaffEmailClass($data, $lastVidx, $handle, 0);
	if ($data->CompoundCoverage == 0)
		$CovererEmail = new CovererEmail($data, $lastVidx, $handle);

	$res = array("lastVidx"=>$lastVidx); $jD = json_encode($res); echo $jD;
	exit();

function enterCompoundCoverage($data, $vidx){
	global $handle, $fp;
	fwrite($fp, "\r\n 101 \r\n");
	foreach ($data as $key=>$val){
		if (isset($val->CovererUserKey)){
			$insStr = "INSERT INTO MD_TA_Coverage (vidx, CovererUserKey, date,accepted, deleted) values (".$vidx.",'".$val->CovererUserKey."','".$val->date."',0,0)";
			$stmt = sqlsrv_query( $handle, $insStr);
			if( $stmt === false ) {
				$dstr = print_r( sqlsrv_errors(), true); fwrite($fp, "\r\n $dstr \r\n");
					}
				}	
		}
	}	

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
		global $handle,$handleBB, $fp, $tableName, $debug; 
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
		$selStr = "SELECT * FROM $tableName WHERE reasonIdx < 9  AND endDate > '$todayString'  AND   userid='".$data->userid."'";	// get users tAs in future
	//	if ($debug)
	//		fwrite($fp, "\r\n $selStr ");
		$dB = new getDBData( $selStr, $handle);
		$i = 0;
		while ($assoc = $dB->getAssoc()){													// check each found tA for overlap
			$cmpStartDate = $assoc['startDate']->format('Y-m-d'); 	$cmpEndDate = $assoc['endDate']->format('Y-m-d'); 
			if ($debug)
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


function sendMultiAskForCoverage($vidx, $data){
	global $handle, $fp;
	$toSendParams = Array();
	$j = 0;
	foreach ($data->CompoundCoverers as $key => $val) {
		$covUserId = getSingle("SELECT UserID FROM users WHERE UserKey = ".$val, 'UserID', $handle);
		$selStr = "SELECT UserKey, LastName, Email,  service FROM physicians WHERE UserKey = '".$val ."'"; 
		$dB = new getDBData($selStr, $handle);
		$toSendParams[$covUserId] = $dB->getAssoc();										// make param array forEach Coverer
	}
	$dsrt = print_r($toSendParams, true); fwrite($fp, "12345 ". $dsrt);
	foreach ($toSendParams as $key=>$val){
		$data->CovererUserKey = $val['UserKey'];
		$data->CovererEmail = $val['Email'];
		$data->CovererLastName = $val['LastName'];
		$data->CovererUserId = $key;
		$CovererEmail = new CovererEmail($data,$vidx, $handle);	
	}
}

/**
 * Finds the specific days of the overlap and sends email announcing them 
 * $oData is the tA which is found to be overlapping. newStartDate and newEndDate or the dates of tA  2B inserted. 
 */
function sendServiceOverlapEmail($oData, $newTa){												// $oData is ARRAY which has DateTimes
	global $handle, $fp, $level, $debug;
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
		$mailAddress = $prodMailAddress;
		$subj = "Two Physicians in $serviceName Away  --- to ". $prodMailAddress;
		$mailAddress = "flonberg@partners.org";	

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
		if (!$debug)
			$sendMail->send();	
}

function getNomCovLastName($data){
	global $handle;
	$selStr = "SELECT LastName FROM physicians WHERE UserKey = ". $data['CoverageA'];
	$ret =  getSingle($selStr, 'LastName', $handle);
	return $ret;
}
function getSimpleCovData($CovUserKey){
	global $handle;
	$selStr = "SELECT UserKey, LastName, Email,  service FROM physicians 
			WHERE  UserKey = $CovUserKey";
	$dB = new getDBData($selStr, $handle);
	return $assoc = $dB->getAssoc();
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
function getNeededParamsXX($data){
	global $handle, $fp, $debug;
	$userid = isset($data->useridStr) ? $data->useridStr : $data->userid;
	$data->goAwayerUserKey = getSingle("SELECT UserKey FROM users WHERE UserID = '".$userid."'", "UserKey", $handle);			// get name of GoAwayer
	//$data->CovererUserId =  getSingle("SELECT UserID FROM users WHERE UserKey = ". $data->coverageA,  "UserID", $handle);			// get name of GoAwayer
	//$selStr = "SELECT UserKey, LastName, Email,  service FROM physicians WHERE UserKey = '".$data->goAwayerUserKey ."' OR UserKey ='".$data->coverageA ."'"; 
	$selStr = "SELECT UserKey, LastName, Email,  service FROM physicians WHERE UserKey = '".$data->goAwayerUserKey ."'"; 
fwrite($fp, "348348 ". $selStr);	
		$dB = new getDBData($selStr, $handle);
			while ($assoc = $dB->getAssoc()){
					$data->goAwayerLastName = $assoc['LastName'];
					$data->service = $assoc['service'];
				}
	return $data; 
}

function getCompoundCoverers($data){
	global $handle, $fp;
	$CompoundCovInfo = Array();
	$CompoundCovUserKeys = Array();
	$ind = 0;
	foreach ($data->CoverDays as $key=>$val){
		if (!in_array($val->CovererUserKey, $CompoundCovUserKeys))
			$CompoundCovUserKeys[$ind++]= $val->CovererUserKey;
	}
	$selStr = "SELECT UserKey, LastName, Email,  service FROM physicians 
			WHERE  UserKey IN (" . implode(',', $CompoundCovUserKeys) . ")";
	$dB = new getDBData($selStr, $handle);
	$i = 0;
	while ($assoc = $dB->getAssoc()){
		$CompoundCovInfo[$i++] = $assoc;
	}
	return $CompoundCovInfo	;	
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




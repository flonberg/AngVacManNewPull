	
<?php


require_once 'H:\inetpub\lib\esb\_dev_\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
//header("Access-Control-Allow-Origin: *");	
$handle = connectDB_FL();
ini_set("error_log", "./Alog/enterAngVacError.txt");

$IAP = new InsertAndUpdates();
$in = 0;
do {
$fp = fopen("./Alog/enterAngVacLog".$in.".txt", "w+");
$in++;
}
while ($fp ===FALSE);
$today = new DateTime(); $todayString = $today->format("Y-m-d H:i:s"); fwrite($fp, "\r\n $todayString \r\n ");
	$tableName = 'MDtimeAway';
 $body = @file_get_contents('php://input');            // Get parameters from calling cURL POST;
	$data = json_decode($body);
                                    	// Write out the data to the log
	$s = print_r($data, true);                              	// Create pretty form of data
        fwrite($fp, $s);                                     	// Write out the data to the log	

	$ret = array("result"=>"success");
	$tst3 =  checkOverlap($data);
	if ($tst3 == 1){
		$rArray = array("result"=>0);
		$rData = json_encode($rArray);
		echo $rData;
		fwrite($fp, "\r\n overlap ". $rData ." \r\n");
		exit();
	}
	$data = getNeededParams($data);

	$s = print_r($data, true);      fwrite($fp, $s); 
	if (!isset($data->coverageA)){
		$retArray = array("test"=>"coverageA");
		fwrite($fp, "\r\n  32 ffff \r\n");
		$jData = json_encode($retArray);
		echo $jData;
		exit();
	}

	$theOverlap = checkServiceOverLap($data);	

	$insStr = "INSERT INTO $tableName (overlapVidx, userid, service,  userkey, startDate, endDate, reasonIdx, coverageA,  note, WTM_Change_Needed, WTMdate, WTM_self, createWhen)
				values($theOverlap, '$data->userid','$data->service', '".$data->goAwayerUserKey."','".$data->startDate."', '".$data->endDate."',  ".$data->reasonIdx.",'".$data->coverageA."','". $data->note."', '". $data->WTMchange."','". $data->WTMdate."','". $data->WTM_self."' , getdate()); SELECT SCOPE_IDENTITY()";
	
	fwrite($fp, "\r\n $insStr");
	$res = $IAP->safeSQL($insStr, $handle);
	$selStr = "SELECT vidx FROM $tableName WHERE vidx = SCOPE_IDENTITY()";					// get the vidx of last inserted record
	$lastVidx = getSingle($selStr, 'vidx', $handle);
	fwrite($fp, "\r\n last vidx is $lastVidx \r\n ");
	$selStr = "SELECT UserKey FROM users WHERE UserID = '". $data->userid."'";					// get the UserKey of the GoAwayer
	fwrite($fp, "\r\n $selStr \r\n ");
	$goAwayerUserKey = getSingle($selStr, 'UserKey', $handle);
	fwrite($fp, "\r\n goAwayerUserKey is $goAwayerUserKey \r\n ");
	if (isset($data->coverageA) && $data->coverageA > 0)
		sendAskForCoverage($lastVidx,  $data);

	$res = array("result"=>"Success"); $jD = json_encode($res); echo $jD;
	exit();
/*
	function getLastId() {
		$result = mssql_fetch_assoc(mssql_query("select @@IDENTITY as id"));
		return $result['id'];
	}	
*/

function checkServiceOverLap($data){
	global $handle, $fp, $tableName; 
	$overlap = 0;
	$selStr = "SELECT service,vidx, startDate, endDate, userkey, userid FROM $tableName WHERE service = '".$data->service."' AND reasonIdx < 9
			AND (
				(startDate >= '".$data->startDate."' AND startDate <= '".$data->endDate."' OR  endDate >= '".$data->startDate."' AND startDate <= '".$data->endDate."') 
			OR	(startDate < '".$data->startDate."' AND endDate > '".$data->endDate."' ) 
				)";
	fwrite($fp, "\r\n 69  selStr is \r\n  $selStr \r\n");
	$dB = new getDBData($selStr, $handle);
	$i = 0;		
	$row = array();																				// index for the returned array of overlap tA. 
	while ($assoc = $dB->getAssoc()){
		$overlap = 1;
		fwrite($fp, "\r\n vidx founr is ". $assoc['vidx']);
		$row[$i] = $assoc;																	// store the overlapping tA
	fwrite($fp, "\r\n assoc found is "); $std = print_r($assoc, true); fwrite($fp, $std);
	//	ob_start(); var_dump($assoc);$data = ob_get_clean();fwrite($fp, $data);
		$row[$i++]['overlapName'] = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$assoc['userkey']."'", "LastName", $handle);	// get LastName of Overlapping tA
		return $assoc['vidx'];
	}
	fwrite($fp, "\r\n tAs found are "); $std = print_r($row, true); fwrite($fp, $std);
	fwrite($fp, "\r\n 92 overlap is $overlap \r\n");
	return 0;

}	

function checkOverlap($data){
		global $handle, $fp, $tableName; 
		$tString = $data->endDate;
		$necParams = array('userid', 'startDate', 'endDate');
		foreach ($necParams as $key => $val){
			if (!isset($data->$val)  && strlen($data->$val < 1)){
				fwrite($fp, "\r\n $key  -- $val datum missing");
				return false;
			//	return true;
			}
		}
		$newStartDateTime = new DateTime($data->startDate);		$newEndDateTime = new DateTime($data->endDate);
		$newStartDateString = $data->startDate;  $newEndDateString = $data->endDate;
		$today=date_create(); $todayString =  date_format($today,"Y-m-d ");		
		$today = new DateTime(); $todayString = $today->format('Y-m-d');
		$selStr = "SELECT * FROM $tableName WHERE reasonIdx < 9  AND endDate > '$todayString'  AND   userid='".$data->userid."'";
		fwrite($fp, "\r\n $selStr ");
		$dB = new getDBData( $selStr, $handle);
		ob_start(); var_dump($dB);$data = ob_get_clean();fwrite($fp, $data);
		while ($assoc = $dB->getAssoc()){
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
	global $handle, $fp;
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
		$headers = 'MIME-Version: 1.0' . "\r\n";
       	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: <whiteboard@partners.org>' . "\r\n";
        $headers .= 'Cc: flonberg@partners.org'. "\r\n";
		$sendMail = new sendMailClassLib($mailAddress,  $subj, $message);	
		$sendMail->setHeaders($headers);	
		$rData = array("result"=>"pending");
		$jData = json_encode($rData);
		echo $jData;
		$sendMail->send();	
		exit();
}
function getNeededParams1($data){
	global $handle;
	$data->goAwayerUserKey = getSingle("SELECT UserKey FROM users WHERE UserID = '".$data->userid."'", "UserKey", $handle);			// get name of GoAwayer
	$data->CovererUserId =  getSingle("SELECT UserID FROM users WHERE UserKey = ". $data->coverageA,  "UserID", $handle);			// get name of GoAwayer
	//$data->goAwayerLastName = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$data->goAwayerUserKey ."'", "LastName", $handle);			// get name of GoAwayer
	//$data->CovererLastName=  getSingle("SELECT LastName FROM physicians WHERE UserKey = ".$data->coverageA, "LastName", $handle);			// get name of GoAwayer
	//$data->CovererEmail =  getSingle("SELECT Email FROM physicians WHERE UserKey = ".$data->coverageA, "Email", $handle);			// get name of GoAwayer
	return $data;
}
function getNeededParams($data){
	global $handle;
	$data->goAwayerUserKey = getSingle("SELECT UserKey FROM users WHERE UserID = '".$data->userid."'", "UserKey", $handle);			// get name of GoAwayer
	$data->CovererUserId =  getSingle("SELECT UserID FROM users WHERE UserKey = ". $data->coverageA,  "UserID", $handle);			// get name of GoAwayer
	$selStr = "SELECT UserKey, LastName, Email,  service FROM physicians WHERE UserKey = '".$data->goAwayerUserKey ."' OR UserKey ='".$data->coverageA ."'"; 
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
					$IAP->lfsafeSQL( $insStr, $handle);
				}
			}
			}
		}
}
/*
function safeSQL($insStr, $handle){
	global $fp; 
	try { 
		$res = sqlsrv_query($handle, $insStr);
	} catch(Exception $e) {
		error_log( "Exception is ". $e);
	}
	if ($res === FALSE){
		fwrite($fp, 'MSSQL error: for '. $insStr );
	}
	else 
		fwrite($fp, "\r\n $insStr");
}
*/


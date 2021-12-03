
<?php

require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
//header("Access-Control-Allow-Origin: *");	
$handle = connectDB_FL();

	$tableName = 'MDtimeAway';
 $body = @file_get_contents('php://input');            // Get parameters from calling cURL POST;
	$data = json_decode($body);
	$s = print_r($data, true);                              	// Create pretty form of data
	$fp = fopen("./Alog/enterAngVacLog2.txt", "w+");
        fwrite($fp, $s);                                     	// Write out the data to the log
	$ret = array("result"=>"success");
	$tst3 =  checkOverlap($data);
	ob_start(); var_dump($tst3);$tData = ob_get_clean();fwrite($fp, "\r\n test3 is ".  $tData);
	if ($tst3){
		$rArray = array("result"=>0);
		$rData = json_encode($rArray);
		echo $rData;
		fwrite($fp, "\r\n overlap ". $rData ." \r\n");
		exit();
	}
	$rArray = array("result"=>1);
	$rData = json_encode($rArray);
	$insStr = "INSERT INTO $tableName (userid, startDate, endDate, reasonIdx, coverageA,  note, WTM_Change_Needed, createWhen)
				values('$data->userid', '".$data->startDate."','".$data->endDate."',".$data->reason.",'".$data->coverageA."','". $data->note."', '". $data->WTMchange."', getdate())";
	
	fwrite($fp, "\r\n $insStr");
	$res = sqlsrv_query($handle, $insStr);
	$selStr = "SELECT vidx FROM $tableName WHERE vidx = SCOPE_IDENTITY()";					// get the vidx of last inserted record
	$lastVidx = getSingle($selStr, 'vidx', $handle);
	fwrite($fp, "\r\n last vidx is $lastVidx \r\n ");
	$selStr = "SELECT UserKey FROM users WHERE UserID = '". $data->userid."'";
	fwrite($fp, "\r\n $selStr \r\n ");
	$goAwayerUserKey = getSingle($selStr, 'UserKey', $handle);
	fwrite($fp, "\r\n goAwayerUserKey is $goAwayerUserKey \r\n ");
	
	sendAskForCoverage($lastVidx, $goAwayerUserKey, $data->coverageA,  $data->startDate, $data->endDate, $data);
	echo $rData;
		
	exit();

function checkOverlap($data){
		global $handle, $fp, $tableName; 
		$newStartDateTime = new DateTime($data->startDate);
		$newEndDateTime = new DateTime($data->endDate);
		$selStr = "SELECT * FROM $tableName WHERE reasonIdx < 9 AND  userid='".$data->userid."'";
		fwrite($fp, "\r\n $selStr ");
		$dB = new getDBData( $selStr, $handle);
		ob_start(); var_dump($dB);$data = ob_get_clean();fwrite($fp, $data);
		while ($assoc = $dB->getAssoc()){
//			$s = print_r($assoc, true); 
//			fwrite($fp, $s);	
			$tst =  ($newStartDateTime <= $assoc['startDate'] && $newEndDateTime >= $assoc['endDate']); // if New tA ENCLOSES an existing tA
			fwrite($fp, "\r\n test if \r\n ");
			ob_start(); var_dump($tst);$data = ob_get_clean();fwrite($fp, $data);
			if ($tst){
				fwrite($fp, "\r\n New tA ENCLOSED an existing tA OverLap detected so NOT INSERT \r\n ");
				return true;
			}

			$tst2 =  ($newEndDateTime >= $assoc['startDate'] && $newEndDateTime <= $assoc['endDate']); // if New starts or ends INSIDE an existing tA
			fwrite($fp, "\r\n test if \r\n ");
			ob_start(); var_dump($tst2);$data = ob_get_clean();fwrite($fp, $data);
			fflush($fp);
			if ($tst2){
				fwrite($fp, "\r\n new tA ended INSIDE an existing tA so  OverLap detected so NOT INSERT \r\n ");
				return true;		// there IS an overlap
			}
		}
		return false;				// there is NOT an overlap
	}
function sendAskForCoverage($vidx, $userkey, $covererUserKey, $startDate, $endDate, $data)
{
	global $handle, $fp;
	fwrite($fp, "\r\n vidx is $vidx");
	$selStr = "SELECT LastName FROM physicists WHERE UserKey = $userkey";			// get name of GoAwayer
	$goAwayerLastName = getSingle("SELECT LastName FROM physicians WHERE UserKey = $userkey", "LastName", $handle);			// get name of GoAwayer
	$CovererLastName =  getSingle("SELECT LastName FROM physicians WHERE UserKey = ".$data->coverageA, "LastName", $handle);			// get name of GoAwayer
	$CovererUserId =  getSingle("SELECT UserID FROM users WHERE UserKey = ". $data->coverageA,  "UserID", $handle);			// get name of GoAwayer
	$link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?userid=".$CovererUserId."&vidxToSee=".$vidx;	// No 8 2021
	fwrite($fp, "\r\n ". $link);
	$mailAddress = "flonberg@partners.org";					////// changed on 6-24-2016   \\\\\\\\\\\
	$subj = "Coverage for Time Away";
	$msg =    "Dr. $CovererLastName: <br> Dr. $goAwayerLastName is going away from ". $data->startDate ." to ". $data->endDate ." and would like you to cover. ";
	$msg .= "\r\n <br> To accept or decline this coverage, and to select a WTM date click on the below link.";
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
			</html>
			'; 

	$headers = 'MIME-Version: 1.0' . "\r\n";
       	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
       	$headers .= 'From: Whiteboard'. "\r\n";
        $headers .= 'Cc: flonberg@partners.org'. "\r\n";
       	$res = mail ( $mailAddress, $subj, $message, $headers);
	fwrite($rp, "\r\n $message \r\n ");
   	ob_start(); var_dump($res); $data = ob_get_clean(); fwrite($rp, $data);
}
function enterCovsInVacCov($regDuties, $dows, $userkey, $vidx)
{
	global $handle, $fp, $dosimetrist;
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
					sqlsrv_query( $handle, $insStr);
				}
			}
			}
		}
}

<?php

require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
header("Content-Type: application/json; charset=UTF-8");
$handle = connectDB_FL();
	$test = 1;

	$fp = fopen("./Alog/editAngVacLog.txt", "w+");
	$todayString =  date('Y-m-d H:i:s');
	fwrite($fp, "\r\n $todayString");
 	$body = @file_get_contents('php://input');            // Get parameters from calling cURL POST;
	$data = json_decode($body, true);
	$s = print_r($data, true);                              	// Create pretty form of data
	fwrite($fp, "\r\n data \r\n ");
	fwrite($fp, $s);
	$upDtStr = "UPDATE TOP(1) MDtimeAway SET ";
	if (strlen($data['startDate']) > 2  )
		$upDtStr .= "startDate = '". $data['startDate']."',";
	if (strlen($data['endDate']) > 2  )
		$upDtStr .= "endDate = '". $data['endDate']."',";
	if ($data['reasonIdx'] >= 1)
		$upDtStr .= "reasonIdx = '". $data['reasonIdx']."',";
	if (strlen($data['note']) > 1)
		$upDtStr .= "note = '". $data['note']."',";
	if (strlen($data['CovAccepted']) >= 0)
	{
		$upDtStr .= "CovAccepted = '". $data['CovAccepted']."',";
		$int = 0;
		sendAcc($data['vidx']);
		}
	if (strlen($data['WTMdate']) > 0)
		$upDtStr .= "WTMdate = '". $data['WTMdate']."',";
//	$tst = strlen($data['WTMnote']);
       //	fwrite($fp, "\r\n\ strnel is $tst \r\n ");
	if (strlen($data['WTMnote']) > 1)
		$upDtStr .= "WTMnote = '". $data['WTMnote']."',";
	$upDtStr = substr($upDtStr, 0, -1);
	$upDtStr .= " WHERE vidx = ".$data['vidx'];
	if (strlen($data['startDate']) > 2 && strlen($data['endDate']) > 2 )
		$updateStr = "UPDATE TOP(1) MDtimeAway SET startDate = '".$data['startDate']."', endDate = '".$data['endDate']."' WHERE vidx = ".$data['vidx']; 
	if (strlen($data['startDate']) > 2  )
		$updateStr = "UPDATE TOP(1) MDtimeAway SET startDate = '".$data['startDate']."' WHERE vidx = ".$data['vidx']; 
	if (strlen($data['endDate']) > 2  )
		$updateStr = "UPDATE TOP(1) MDtimeAway SET endDate = '".$data['endDate']."' WHERE vidx = ".$data['vidx']; 
		$updateStr = "UPDATE TOP(1) MDtimeAway SET endDate = '".$data['endDate']."' WHERE vidx = ".$data['vidx']; 
	fwrite($fp, $upDtStr );
	$res = sqlsrv_query($handle, $upDtStr);
	exit();

	function sendAcc($vidx){
		global $fp, $handle;
		$selStr = "SELECT startDate, endDate, coverageA, userid FROM MDtimeAway WHERE vidx = $vidx";
		fwrite($fp, "\r\n $selStr \r\n ");
		$dB = new getDBData($selStr, $handle);
		$assoc = $dB->getAssoc();
		$jData = json_encode($assoc);  echo $jData;
		$ss = print_r($assoc, true); fwrite($fp, $ss);
		$selStr = "SELECT LastName from physicians WHERE UserKey = ".$assoc['coverageA'];
		fwrite($fp, "\r\n $selStr \r\n ");
		$covLastName = getSingle($selStr, "LastName", $handle);
		$selStr = "SELECT UserKey from users WHERE UserID = '".$assoc['userid']."'";
		fwrite($fp, "\r\n $selStr \r\n ");
		$goAwayerUserID = getSingle($selStr, "UserKey", $handle);
		fwrite($fp, "\r\n goAwayerUserID is $goAwayerUserID");
		$int = 0;
	fwrite($fp, "\r\n ". $link);
	$mailAddress = "flonberg@partners.org";					////// changed on 6-24-2016   \\\\\\\\\\\
	$subj = "Coverage for Time Away";
	$msg =    "Dr. $CovLastName: <br> Dr. $goAwayerLastName is going away from ". $data->startDate ." to ". $data->endDate ." and would like you to cover. ";
	$msg .= "\r\n <br> To accept or decline this coverage, and to select a WTM date click on the below link.";
	$mgs .= "\r\n <br> This will NOT work with Internet Explorer as you default browser.";
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
			<p> The above link will NOT work if Internet Explorer is you default browser.  In the case copy the link to use in Chrome </p> 
			</body>
			</html>
			'; 

	$headers = 'MIME-Version: 1.0' . "\r\n";
       	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
       	$headers .= 'From: Whiteboard'. "\r\n";
        $headers .= 'Cc: flonberg@partners.org'. "\r\n";
  //     	$res = mail ( $mailAddress, $subj, $message, $headers);

	}

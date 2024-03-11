<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
require_once('workdays.inc');
require_once('dosimetristList.php');
include('isHollidayLib.php');
ini_set("error_log", "./log/getVidxError.txt");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

$handle = connectDB_FL();
	$tableName = 'MDtimeAway';
	$fp = fopen("./Alog/getVidxToSeeLog.txt", "w+");
	$todayString =  date('Y-m-d H:i:s');
	fwrite($fp, "\r\n $todayString");
	fwrite($fp, "\r\n vidxToSee is  ". $_GET['vidxToSee']);
	$selStr = "SELECT * FROM $tableName WHERE vidx = ".$_GET['vidxToSee'];
	$dB = new getDBData($selStr, $handle);
	$assoc = $dB->getAssoc();
$dstr = print_r($assoc, true); fwrite($fp, $dstr);	

	$selStr2 = "SELECT idx,vidx,CovererUserKey,date,deleted, accepted FROM MD_TA_Coverage WHERE vidx = ".$assoc['vidx'];
	fwrite($fp, "\r\n $selStr2");
	$dB2 = new getDBData($selStr2, $handle);
	$ind2 = 0;
	while ($assoc2 = $dB2->getAssoc())
		$assoc['Coverage'][$ind2++]= $assoc2;
	$assoc['startDate'] = formatDate($assoc['startDate']);
	$assoc['startDate_m-d-y'] = formatDate($assoc['startDate']);
	$assoc['endDate'] = formatDate($assoc['endDate']);
	//$assoc['startDateConvent'] = formatDate($assoc['startDate']);
	$assoc['WTMnote'] = $assoc['WTMnote'];
	if (isset($assoc['WTMdate'])){
		if (is_object($assoc['WTMdate'])){
			$dateString = formatDate($assoc['WTMdate']);
		if (@strpos($dateString, '1900') === FALSE)
			$assoc['WTMdate'] = $dateString;
		else {
			$assoc['WTMdate'] = "";
		}
		}
	}
	$selStr = "SELECT UserKey from users WHERE UserID = '".$_GET['userid']."'";
	$assoc['loggedInUserKey'] = getSingle($selStr, 'UserKey', $handle);
	$selStr = "SELECT UserKey from users WHERE UserID = '".$assoc['userid']."'";
	$assoc['goAwayerUserKey'] = getSingle($selStr, 'UserKey', $handle);
	$selStr = "SELECT LastName from physicians WHERE UserKey = '".$assoc['goAwayerUserKey']."'";
	$assoc['goAwayerLastName'] = getSingle($selStr, 'LastName', $handle);
	if ($assoc['coverageA'] > 0){
		$selStr = "SELECT LastName from physicians WHERE UserKey = '".$assoc['coverageA']."'";
		$assoc['CovererLastName'] = getSingle($selStr, 'LastName', $handle);
		$assoc['covererDetails']['LastName'] =$assoc['CovererLastName'] ;
		if ($assoc['coverageA'])
			if (  intval($assoc['loggedInUserKey']) == intval($assoc['coverageA']))
				$assoc['IsUserCoverer'] = true;
	}
	else if ($assoc['coverageA'] == 0){
		$assoc['CovererLastName'] = 'TBD';
	}
	$ss = print_r($assoc, true); fwrite($fp,"\r\n5555 \r\n"); fwrite($fp, $ss);
	$jData = json_encode($assoc);
	echo $jData;
	exit();
	function formatDate($dt){
		global $fp;
		if (is_object($dt))
			try  {
				return $dt->format('Y-m-d');
			}
			catch(Exception $e){
			fwrite($fp,  $e->getMessage());
		}
		else
			return $dt;
	}

<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
require_once('workdays.inc');
require_once('dosimetristList.php');
include('isHollidayLib.php');
$handle = connectDB_FL();
	$tableName = 'MDtimeAway';
	$fp = fopen("./Alog/getVidxToSeeLog.txt", "w+");
	$todayString =  date('Y-m-d H:i:s');
	fwrite($fp, "\r\n $todayString");
	fwrite($fp, "\r\n vidxToSee is  ". $_GET['vidxToSee']);
	$selStr = "SELECT * FROM $tableName WHERE vidx = ".$_GET['vidxToSee'];
	$dB = new getDBData($selStr, $handle);
	$assoc = $dB->getAssoc();
	$assoc['startDateConvent'] = formatDate($assoc['startDate']);
	$assoc['endDateConvent'] = formatDate($assoc['endDate']);
	$assoc['startDateConvent'] = formatDate($assoc['startDate']);
	$assoc['WTMnote'] = $assoc['WTMnote'];
	if ($assoc['WTMdate'])
		$assoc['WTMDateConvent'] = formatDate($assoc['WTMdate']);
	$selStr = "SELECT UserKey from users WHERE UserID = '".$_GET['userid']."'";
	$assoc['loggedInUserKey'] = getSingle($selStr, 'UserKey', $handle);
	$selStr = "SELECT UserKey from users WHERE UserID = '".$assoc['userid']."'";
	$assoc['goAwayerUserKey'] = getSingle($selStr, 'UserKey', $handle);
	$selStr = "SELECT LastName from physicians WHERE UserKey = '".$assoc['goAwayerUserKey']."'";
	$assoc['goAwayerLastName'] = getSingle($selStr, 'LastName', $handle);
	$selStr = "SELECT LastName from physicians WHERE UserKey = '".$assoc['coverageA']."'";
		$assoc['CovererLastName'] = getSingle($selStr, 'LastName', $handle);
	if (intval($assoc['loggedInUserKey']) == intval($assoc['CoverageA']))
		$assoc['IsUserCoverer'] = true;
	$ss = print_r($assoc, true); fwrite($fp, $ss);
	$jData = json_encode($assoc);
	echo $jData;
	exit();

	function formatDate($dt){
		try  {
			return $dt->format('m-d-Y');
		}
		catch(Exception $e){
		//	echo $e->getMessage();
		}
	}

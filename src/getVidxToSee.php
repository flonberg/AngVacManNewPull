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
	$assoc['startDate'] = formatDate($assoc['startDate']);
	$assoc['startDate_m-d-y'] = formatDate($assoc['startDate']);
	$assoc['endDate'] = formatDate($assoc['endDate']);
	//$assoc['startDateConvent'] = formatDate($assoc['startDate']);
	$assoc['WTMnote'] = $assoc['WTMnote'];
	if ($assoc['WTMdate'])
		if (strpos($assoc['WTMdate'], '1900') === FALSE)
			$assoc['WTMdate'] = formatDate($assoc['WTMdate']);
		else {
			$assoc['WTMdate'] = "";
		}
	$dstr = print_r($assoc, true); fwrite($fp, $dstr);
	$selStr = "SELECT UserKey from users WHERE UserID = '".$_GET['userid']."'";
	$assoc['loggedInUserKey'] = getSingle($selStr, 'UserKey', $handle);
	$selStr = "SELECT UserKey from users WHERE UserID = '".$assoc['userid']."'";
	$assoc['goAwayerUserKey'] = getSingle($selStr, 'UserKey', $handle);
	$selStr = "SELECT LastName from physicians WHERE UserKey = '".$assoc['goAwayerUserKey']."'";
	$assoc['goAwayerLastName'] = getSingle($selStr, 'LastName', $handle);
	$selStr = "SELECT LastName from physicians WHERE UserKey = '".$assoc['coverageA']."'";
	$assoc['CovererLastName'] = getSingle($selStr, 'LastName', $handle);
	$assoc['covererDetails']['LastName'] =$assoc['CovererLastName'] ;
	if ($assoc['coverageA'])
		if (  intval($assoc['loggedInUserKey']) == intval($assoc['coverageA']))
			$assoc['IsUserCoverer'] = true;
	$ss = print_r($assoc, true); fwrite($fp, $ss);
	$jData = json_encode($assoc);
	echo $jData;
	exit();
	function formatDate($dt){
		global $fp;
		if (is_object($dt))
			try  {
				return $dt->format('m/d/Y');
			}
			catch(Exception $e){
			fwrite($fp,  $e->getMessage());
		}
		else
			return $dt;
	}

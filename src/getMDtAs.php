<?php

require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
require_once('workdays.inc');
require_once('dosimetristList.php');
include('isHollidayLib.php');
$handle = connectDB_FL();
	$tableName = 'MDtimeAway';
	$fp = fopen("./Alog/getMDtAsLog.txt", "w+");
	$todayString =  date('Y-m-d H:i:s');
	fwrite($fp, "\r\n $todayString");
	$s =  print_r($_GET, true); fwrite($fp, $s); 
	//$MDtAs = getMDtAs();
	$MDtAs['tAs'] = getMDtAs();
//	$MDtAs['coverers'] = getServiceOfGoAwayer();
	$jData = json_encode($MDtAs);
//	echo "<br> 14 <br>"; echo "<pre>"; print_r($MDtAs); echo "</pre>"; 
	echo $jData;
 exit();
function getMDtAs(){
	global $handle, $fp, $tableName;
	$MDs = getMDs();
	$MDservice = getMDservice();
	$UserKeys = getAllUserIds($handle);
	$firstDay = date('Y-m-01');
	//$secondMonth = $_GET['adv'] + 2;
	if (isset($_GET['adv'])){						// NOT the present month
		$firstDay = date('Y-m-01', strtotime('first day of +'.$_GET['adv'].' month'));
		$lastDateOnCalendar = new DateTime($firstDay);			// make a date for LastDateOnCalendar
		$lastDateOnCalendar->modify('+ 1 months');		
		$lastDay = $lastDateOnCalendar->format( 'Y-m-t' );		// go to the LAST day in that month
	}
	fwrite($fp, "\r\n firstDay is $firstDay lastDay is $lastDay \r\n ");
	$firstDate = new DateTime($firstDay);
	$endDate = $firstDate->modify(" + 2 months");
	$endDateString = $endDate->format("Y-m-d");
	$selStr = "SELECT * FROM $tableName WHERE endDate >= '".$firstDay."' AND startDate < '".$endDateString."' AND reasonIdx < 9 ORDER BY startDate";
	fwrite($fp, "\r\n selStr is \r\n $selStr ");
	$dB = new getDBData($selStr, $handle);
	$i = 0;
	$servArray = array('1'=>'14','2'=>'13','3'=>'3','4'=>'5','5'=>'4','6'=>'13');
	$vacGraph = array();
	while ($assoc = $dB->getAssoc()){
		$vacGraph[$i][$assoc['userid']]['LastName'] = $MDs[$UserKeys[$assoc['userid']]]['LastName'];
		$vacGraph[$i][$assoc['userid']]['service'] = $MDs[$UserKeys[$assoc['userid']]]['service'];
		$vacGraph[$i][$assoc['userid']]['serviceAlph'] = $servArray[$MDs[$UserKeys[$assoc['userid']]]['service']];
		$vacGraph[$i][$assoc['userid']]['UserKey'] = $UserKeys[$assoc['userid']];
		$vacGraph[$i][$assoc['userid']]['userid'] = $assoc['userid'];
		$vacGraph[$i][$assoc['userid']]['note'] = $assoc['note'];
		$vacGraph[$i][$assoc['userid']]['reasonIdx'] = $assoc['reasonIdx'];
		$vacGraph[$i][$assoc['userid']]['vidx'] = $assoc['vidx'];
		$vacGraph[$i][$assoc['userid']]['startDate'] = $assoc['startDate']->format('Y-m-d');
		$vacGraph[$i][$assoc['userid']]['endDate'] = $assoc['endDate']->format('Y-m-d');
		$vacGraph[$i][$assoc['userid']]['WTMnote'] = $assoc['WTMnote'];
		$vacGraph[$i][$assoc['userid']]['WTM_self'] = $assoc['WTM_self'];
		$vacGraph[$i][$assoc['userid']]['WTM_Change_Needed'] = $assoc['WTM_Change_Needed'];
		$vacGraph[$i][$assoc['userid']]['CovAccepted'] = $assoc['CovAccepted'];
		$vacGraph[$i][$assoc['userid']]['class']=  'orange' ;
		if ($assoc['CovAccepted'] > 0 ) 
			$vacGraph[$i][$assoc['userid']]['class']= 'green';
		if ($assoc['WTMdate'])
			$vacGraph[$i][$assoc['userid']]['WTMdate'] = formatDate($assoc['WTMdate']);
		$vacGraph[$i][$assoc['userid']]['daysTillStartDate'] = getdays($firstDay, $vacGraph[$i][$assoc['userid']]['startDate'], $firstDay);
		$vacGraph[$i][$assoc['userid']]['daysTillCalEnd'] = getdays( $vacGraph[$i][$assoc['userid']]['endDate'], $lastDay, $firstDay);
		// 1 day tA has StartDate = EndDate so have to add 1 to length
		$vacGraph[$i][$assoc['userid']]['vacLength'] = getdays($vacGraph[$i][$assoc['userid']]['startDate'],$vacGraph[$i][$assoc['userid']]['endDate'], $firstDay) + 1  ;
		$selStr = "SELECT UserID from users WHERE UserKey = '".$assoc['coverageA']."'";
//	$vacGraph[$i][$assoc['userid']]['covererUserKey'] = $selStr;
		if (isset($assoc['coverageA']) && strlen($assoc['coverageA']) > 1){	
			$vacGraph[$i][$assoc['userid']]['covererUserId'] = getSingle($selStr, 'UserID', $handle);
			$vacGraph[$i][$assoc['userid']]['covererageA_UserKey'] = $assoc['coverageA' ];
			$vacGraph[$i][$assoc['userid']]['covererDetails'] = $MDs[$assoc['coverageA']];
		}
	
		$i++;
		fwrite($fp, "\r\n ".$vacGraph[$i][$assoc['userid']]['startDate']." daysTillStart is ". $vacGraph[$i][$assoc['userid']]['daysTillStartDate'] );
	}
	$sss = print_r($vacGraph, true); fwrite($fp, $sss);
	$onOneLine = putOnOneLine2($vacGraph, $MDservice);
	usort($onOneLine, 'sortByOrder');

	return $onOneLine;
}
function formatDate($dt){
		try  {
			return $dt->format('m-d-Y');
		}
		catch(Exception $e){
			echo $e->getMessage();
		}
	}

function sortByOrder($a, $b) {
    return $a[0]['serviceAlph'] > $b[0]['serviceAlph'];
}



function getMDs(){
	global $handle;
//	$users = getAllUserIDs($handle);
	$selStr = "SELECT * FROM physicians";
	$dB = new getDBData($selStr, $handle);
	while ($assoc = $dB->getAssoc()){
		$row[$assoc['UserKey']] = $assoc;
//		$row[$assoc['UserKey']]['userData'] = $users[$assoc['UserKey']];
	}
	return $row;
}
function getMDservice(){
	global $handle;
//	$users = getAllUserIDs($handle);
	$selStr = "SELECT * FROM mdservice";
	$dB = new getDBData($selStr, $handle);
	while ($assoc = $dB->getAssoc()){
		$row[$assoc['idx']] = $assoc['service'];
//		$row[$assoc['UserKey']]['userData'] = $users[$assoc['UserKey']];
	}
	return $row;
}
////////  days between dates \\\\\\\\\\\\\\\\\\\\\\\
function getdays($day1,$day2, $firstDay) 
{ 
	global $fp;
  if ($day1 == $day2)
	  return 0;
  else  {
	  $tst = round((strtotime($day2)-strtotime($day1))/(24*60*60),0) ; 
	  $tst2 = round((strtotime($day2)-strtotime($firstDay))/(24*60*60),0) ; 
	  if (strtotime($day1) - strtotime($firstDay) <= 0) {				// $day1 is in before CalendarStart
	  	$tst = round((strtotime($day2)-strtotime($firstDay))/(24*60*60),0) ; 
	  }
	  if ($tst < 0 )
		  $tst = 0;
	  return $tst;
  }
}

function putOnOneLine2($vacGraph, $MDservice){
	$ool = array();
	$byService = array();
	foreach($vacGraph as $key=>$val){
		$userid = key($val);
		$service = $val['service'];
		if (!is_array($ool[$userid])){
			$ool[$userid][0] = $val[$userid];
		}
		else {
			array_push($ool[$userid],  $val[$userid]);
		}
	}
	return $ool;
}


function getAll($handle)
{
	$dB = new getDBData("Select * from physicists", $handle);
	while ($assoc =$dB->getAssoc()){
//											echo "<br> "; var_dump($assoc);
		$row[$assoc['UserKey']] = $assoc;
	}
	$row[202]['LastName'] = "Seco";								// Kludge for Seco's double entry
	 $row[347]['LastName'] = "Hou";$row[347]['FirstName'] = "Tony";
	 $row[349]['LastName'] = "Barros";$row[349]['FirstName'] = "Nester";
	 $row[348]['LastName'] = "Vu";$row[348]['FirstName'] = "Kenneth";
	 uasort($row, "cmp");
	return $row;
}

function getAllUserIds($handle)
{
        $DB = new getDBData("SELECT UserID, UserKey  FROM users ORDER By UserKey", $handle);
        while ($DBRow = $DB->getAssoc()){
                $allUserIds[$DBRow['UserID']]=$DBRow['UserKey'];
	}
							/////////  hard code to include engineers to avoid having to change 'users' table
	$allUserIds[347] = 'TH963';
	$allUserIds[349] = 'nab35';
	$allUserIds[348] = 'kv072';
 	return $allUserIds;
}
function alphabetizeByThis($s, $vacGraph)
{
	$ct =  count($vacGraph);
	foreach ($vacGraph as $key=>$val)				//alphabetize
		for($x = 0; $x < $ct; $x++){
       		 	for($y = 0; $y < $ct; $y++){
				if (array_key_exists("lastName", $vacGraph[$x]) && array_key_exists("lastName", $vacGraph[$y])){
       		         		if (strcmp($vacGraph[$x]['lastName'], $vacGraph[$y]['lastName'])<  0){
       		               			  $hold = $vacGraph[$x];
	       		       	        	  $vacGraph[$x] = $vacGraph[$y];
       			       	        	  $vacGraph[$y] = $hold;
       			         	}
				}
			}
		}
//									if (strcmp($_GET['showPhys'], 2) == 0) var_dump($vacGraph);
	return $vacGraph;
}

function getPhysicists($handle, $dosimetrist)
{
	$dB = new getDBData("Select * from physicists", $handle);
	while ($assoc =$dB->getAssoc()){
		if (in_array($assoc['UserKey'], $dosimetrist))
			continue;
		$row[$assoc['UserKey']] = $assoc;
	}
	$row[202]['LastName'] = "Seco";								// Kludge for Seco's double entry
	$row[113]['LastName'] = "Hsiao-Ming";								// Kludge for Seco's double entry
	$row[336]['LastName']="Nguyen"; $row[347]['FirstName'] = "Khanhnhat";
	$row[347]['LastName']="Hou"; $row[347]['FirstName'] = "Tony";
	$row[349]['LastName']="Barros"; $row[349]['FirstName'] = "Nestor";
	$row[348]['LastName']="Vu"; $row[348]['FirstName']="Kenneth";
	 uasort($row, "cmp");

	return $row;
}
function getDosimetrists($handle, $dosimetrist)
{
	$dB = new getDBData("Select * from physicists", $handle);
	while ($assoc =$dB->getAssoc()){
		if (!in_array($assoc['UserKey'], $dosimetrist))
			continue;
		$row[$assoc['UserKey']] = $assoc;
	}
	uasort($row, "cmp");
	return $row;
}


function cmp($a, $b)
{
    return strcmp($a["LastName"], $b["LastName"]);
}
 


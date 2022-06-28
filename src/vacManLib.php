<?php
require_once 'H:\inetpub\lib\esb\_dev_\sqlsrvLibFL.php';

/**
 * Checks for an existing tA in the same service as the  2 B entered tA which overlaps. 
 */
function checkServiceOverLapLib($data){
	global $handle, $fp, $tableName; 
	$overlap = 0;	$i = 0;		$row = array();								// set up		
	$selStr = "SELECT vidx, userid, startDate, endDate  FROM MDtimeAway WHERE service = '".$data->service."' AND reasonIdx < 9 AND (
		( startDate >= '".$data->startDate."' AND  startDate <= '".$data->endDate."')
		OR	( endDate >= '".$data->startDate."' AND  endDate <= '".$data->endDate."')
		OR (   startDate <= '".$data->startDate."' AND  endDate >= '".$data->endDate."'  )
			)";
	fwrite($fp, "\r\n 84  selStr is \r\n  $selStr \r\n");
	$dB = new getDBData($selStr, $handle);
	while ($assoc = $dB->getAssoc()){
		$row[$i] = $assoc;																	// store the overlapping tA
		$row[$i++]['overlapName'] = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$assoc['userkey']."'", "LastName", $handle);	// get LastName of Overlapping tA
	}
	return $row;
}	
/**
 * Check for overlap of existing tA for the given goAwayer
 */
function checkOverlapLib($data){
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
?>
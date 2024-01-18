<?php
function sendStaffLib( $newTa, $mode){
    global $fp, $handleBB, $handle, $debug;
    $dB = getStaff($newTa['goAwayerUserKey']);
fwrite($fp, "\r\n 55555 ");    ob_start(); var_dump($newTa );$data = ob_get_clean();fwrite($fp, "\r\n newTa is \r\n ". $data);
	$i = 0;
	$link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?vidxToSee=".$newTa['vidx'];	
	$covMsg = "<p> The coverage for this Time Away is TBD </p>";
	
	while ($assoc = $dB->getAssoc()){
		$link = "\n https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/dist/MDModality/index.html?userid=".$assoc['UserID']."&vidxToSee=".$newTa['vidx'];	
	//	$mailAddress = $assoc['Email'];
		$mailAddress = 'flonberg@mgh.harvard.edu';
		$subj = "Time Away for Dr. ". $newTa['goAwayerLastName'];
		$msg = "<p> Hi ".$assoc['FirstName']."<p>";
        if ($mode == 0)
		    $msg.= "<p>Dr. ". $newTa->goAwayerLastName ." is going to be away from ". $newTa->startDate ." through ". $newTa['endDate'] ."</p>";
        if ($mode == 1){
            $msg.= "<p>With reference to Dr. ". $newTa['goAwayerLastName'] ." starting ". $newTa['dBstartDate']->format('Y-m-d') ."</p>";
		    $msg.= "<p>Dr. ". $newTa['CovererLastName'] ." has accepted coverage. </p>";
        }
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
					</body>
				</head>	
			</html>
			'; 
		$sendMail = new sendMailClassLib($mailAddress,  $subj, $message);	
		//if (!$debug)
			$sendMail->send();	  
        }
    }
    function getStaff($goAwayerUserKey){
        global $handle, $fp;
        $selStr = "SELECT * from MD_TimeAway_Staff WHERE MD_UserKey = ". $goAwayerUserKey;
    fwrite($fp, "\r\n 424242". $selStr);    
        $dB = new getDBData($selStr, $handle);
        $assoc = $dB->getAssoc();
    fwrite($fp, "\r\n 4444 ");    ob_start(); var_dump($assoc);$data = ob_get_clean();fwrite($fp, "\r\n ". $data);
        $selStr = "SELECT other.FirstName, other.LastName, other.Email, other.UserKey, users.UserID 
        FROM other LEFT JOIN users on other.UserKey=users.UserKey WHERE other.UserKey IN (";
        foreach ($assoc as $key=>$val){                                     
            if ($val > 0)
                $selStr .= " $val,";                        // add staff memeber to selStr
        }
        $selStr = substr($selStr, 0, -1);
        $selStr .= ")";
fwrite($fp,  "\r\n 525252 ". $selStr);
        $dB = new getDBData($selStr, $handle);
        return $dB;
    }

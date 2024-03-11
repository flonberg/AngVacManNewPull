<?php
require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL.php';
require_once './mailLib.php';
require_once './safeSQL.php';
header("Content-Type: application/json; charset=UTF-8");
$handle = connectDB_FL();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "./Alog/editSingleParamError.txt");
$IAP = new InsertAndUpdates();
	if (strpos(getcwd(), 'dev') !== FALSE)
		$level = 'dev';
	else 
		$level = 'prod';	
	$fp = fopen("./Alog/editSingleParamLog.txt", "w+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
	$std = print_r($_GET, true); fwrite($fp, "\r\n GET has \r\n". $std);
    $updateStr = "UPDATE TOP(1) ".$_GET['tableName']." SET ".$_GET['name']." = '".$_GET['value']."' WHERE vidx = ".$_GET['vidx'];
    if ($_GET['tableName'] == "MD_TA_Coverage")
        $updateStr = "UPDATE TOP(14) ".$_GET['tableName']." SET ".$_GET['name']." = '".$_GET['value']."' WHERE vidx = ".$_GET['vidx'] ." AND CovererUserKey = ".$_GET['goAwayerLastName'];
    fwrite($fp, "\r\n $updateStr \r\n");
    $res = sqlsrv_query( $handle, $updateStr);    
    if( $res === false ) {
        $dstr =  print_r( sqlsrv_errors(), true);
        fwrite($fp, "\r\n $dstr \r\n ");
    }
    if ($_GET['name'] == 'coverageA'){
        $email = getSingle("SELECT Email FROM physicians WHERE UserKey = '".$_GET['value']."'", 'Email', $handle);
        $LastName = getSingle("SELECT LastName FROM physicians WHERE UserKey = '".$_GET['value']."'", 'LastName', $handle);
        fwrite($fp, "\r\n email is $email \r\n");
        $userid = getSingle("SELECT UserID FROM users WHERE UserKey = '".$_GET['value']."'", 'UserID', $handle);
        fwrite($fp, "\r\n UserID is $userid");
        $link = "\n https://whiteboard.partners.org/esb/FLwbe/angVac6/dist/MDModality/index.html?userid=".$userid."&vidxToSee=".$_GET['vidx']."&acceptor=1";	
        fwrite($fp, "\r\n ". $link);
		$mailAddress = $email;	
		$subj = "Coverage for Time Away";
		$subj .= " to ".$email;							
		$mailAddress = "flonberg@partners.org";					////// for testing   \\\\\\\\\\\
		$msg = "Dr.".$LastName.": <br> Dr.". $_GET['goAwayerLastName'] ." would like you to cover a Time Away. ";
		$msg .= "<p> To see details and accept or decline this altered coverage click on the below link.</p>";
		$message = '
			<html>
				<head>
					<title> Time Away Coverage </title>
						<body>
							<p>'. $msg .'</p>
							<p>
								<a href='.$link .'> Accept Coverage. </a>
						</body>
				</head>	
			</html>
				'; 
			$sendMail = new sendMailClassLib($mailAddress, $subj, $message);	
	//		$sendMail->setHeaders($headers);	
			$sendMail->send();
    }

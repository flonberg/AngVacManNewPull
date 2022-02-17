<?php

function connectDB_FL()
{

$serverName = "phsqlweb242";
/* Get UID and PWD from application-specific files.  */
//$uid = file_get_contents("C:\AppData\uid.txt");
//$pwd = file_get_contents("C:\AppData\pwd.txt");
$connectionInfo = array( "UID"=>'WB_Admin',
                         "PWD"=>'2WhiteBoarD2',
                         "Database"=>"imrt");

/* Connect using SQL Server Authentication. */
$handle = sqlsrv_connect( $serverName, $connectionInfo);
return $handle;

}
/* returns assoc. array with key specified by '$keyStr' */
function getAssocByKey($selStr, $keyStr, $handle)
{
	$row=null;
	$res = sqlsrv_query($handle, $selStr);
	if ($res == FALSE)
		error("getAssocByKe error $selStr");
	while ($assoc = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC )){
		if (is_object($assoc[$keyStr]))			// allow for 'date-objects'
			$assoc[$keyStr] = $assoc[$keyStr]->format("Y-m-d");
		$row[$assoc[$keyStr]] = $assoc;
		}
	return $row;
}
	
/*  returns the single value of columne specified by '$p' according to search string specified by '$s' */
function getSingle($s,  $p, $handle)
{
	$res = sqlsrv_query($handle, $s);
	if ($res == FALSE)
		error("getSingle---".$s);
	if (is_resource($res)){							// Oct16_2018 
		$row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC );
		if (isset($row[$p]))									// oct4_2018
			return $row[$p];
	}
}

/* inserts record specified by '$insStr'in '$tableName'  and returns the value of the column spec. by '$idenColName' for the record inserted */
function insertRetLast($insStr, $tableName,  $idenColName, $handle)
{
	$res1 = sqlsrv_query($handle, $insStr);	
	if ($res1 == FALSE)
		error($insStr);
	$selStr = "SELECT top(1) $idenColName FROM $tableName ORDER BY $idenColName DESC";
	$res = sqlsrv_query($handle, $selStr);
	if ($res == FALSE)
		error($insStr);
	$assoc = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC );
	$last = $assoc[$idenColName];
	return $last;
}
/////////////    Returns the ID of the last inserted record       \\\\\\\\\\\\\\\\\\\\
/////////////  INSERT INTO tableName  (... ) VALUES  (...); SELECT SCOPE_IDENTITY() AS ID         \\\\\\\\\\\\\\\\\\\\\
function insertScopeID($s, $handle)
{
	$res = sqlsrv_query($s, $handle);
	sqlsrv_next_result($res);
	sqlsrv_fetch($res);
	return sqlsrv_get_field($res,0);
}

function sqlsrvCmd($handle, $cmdStr)
{
	$res1 = sqlsrv_query($handle, $cmdStr);	
	if ($res1 == FALSE)
		error($cmdStr);
	if (is_null($res1 ))
		error($cmdStr);
	
}
function doCmd($cmdStr, $handle)
{
	$res1 = sqlsrv_query($handle, $cmdStr);	
	if ($res1 == FALSE)
		error($cmdStr);
	return $res1;	
}

function error($s)
{
	$errorArray = array('err'=>$s);
}




function getLast($handle, $tableName, $p)
{

	$selString = "SELECT TOP 1 ". $p ." FROM $tableName ORDER BY ". $p ." DESC";
									
	$res1 = sqlsrv_query($handle, $selString);	
	if ($res1 == FALSE)
		error($selString);
	$assoc = sqlsrv_fetch_array($res1, SQLSRV_FETCH_ASSOC );
								
	$last = $assoc[$p];
	return $last;
}

class getDBData
{
        protected $result;
        public function __construct($s, $handle)
        {
                $this->result = sqlsrv_query($handle, $s);
		if ($this->result === FALSE)
			error($s);
        }
        public function getResult()
        {
                return $this->result;
        }
        public function getAssoc()
        {
		if (is_resource($this->result)){
            	   	 $row = sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC);
               		 return $row;
		}
		else
			return false;
        }
        public function getRow()
        {
                $row = sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC);
                return $row;
        }
        public function getNum()
        {
		$row_count = sqlsrv_num_rows($this->result);
                return $row_count;
        }
}
function safeSQL_lib($insStr, $handle){
	global $fp,$cp, $mp; 
	try { 
		$res = sqlsrv_query($handle, $insStr);
		} 	catch(Exception $e) {
				error_log( "Exception is ". $e);
			}
	if ($res !== FALSE){
		fwrite($cp, "\r\n Sucess SQL ". $insStr);
	}
	else {
		fwrite($mp, "\r\n update failed for $insStr");	fwrite($cp, "\r\n update failed");

	}
}
class logFiles 
{
	public $cumul;									// accumulating file
	public $error; 												// accumulating error file
	public $int;												// rewrite each run
	public $loc;
	public function __construct(){
		$myDir = __DIR__;
		if (strpos($myDir, '_dev_') !== FALSE)
			$this->loc = "_dev_";
		if (strpos($myDir, '_prod_') !== FALSE)
			$this->loc = "_prod_";	
		$now = new DateTime(); $nowString = $now->format("Y-m-d H:i:s");
		$this->cumul = 	fopen("H:\\inetpub\\esblogs\\".$this->loc."\\test2_FL_LIB_Inc2.log", "w+"); 	
		fwrite($this->cumul, "\r\n $nowString \r\n");
		$this->errorFileName = 	"H:\\inetpub\\esblogs\\".$this->loc."\\test2_FL_LIB_Error2.log"; 	
		fwrite($this->error, "\r\n $nowString \r\n");	
	}
	

}
class InsertAndUpdates 
{
	public $lf;
	public function __construct(){
		$now = new DateTime(); $nowString = $now->format("Y-m-d H:i:s"); $nowStringShort = $now->format('Y-m-d');
		$fileIndex = 0;
		do {
			$fileIndex++;
			$fileName = "H:\\inetpub\\esblogs\\_dev_\\insAndUpdate".$nowStringShort."-".$fileIndex.".log"; 
			$this->lf = fopen($fileName, "a+");
			if ($fileIndex > 10)
				break;
		}	while ($this->lf === FALSE);
		
		if ($this->lf === FALSE){														// permission proble, 
			$fileName = "H:\\inetpub\\esblogs\\_dev_\\insAndUpdate".$nowStringShort."A.log"; 
			$this->lf = fopen($fileName, "a+");
		}
		fwrite($this->lf, "\r\n \r\n ". $nowString);
	}
	function safeSQL($insStr, $handle){
		try { 
			$res = sqlsrv_query($handle, $insStr);
			} 	catch(Exception $e) {
					error_log( "Exception is ". $e);
				}
		if ($res !== FALSE){
			fwrite($this->lf, "\r\n Sucess SQL \r\n". $insStr);
		}
		else {
			fwrite($this->lf, "\r\n update failed for $insStr");	
			$errs = print_r( sqlsrv_errors(), true); fwrite($this->lf, $errs);
		}
	}
	function close(){
		fclose($this->lf);
	}
}

class sendMailClassLib
{
	public function __construct($address, $subject, $msg){
		$this->address = $address;
		$this->subject = $subject; 
		$this->msg = $msg;
		$this->headers="";
		$this->headers = 'MIME-Version: 1.0' . "\r\n";
		$this->headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	 	$this->headers .= 'From: <whiteboard@partners.org>' . "\r\n";
	 	$this->headers .= 'Cc: flonberg@partners.org'. "\r\n";
		$this->logFp = fopen("H:\\inetpub\\esblogs\\_dev_\\sendMail.log", "w+");
		$now = new DateTime(); $nowString = $now->format("Y-m-d H:i:s"); fwrite($this->logFp, "\r\n $nowString");
	}
	public function setHeaders($headers){
		$this->headers = $headers;
	}
	public function send(){
		 mail($this->address,$this->subject,$this->msg, $this->headers);

	}
	public function setSubject($subject){
		$this->subject = $subject; 
	}
}


<?php

function connectBB()
{
$serverName = "ROBLACKBOARD19\ROBLACKBOARD19";
$connectionInfo = array( "UID"=>'WB_Dev',
                         "PWD"=>'intake',
                         "Database"=>"imrt");
$handle = sqlsrv_connect( $serverName, $connectionInfo);
	if($handle===false)
	{
	$timestamp = date('Y-m-d h:i:s');
	$errors = $timestamp.PHP_EOL.print_r( sqlsrv_errors(),true);
	error_log("\n".$errors.PHP_EOL.PHP_EOL.PHP_EOL, 3,'H:/inetpub/lib/connectionErrors.log');
	echo 'Unable to connect to Blackboard<br>';
	}
return $handle;	
}
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
							//	echo "<br> lib hs <br>"; var_dump($handle);
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
//	if ($res == FALSE)							// Nov 4 2021
//		error("getSingle---".$s);
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
	echo "<script>";
	echo "alert('QUERY failed for $s')";
	echo "</script>";
}

function getLast($handle, $tableName, $p)
{

	$selString = "SELECT TOP 1 ". $p ." FROM $tableName ORDER BY ". $p ." DESC";
											echo "<br> selString is <br> $selString";
	$res1 = sqlsrv_query($handle, $selString);	
	if ($res1 == FALSE)
		error($selString);
	$assoc = sqlsrv_fetch_array($res1, SQLSRV_FETCH_ASSOC );
											echo "<br> assoc is <br>"; var_dump($assoc);
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
/*
function genInsert($s)
{
        $result = mssql_query($s)
                        or die("insert Query failed,  for query  $s ");
}
function genDelete($s)
{
	$result = mssql_query($s)
		or die("Delete query failed for $s");
}
function genInsertIdx($s)
{
        $result = mssql_query($s)
                        or die("insert Query failed,  for query  $s ");
	$res = mssql_query("select SCOPE_IDENTITY()")
		or die ("scope_identity failed");
	$row = mssql_fetch_array($res);
	return $row[0];
}

function genUpdate($s)
{
        mssql_query($s)
                      or die("update Query failed,  for   . $s ");
}
y
function getAssoc($s)
{
         $result = mssql_query($s) or die ("general query failed for $s");
         return mssql_fetch_row($result);
}

function getAssoc1($s)
{
         $result = mssql_query($s) or die ("getAssoc1 general query failed for $s");
         return mssql_fetch_assoc($result);
}

function getAssocMulti($s)
{
	$i = 0;
         $result = mssql_query($s) or die ("getAssocMulti general query failed for $s");
	 while ($row = mssql_fetch_assoc($result))
		 $rowAssoc[$row['name']] = $row;
         return $rowAssoc;
}

function getTechnique()
{
	$techniqueDB = new getDBData("SELECT techidx, technique FROM technique WHERE active=1");
	while ($techniqueRow = $techniqueDB->getAssoc())
		$techniques[$techniqueRow['techidx']] = $techniqueRow['technique'];
	return $techniques;
}

function getPhysicist()
{
	$physicistDB = new getDBData("SELECT UserKey, LastName FROM physicists ");
	while ($physicistRow = $physicistDB->getAssoc())
		$physicist[$physicistRow['UserKey']] = $physicistRow['LastName'];
	return $physicist;
}

function getMD()
{
	$MDDB = new getDBData("SELECT UserKey, LastName FROM physicians ");
	while ($MDRow = $MDDB->getAssoc())
		$MD[$MDRow['UserKey']] = $MDRow['LastName'];
	return $MD;
}
function getDx()
{
 $dxDB = new getDBData("SELECT dxidx, dxDescript FROM MQdiag order by dxcode");
 while ($dxRow = $dxDB->getAssoc())
  $dxs[$dxRow['dxidx']] = $dxRow['dxDescript'];
 return $dxs;
}
 


function getTxModes()
{
	$txModeDB = new getDBData("SELECT idx, mode FROM txmode");
	while ($txModeRow = $txModeDB->getAssoc())
		$txModes[$txModeRow['idx']] = $txModeRow['mode'];
	return $txModes;
}


function editComment($planId, $Comment)
{
                $Comment_Result=mssql_query("SELECT QA_Comment FROM QA_Params WHERE Plan_Idx=$planId");
                $Comment_Read=mssql_result($Comment_Result,0,"QA_Comment");
                if ( $Comment_Read != $Comment){
                        $query = "UPDATE QA_Params SET QA_Comment = '$Comment' WHERE Plan_Idx = $planId";
                        mssql_query("$query") or die ("Comment update failed for $planId");
                }
}
function check_valid_user($userid)
{
	if (strlen($userid) >0)
	{
		$loguser =getSingle("SELECT userid FROM login WHERE UserID = '" . $userid ."'", "userid");	
		$date = date('Y-m-d H:m');
	//	genUpdate("UPDATE login SET Logtime = '" .$date ."' WHERE UserID ='" . $userid ."'");	
		$priviledge = getSingle("SELECT privledge FROM users WHERE UserID = '" . $userid ."'", "privledge");
		return $priviledge;
	}
	return false;
}

function genMenu($name, array $options, $selected=null)
{

	$txMode = new getDBData($q);
	$i = 0;
        while ($row = $txMode->getAssoc())
        {
		$midx[$i] = $row[$s1];
		$mname[$i] = $row[$s2];
		$i++;
	}
	$count = $i;
	if($mmode == 1)
	{
	//	echo "<option value=\"ALL\">ALL</option>";
	}
	$dropdown  = '<select name = "'. $name.'" id="'. $name.'">'."\n";
	$selected = $selected;
	foreach ($options as $key => $option)
	{
		$select = $selected==$key ? ' selected' :null;
		$dropdown .= '<option value"' .$key.'"' . $select. '>' . $option  . '</option>'."\n";
	}
	$dropdown .= '<select>' ."\n";
	return $dropdown; 
}
*/

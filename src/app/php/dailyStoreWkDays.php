<?php
//require_once 'H:\inetpub\lib\ESB\_dev_\sqlsrvLibFL2noBB.php';
require_once 'H:\inetpub\lib\sqlsrvLibFL2NewBB.php';
require_once('H:\inetpub\lib\switchConnMQ.inc');
ini_set("error_log", "./log/QAdashBoardError.txt");
$connDB = new connDB();
$handleMQ = connectMSQ(); 
$handle242 = connectDB_FL();
$handleBB  = connectBB();

if (isset($_GET['debug']))
  $debug = $_GET['debug'] == '1' ? TRUE : FALSE;

if (isset($_GET['Start'])){
    $params['startDate'] = $_GET['Start'];
    $params['endDate'] = $_GET['End'];
    $params['startWFstage'] = 'Contours and Prescription';
    $params['endWFstage'] = 'StartDate';
}
else
  $params = makeLast3Days();
  echo "params are <pre>"; print_r($params); echo "</pre>";
  $wd = fileWorkdays($params);
$fp = fopen('./log1023/dailyStorePlans.txt', 'a+');
$now = date('Y-m-d H:i'); fwrite($fp, "\r\n $now \r\n "); 
$str = print_r($params, true); fwrite($fp, $str );
echo "<br> params  <pre>"; print_r($params); echo "</pre>";
$lp = openLogCVSfile($params);
$body = json_encode($params);
echo "<br> GET<br>"; var_dump($_GET);

$ch = curl_init(); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  $url = "https://ion.mgh.harvard.edu/cgi-bin/QAdashBd/dailyStoreWkDays.php";			//  ION/.../dailyStoreWkDays.php starts from today and goes back 3 days;
  if (isset($_GET['Start']))
    $url .='?Start='.$_GET['Start'].'&End='.$_GET['End'];
//  $url .='?Start='.$params['startDate'].'&End='.$params['endDate'];
  //if ($debug)
      echo "<br> 30 url is $url <br>";  
    fwrite($fp, "\r\n ION url is is ". $url);
    $ret = curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch,CURLOPT_POST, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 					// return argument is a json string
    $output = curl_exec($ch); 	

  $data = json_decode($output, true);

 // var_dump($data);

  var_dump(json_last_error());
  if (json_last_error() > 0){
    echo "<br> lasterror is <br>"; 
  //  var_dump($output);
  }
 // else
   // var_dump($data);

$fromIon = count($data[0]);                                                                 // counter for Plans retrieved from ION

echo "<br> count from ION 0 is "; var_dump(count($data[0])); echo "<br>";
fwrite($fp, "\r\n Records from ION is $fromIon");

  $selStr = "SELECT
  MAX(case when intakeFldName = 'patUnitNumber' then value end) patUnitNumber
  FROM intakeResponses AS IR
  JOIN intakeForm AS INF ON IR.formID = INF.formID
  WHERE IR.valid=1
  AND INF.valid =1              
  GROUP BY IR.formID, INF.createWhen
  HAVING MAX(case when intakeFldName = 'isThisUrgent' then value end) = '1'
  ORDER BY IR.formID DESC"  ;
$dB = new getDBData($selStr, $handle242);
$urgent = Array();
while ($assoc = $dB->getAssoc()){
 // echo "<br> 8282 </pre>"; print_r($assoc); echo "</pre>";
  array_push($urgent,str_replace("-", "", $assoc['patUnitNumber']));
} 
//echo " urgents <pre>"; print_r($urgent); echo "</pre>";
$i = 0;
$obj = $data[0];                              // the
$recIns = 0;
$dups = 0;
$loops = 0;


/** loop to get CT time  */  
  foreach ($data[0] as $key => $val) {  
    if (strlen($key) > 3)                         // it is NOT a plan
      continue; 
    $strMRN = putInDashes($val['UnitNumber']);  
    $Pat_Id1 = getMQiD($strMRN);
    $data[0][$key]['CTdateTime'] = @selectFromMQTest($Pat_Id1, $val['ScanDate']);
  }
 // if ($debug)

//  echo "<pre>"; print_r($data[0]); echo "</pre>";
foreach ($data[0] as $key=>$val){
  if (strlen($key) < 4)
  {
 //  echo "  107107 key is  $key <pre>"; print_r($val); echo "</pre>";
  if (isset($val['Contours']))
    $data[0][$key]['workdays'] = workDaysf( $val['Contours'], $val['StartDate'],$val['planIdx'], 'C&PtoSD',$wd);
  if (isset($val['ScanDate']))
    $data[0][$key]['workdays2'] = workDaysf( $val['ScanDate'], $val['Contours'],$val['planIdx'], 'ScDtoC&P',$wd);
  if (isset($val['planWriteUp']))
    $data[0][$key]['workdays3'] = workDaysf( $val['planWriteUp'], $val['StartDate'],$val['planIdx'], 'PWUtoSD',$wd);
  // $tst = strpos($val['SimDate'], '0000') ; echo "<br> 112 ". $val['SimDate']; var_dump($tst);
    if (strpos($val['SimDate'], '0000') === FALSE)                                             // there IS valid SimDate
          $data[0][$key]['earlierOfSDorVsim'] = $val['SimDate'] < $val['StartDate'] ? $val['SimDate'] : $val['StartDate'];    // take Earlier(SimDate, StartDate)
      else
          $data[0][$key]['earlierOfSDorVsim']=$val['StartDate'];                          // take StartDate
      $data[0][$key]['earlierOfSDorVsim'] = goBack1WorkDay($data[0][$key]['earlierOfSDorVsim']);   
      $data[0][$key]['NworkDays'] = workDaysf($data[0][$key]['Contours'], $data[0][$key]['earlierOfSDorVsim'],$val['planIdx'], 'C&PtoSD',$wd);     
      $data[0][$key]['NworkDays3'] = workDaysf($data[0][$key]['planWriteUp'], $data[0][$key]['earlierOfSDorVsim'],$val['planIdx'], 'C&PtoSD',$wd);
      $data[0][$key]['WkDaysTpToEarlier'] = workDaysf($data[0][$key]['Treatment Planning'], $data[0][$key]['earlierOfSDorVsim'],$val['planIdx'], 'C&PtoSD',$wd);
  }

}
//echo "124 <pre>"; print_r($data[0]); echo "</pre>";
$cols = array('planIdx','UnitNumber','ScanDate','CTdateTime','EffCtDate','StartDate','authStartDate','SimDate','EffStartDate','Contours',
'planWriteUP','workdays','workdays2','workdays3');
writeCSV($data[0], $cols);


// Loop to do dB INSERTS
foreach ($data[0] as $key => $value) {  
    if (strlen($key) > 3)                                         // skip not-plan data
      continue;
      $insStr1 = "INSERT INTO QAD_workdays6 (insertWhen, ";  
    $insStr2 = "values (GETDATE(),";
    foreach ($value as $kkey => $vval){
      if (strpos($vval, '0000') !== FALSE)
        continue;
      if (strpos($kkey, 'Planning') !== FALSE )
        $kkey = 'TreatmentPlanning';  
      $insStr1 .= $kkey .",";
      $insStr2 .= "'".$vval ."',";  
      }
    $tst = in_array( $value['UnitNumber'], $urgent );
    //echo "<br>127127 ". $value['UnitNumber'];
    $insStr1 .= "urgent,";
    if ($tst ){
        $insStr2 .= "1,";  
    }
    else
      $insStr2 .= '0,';
  $insStr1 = substr($insStr1, 0, -1);
  $insStr2 = substr($insStr2, 0, -1);
  $insStr = $insStr1 .") " . $insStr2 . ")";

  if ($debug)
    echo "<br> 9595  $insStr <br>";
    $res = sqlsrv_query($handleBB, $insStr);
    $res = sqlsrv_query($handle242, $insStr);
    if ($res === FALSE){
      if( ($errors = sqlsrv_errors() ) != null) {
        foreach( $errors as $error ) {
            echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
            echo "code: ".$error[ 'code']."<br />";
            echo "message: ".$error[ 'message']."<br />";
            echo "<br> $insStr <br>";
          }
        }
        $dups++;
      } 
    else {
      $recIns++;
      $rows_affected = sqlsrv_rows_affected( $res);
      if ($debug)
          echo "<br> rec aff ".  $rows_affected;
    }   
}

echo "<br> inserts =  $recIns"; fwrite($fp, "\r\n $recIns plans Inserted");
echo "<br> dups =  $dups"; fwrite($fp, "\r\n $dups  dups");
exit();
/**
 * Open file named 'wkdays-2023-09-05-2023-09-08.txt"
 */
function fileWorkdays($params){
  $start = substr($params['startDate'],5);
  $end = substr($params['endDate'],5);
  $fName = "wkdays-".$start."-".$end.".txt";
 echo "<br> fNaame is $fName";
  $wd = fopen("./log1023/".$fName, "w+");
  $today = date("Y-m-d H:i");
  fwrite($wd, "\r\n". $today);
  return $wd;

}
function workdaysf($Date_1, $Date_2, $planIdx, $WFStage, $wd)
{
  global $debug;   
  $hols=array(     // corrected Columbus day 09-09 to 10-09 
               "2020-01-01"=>"New Years", "2020-01-20"=>"MLK","2020-02-17"=>"Washington","2020-05-25"=>"Memorial","2020-07-03"=>"Indep","2020-09-07"=>"Labor",
                "2020-10-12"=>"Columbus","2020-11-11"=>"Veterans","2020-11-26"=>"Thanksgiving","2020-12-25"=>"Christmas",
                "2021-01-01"=>"New Years", "2021-01-18"=>"MLK", "2021-02-15"=>"Washington", "2021-05-31"=>"Memorial","2021-06-05"=>"Independence","2021-09-06"=>"Labor",
                "2021-10-11"=>"Veterans","2021-11-25"=>"Thanksgiving","2021-12-24"=>"Christmas","2021-12-31"=>"Christmas",
                "2022-01-17"=>"MLK","2022-02-21"=>"WashingtonsBirthday","2022-05-30"=>"MemorialDay","2022-06-20"=>"JuneTeenth","2022-07-04"=>"July4",
                "2022-09-05"=>"LaborDay","2022-10-10"=>"ColumbusDay","2022-11-24"=>"Thanksgiving","2022-12-26"=>"ChristmasDay",
                "2023-01-02"=>"New Years","2023-01-16"=>"MLK","2023-02-20"=>"President Day","2023-05-29"=>"Memorial","2023-16-19"=>"JuneTeenth","2023-07-04"=>"Independence",
                "2023-10-09"=>"Columbus","2023-11-10"=>"Veterans Day","2023-11-23"=>"Thanksgiving","2023-12-25"=>"Christmas",
                "2024-01-01"=>"New Years Day","2024-01-15"=>"MLK","2024-02-19"=>"Washington Birthday","2024-04-15"=>"Patriots Day","2024-05-27"=>"Memorial Day","2024-06-19"=>"JuneTeenth",
                "2024-06-04"=>"Independence Day","2024-09-02"=>"Labor Day","2024-10-14"=>"Columbus Day","2024-11-11"=>"Veterans Day","2024-11-28"=>"Thanksgiving","2024-12-25"=>"Christmas"
                );
    $Date_2 = substr($Date_2,0, 10);                                                   // eliminate the TIME part

          fwrite($wd, "\r\n $planIdx ---- $WFStage ---  $Date_1   --- $Date_2 ----");
                $workDays = 0;
                if ($Date_1 == $Date_2)                                               // if dates are same workdays = 0
                        return 0;
               // $dayAfterDate = $Date_1;
                $wkDayIndex = date('w', strtotime($Date_1));                          // find the weekday/weekend of earlyDate
                $safe = 0;                                                            // prevent runaway loop
                while ($Date_1 < $Date_2){
                        $dayAfterTS = strtotime('+1 day', strtotime ($Date_1 ));      // make TS of dayAfter earlyDate
                        $wkDayIndex =  date('w', $dayAfterTS);                        // get its weekday/weekend index
                        $dayAfterDate = date( 'Y-m-d', $dayAfterTS);
                        if ($wkDayIndex > 0 && $wkDayIndex < 6){                        // if it IS a weekday
                                if (!array_key_exists($Date_1, $hols))                  // and it is NOT holliday
                                                $workDays++;                            // ONLY THEN increment the workDays
                        }
                        $Date_1 =  date( 'Y-m-d', $dayAfterTS);                       // set earlyDate to incremented date            
                        if ($safe++ > 40)
                                break;
                }                                                                     // repeat
            fwrite($wd, " --- workDays is $workDays"); 
        return $workDays;
}

function makeLast3Days(){
  $yesterday = date('Y-m-d',strtotime("-1 days"));
  $daysAgo3 = date('Y-m-d',strtotime("-3 days"));
  $params = Array('startDate'=>$daysAgo3,'endDate'=>$yesterday,'startWFstage'=>"Contours and Prescription",'endWFstage'=>'StartDate');
  return $params;
}
/**
 * Opens a file for logging data using the StartDate as part of file name for identification. 
 */
function openLogCVSfile($params){
  $fName = "AstoreDaily_CVS_".$params['startDate'];
  $inp = 0;
  $safe = 0;
  $lp = FALSE;
  do {
    $lp = fopen('./log1023/'.$fName.'_'.$inp.'.csv', 'w+');
    $inp++;
    if ($safe++ > 10)
      break;
  }
  while
    ($lp === FALSE);
  return $lp;  
}
function selectFromMQTest($Pat_Id1, $date){
  global $handleMQ, $lF, $fp, $fileName;
  $dateTime = $date .' 00:00:00';
  $selStr = "SELECT Top 1 Sch_Id,Activity, Sch_Set_Id, App_DtTm, Location, Version, Create_DtTm, Edit_DtTm, CHG_ID, Notes, Pat_ID1, SchStatus_Hist_SD, SchStatus_Hist_UD
    FROM Schedule 
    WHERE Pat_Id1 = '$Pat_Id1' 
    AND Location IN ('607','1221','1329','348')
    AND Version = '0'
    AND App_DtTm > '$dateTime'
    ORDER By Sch_Set_Id, Sch_Id" ;
//echo "<br> 178 $selStr ";  
  $dB = new getDBData($selStr, $handleMQ);

  while ($assoc = $dB->getAssoc()){
  // echo "186 <pre>"; print_r($assoc['App_DtTm']->format('Y-m-d H:i')); echo "</pre>";
    $CTdateTime = $assoc['App_DtTm']->format('Y-m-d H:i');
}
if (isset($CTdateTime))
  return $CTdateTime;
}
function getMQiD($mrn){
  global $handleMQ;
  $selStr = "SELECT top(1) Pat_Id1, IDA FROM Ident WHERE IDA = '".$mrn."'"; // get the MRN = IDA from the MQ Ident table
  $Pat_Id1 = getSingle($selStr, 'Pat_Id1', $handleMQ);
 // echo "<br> $selStr <br>";
 // echo "<br> 186 <br>"; var_dump($Pat_Id1);
  return $Pat_Id1;
}
/**
 * Put in LeadingZero and Dashes to form MRN suitable for Mosaiq
 */
function putInDashes($mrn){
  $strlen = strlen($mrn);
  if (strlen($mrn) == 6)                // needs leading Zero
    $mrn = '0'.$mrn;                    // put in leading zero
  $strMrn = substr($mrn, 0, 3) .'-'.substr($mrn,3,2).'-'.substr($mrn,5,2); // put in dashes
  return $strMrn;
}

function goBack1WorkDay($date){
  global $hols;
  $safe = 0;
  do {
      $dayBeforeTS = strtotime('-1 day', strtotime ($date ));                              // make TS of dayAfter earlyDate
      $wkDayIndex =  date('w', $dayBeforeTS);
      $date=  date( 'Y-m-d', $dayBeforeTS);                                               // get its weekday/weekend index
  if ($safe++ > 3)
      break;
  }
  while 
      ($wkDayIndex == 0 || $wkDayIndex == 6);
  return $date;    
}  
function writeCSV($data){
  global $lp;
  //echo "<pre>"; print_r($data); echo "</pre>";  
  $cols = array('planIdx','StartDate','SimDate','earlierOfSDorVsim','ScanDate','Contours','planWriteUp','workdays', 'workdays2', 'workdays3','NworkDays','NworkDays3');
  foreach ($cols as $key=>$val)
    fwrite($lp, $val.",");
  fwrite($lp, "\r\n");  
  
  foreach ($data as $key=>$val){
    if (strlen($key) > 3)
      continue;
    foreach ($cols as $kkey=>$vval){
      if (isset($val[$vval]))
        fwrite($lp, $val[$vval].",");
      else
        fwrite($lp, ",");  
    }
    fwrite($lp, "end \r\n");
  }
  
}

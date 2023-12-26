<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
ini_set("error_log", "./Alog/getMDServiceError.txt");
$handle = connectDB_FL();
$fp = fopen("./Alog/getAdmins.txt", "a+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
   
    $selStr = "SELECT p.id, p.physicianID, p.adminID, p.noticeType, p.active,p.noticeType, b.lastName, b.id, b.userKey, 
    FROM en_PhysicianAdminManager p 
    LEFT JOIN en_AdminsForPhysicians b on p.physicianID = b.id
    LEFT JOIN en_PhysiciansWithAdmins pn on p.physicianID = pn.id
   WHERE p.active = 1 and p.noticeType='routine'";

    $selStr = " SELECT p.id, p.physicianID, p.adminID, p.noticeType, p.active,p.noticeType, b.lastName, b.id, pn.userKey, u.UserID as pUserID, b.userKey as AdminUserKey
        FROM en_PhysicianAdminManager p
        LEFT JOIN en_AdminsForPhysicians b on p.physicianID = b.id
        LEFT JOIN en_PhysiciansWithAdmins pn on p.physicianID = pn.id
        LEFT JOIN users u on pn.userKey = u.UserKey
        WHERE p.active = 1 and p.noticeType='routine' and b.userKey = ".$_GET['UserKey'];
   
    fwrite($fp, "\r\n $selStr");
    $stmt = sqlsrv_query($handle, $selStr);
    if ($stmt === false)
        echo json_encode(array("res"=>"FALSE"));
    else 
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
            $res[$row['userKey']] = $row;
 //   $dB = new getDBData($selStr, $handle);
  //  $i = 1;
 //   while ($assoc = $dB->getAssoc())
  //      $row[$assoc['userKey']] = $assoc;
  //  sort($row);    
    $jData = json_encode($res);
    echo $jData;    
?>

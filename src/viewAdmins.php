<html>
<body>
<form name="test" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
physAdminID: <input type="text" name="physAdminId"><br>
Admin_E-mail: <input type="text" name="adminEmail"><br>
Admin_UserKey: <input type="text" name="adminUserKey"><br>
<input type="submit">
</form>

</body>
</html>
<?php
require_once 'H:\inetpub\lib\sqlsrvLibFL.php';
ini_set("error_log", "./Alog/getMDServiceError.txt");
$handle = connectDB_FL();
var_dump($_POST);

    $selStr = "SELECT  physicianAdmin.physAdminId, physicianAdmin.physicianUserKey, physicianAdmin.adminEmail, physicianAdmin.adminUserKey, physicians.LastName 
    FROM physicianAdmin
    INNER JOIN physicians ON physicians.UserKey = physicianAdmin.physicianUserKey";

    $dB = new getDBData($selStr, $handle);
    $i = 1;
    while ($assoc = $dB->getAssoc())
        $row[$i++] = $assoc;

echo "<pre>"; print_r($row); echo "</pre>";



<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'H:\inetpub\lib\esb\_dev_\sqlsrvLibFL2.php';
$conDB = new connDB();
echo "<br> hellow  7777 <br>";
var_dump($conDB);
exit();
$msg = "This is a 999 test";
$sML = new sendMailClassLib("flonberg@gmail.com",  "Test gmail", $msg);
$sML->addToHeader("Cc: flonberg@gmail.com");
$sML->send();
exit();
?>
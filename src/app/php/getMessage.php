<?php
ini_set("error_log", "./Alog/getMessageError.txt");
$fp = fopen("./Alog/getMessage.txt", "a+"); $todayString =  date('Y-m-d H:i:s'); fwrite($fp, "\r\n $todayString");
$file = file_get_contents('./message.txt', FILE_USE_INCLUDE_PATH);
$ret = array('message'=>$file);
echo json_encode($file);
?>
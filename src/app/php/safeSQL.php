<?php
	function safeSQL($insStr, $handle){
		global $fp;
        try { 
			$res = sqlsrv_query($handle, $insStr);
			} 	catch(Exception $e) {
					fwrite($fp, "Exception is ". $e);
				}
		if ($res !== FALSE){
			fwrite($fp, "\r\n Sucess SQL for \r\n". $insStr);
		}
		else {
			fwrite($fp, "\r\n update failed for $insStr");	
			$errs = print_r( sqlsrv_errors(), true); fwrite($fp, $errs);
		}
	}
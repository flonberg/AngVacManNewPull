<?php
class SQL {
    var $handle;
    var $fp;
    var $thisSql;
    var $sqlRes;
    public function __construct($handle){
        $this->handle = $handle;
        $this->openLogFile();
    }
    public function doSQL($qStr){
        $this->sqlRes = sqlsrv_query( $this->handle, $qStr);
            fwrite($this->fp, "\r\n 95959 \r\n $qStr");   
        if( $this->sqlRes === false ) 
           { $dstr = print_r( sqlsrv_errors(), true); fwrite($this->fp, "\r\n $dstr \r\n");}  
    }
    public function getAssoc($ind = null){
        if (is_null($ind))
           {$ind = 0;  $rowIndex = $ind;}
        while( $assoc =  sqlsrv_fetch_array( $this->sqlRes, SQLSRV_FETCH_ASSOC) ) 
            $row[$rowIndex++] = $assoc;
        return $row;
    }
    private function openLogFile(){
		$now = new DateTime(); 
		$todayStr = $now->format("Y-m-d");
		$this->fp = fopen("./Alog/SQL_log".$todayStr.".txt", "w+");
		$nowString = $now->format("Y-m-d H:i:s");
		fwrite($this->fp, "\r\n $nowString");
        return $this->fp;
	}
}
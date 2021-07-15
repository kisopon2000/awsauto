<?php

require_once dirname(__FILE__)."/XCgiLInfo.php";

class XCgiLLog extends XCgiLInfo
{
    // コンストラクタ
    public function __construct()
    {
        parent::__construct();
    }

    // デストラクタ
    public function __destruct()
    {
        parent::__destruct();
    }

    protected function writeLog($in_message)
    {
        $ret = 0;
    	if(($ret = $this->getIntegRoot($root)) != 0) return $ret;

		$log_directory = $root.LOG_DIRECTORY;          // ログディレクトリ
		$log_filename  = LOG_FILENAME;                 // ログファイル名
		$log_fileext   = '000';                        // ログファイル拡張子
		$log_filepath  = $log_directory.$log_filename; // ログのファイルパス
		$max_lotates   = MAX_LOTATES;                  // ログファイルを残す世代数
		$max_logsize   = MAX_LOGSIZE;                  // 1ファイルの最大ログサイズ(バイト)

	    // 保存先ディレクトリを作成
	    if(!file_exists($log_directory)){
	        mkdir($log_directory);
	    }

	    // ログのローテート
	    if(@filesize($log_filepath.$log_fileext) > $max_logsize){

	        // 最古のログを削除
	        @unlink($log_filepath.sprintf("%03d",$max_lotates));
	        // ログをリネーム .000 → .001
	        for ($i = $max_lotates - 1; $i >= 0; $i--) {
	            $bufilename = $log_filepath.sprintf("%03d",$i);
	            @rename($bufilename, $log_filepath.sprintf("%03d",$i+1));
	        }
	    }

	    // ログ出力 
    	$log_time = date('Y-m-d H:i:s');
	    file_put_contents($log_filepath.$log_fileext, '['.$log_time.'] '.$in_message."\n", FILE_APPEND | LOCK_EX);
		
		return $ret;
	}

    protected function writeErrorLog()
    {
      return $this->writeLog('*ERROR*:'.$this->getErrorFile().':'.$this->getErrorLine().' EXT:'.$this->getExternalError($this->getErrorId()).' INN:'.$this->getErrorId().' MSG:'.$this->getErrorMessage());
    }
}

?>
<?php

require_once dirname(__FILE__).'/XCgiLSession.php';

class XCgiLProc extends XCgiLSession
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
    
    private function kill($process)
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
            $status = proc_get_status($process);
            $taskkill = sprintf(TASKKILL, $status['pid']);
            exec($_SERVER['SystemRoot'].$taskkill, $out);
        } else {
            proc_terminate($process);
        }
    }

    // コマンド起動
    public function proc($in_cmd, $in_ignore, &$out_stdout, &$out_stderr)
    {
        $ret = 0;

        // コマンド実行
        $descriptor = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );
        $orgCurDir = getcwd();
        $ret = $this->getIntegRoot($root);
        if($ret != 0) goto PROC_END;
        $curDir = $root.RESIDENT_DIR;
        if(!file_exists($curDir)){
            $bRet = XCgiTools::mkdir($curDir);
            if(!$bRet){
                $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create dir failed. '.$curDir.' '.__LINE__, basename(__FILE__), __LINE__);
                goto PROC_END;
            }
        }
        chdir($curDir);
        
        $tcounter = 0;
        $process = proc_open($in_cmd, $descriptor, $pipes);
        if(is_resource($process)){
            while(True){
                usleep(CMD_WAIT_TIME);
                $status = proc_get_status($process);
                $pid = $status['pid'];
                $bActive = $status['running'];

                // 終了確認
                if($bActive === false) break;

                $tcounter += CMD_WAIT_TIME;
                if($tcounter >= CMD_TIMEOUT){
                    $this->kill($process);
                    fclose($pipes[0]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    proc_close($process);
                    $this->setError($ret = INTERNALERR_RTN_TIMEOUT, "<!> Exec cmd timeout.".__LINE__, basename(__FILE__), __LINE__);
                    goto PROC_END;
                }
            }
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
        }else{
            $this->setError($ret = INTERNALERR_RTN_LAUNCH_CMD, "<!> Launch cmd failed.".__LINE__, basename(__FILE__), __LINE__);
            goto PROC_END;
        }
        proc_close($process);
        $exitcode = $status['exitcode'];
        //var_dump($status);
        if($exitcode === CMD_RTN_UNAUTHORIZED){
            $this->setError($ret = INTERNALERR_RTN_SESSION_END, "<!> Exec cmd unauthorized.".__LINE__, basename(__FILE__), __LINE__);
            goto PROC_END;
        } elseif($exitcode === CMD_RTN_BAD_REQUEST){
            $this->setError($ret = INTERNALERR_RTN_INVALID_REQUEST, "<!> Exec cmd bad-request.".__LINE__, basename(__FILE__), __LINE__);
            goto PROC_END;
        } elseif($exitcode !== 0){
            if($in_ignore){
                $ret = INTERNALERR_RTN_EXEC_CMD;
            }else{
                $this->setError($ret = INTERNALERR_RTN_EXEC_CMD, "<!> Exec cmd failed.".__LINE__, basename(__FILE__), __LINE__);
            }
            goto PROC_END;
        }

PROC_END:
        chdir($orgCurDir);
        if($ret == 0){
            $out_stdout = $stdout;
        }else{
            $out_stderr = $stderr;
        }
        return $ret;
    }

    // コマンド起動
    public function procasync($in_cmd)
    {
    	$orgCurDir = getcwd();
        $ret = $this->getIntegRoot($root);
        if($ret != 0) goto PROCASYNC_END;
        $curDir = $root.RESIDENT_DIR;
        if(!file_exists($curDir)){
            $bRet = XCgiTools::mkdir($curDir);
            if(!$bRet){
                $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create dir failed. '.$curDir.' '.__LINE__, basename(__FILE__), __LINE__);
                goto PROCASYNC_END;
            }
        }
        chdir($curDir);

        $fp = popen('start '.$in_cmd, 'r');
        pclose($fp);

PROCASYNC_END:
        chdir($orgCurDir);
        return $ret;
    }
}

?>

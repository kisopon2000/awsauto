<?php

require_once "launcher/XCgiCommon.php";

class FortuneLauncher extends CommonLauncher
{
    // エントリーポイント
    public function run()
    {
        if(($ret = $this->prelude()) != 0) goto LAUNCHER_END;
        if(($ret = $this->interlude()) != 0) goto LAUNCHER_END;
        if(($ret = $this->postlude()) != 0) goto LAUNCHER_END;

LAUNCHER_END:

        if($this->isExistError()){
            XCgiResponseApi::redirect($this->getExternalError($this->getErrorId()), $this->getErrorId(), $this->getErrorMessage());
            $this->writeErrorLog();
            return $this->getErrorId();
        }
        return 0;
    }

    // 前処理
    public function prelude()
    {
        $this->_m_WORK_IN_FILE = 'input_Fortune.json';
        $this->_m_WORK_OUT_FILE = 'output_Fortune.json';

        if(($ret = parent::prelude()) != 0) return -1;
        
        $_REQUEST['X_CMD_ARGS'] = "-getfortune ";
        $get = XCgiRequestApi::get();
        $requestMethod = XCgiRequestApi::getRequestMethod();

        // 機能名確定
        switch($requestMethod){
        case 'GET':
            $_REQUEST['X_CMD_ARGS'] = "-getfortune ";
            // 入力JSON生成
            if(isset($get['employee_number'])){
                $inJsonTmp['employee_number'] = $get['employee_number'];
            }
            if(!empty($inJsonTmp)){
                $_REQUEST['X_CMD_IN_JSON'] = json_encode($inJsonTmp);
            }
            break;
        case 'POST':
            $_REQUEST['X_CMD_ARGS'] = "-postfortune ";
            break;
        default:
            $_REQUEST['X_CMD_ARGS'] = "-getfortune ";
        }
        return 0;
    }

    // 実処理
    public function interlude()
    {
        // コマンド取得
        $api = XCgiRequestApi::getApi();
        if(!isset($api)){
            $this->setError($ret = INTERNALERR_RTN_NOT_SET_CMD, '<!> Not exist request command. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // Arguments取得
        if(isset($_REQUEST['X_CMD_ARGS'])){
            $arguments = $_REQUEST['X_CMD_ARGS'];
        }

        // IntegRoot取得
        $ret = $this->getIntegRoot($root);
        if($ret != 0) goto INTERLUDE_END;

        // DocumentRoot取得
        $ret = $this->getDocumentRoot($documentRoot);
        if($ret != 0) goto INTERLUDE_END;

        // EmployeePoolRoot取得
        $ret = $this->getEmployeePoolRoot($employeePoolRoot);
        if($ret != 0) goto INTERLUDE_END;

        // セッションID取得
        $sessionID = $this->startSession();

        // INIから設定を取得
        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, '<!> Not exist INI file. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }
        if(array_key_exists($api, $inidata)){
            $module        = $inidata[$api]['Module'];
            $workDir       = $inidata[$api]['Work'];
            $workDir       = parse_url($workDir, PHP_URL_PATH);
            $workFullDir   = $root.$workDir;
            $workInFile    = $workFullDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile   = $workFullDir."/".$this->_m_WORK_OUT_FILE;
        }else{
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist INI section. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // パス設定
        $workFullDir = str_replace("__SESSION_ID__", $sessionID, $workFullDir);
        $workInFilePath = str_replace("__SESSION_ID__", $sessionID, $workInFile);
        $workOutFilePath = str_replace("__SESSION_ID__", $sessionID, $workOutFile);
        $employeePoolDir = $root.$employeePoolRoot;

        // IN出力先ディレクトリ作成
        if(!empty($_REQUEST['X_CMD_IN_JSON'])){
            if(!file_exists($workFullDir)){
                $bRet = XCgiTools::mkdir($workFullDir);
                if(!$bRet){
                    $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create work dir failed. '.$workFullDir.' '.__LINE__, basename(__FILE__), __LINE__);
                    goto INTERLUDE_END;
                }
                $isExistWorkDir = True;
            }
            // JSONファイル作成
            for($i = 0; $i < FS_RETRY_COUNT; $i++){
                if(file_put_contents($workInFilePath, $_REQUEST['X_CMD_IN_JSON'], LOCK_EX)) break;
                else usleep(FS_RETRY_WAIT_TIME);
            }
            $workInFileQuotedPath = "\"$workInFilePath\"";
        }else{
            $inArg = $workInFileQuotedPath = $workInFilePath = $workInFile = "";
        }

        // OUT出力先ディレクトリ作成
        if(!empty($_REQUEST['X_CMD_OUT_JSON'])){
            if(!file_exists($workFullDir)){
                $bRet = XCgiTools::mkdir($workFullDir);
                if(!$bRet){
                    $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create work dir failed. '.$workFullDir.' '.__LINE__, basename(__FILE__), __LINE__);
                    goto INTERLUDE_END;
                }
                $isExistWorkDir = True;
            }
            $workOutFileQuotedPath = "\"$workOutFilePath\"";
        }else{
            $outArg = $workOutFileQuotedPath = $workOutFilePath = $workOutFile = "";
        }

        // セッション終了
        $this->exitSession();

        // Employeeプール確認
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $employeeNumber = $request["employee_number"];
        $employeePoolDir = $employeePoolDir."/".$employeeNumber;
        //echo $employeePoolDir."\n";
        if(!file_exists($employeePoolDir)){
            $bRet = XCgiTools::mkdir($employeePoolDir);
            if(!$bRet){
                $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create employee pool dir failed. '.$employeePoolDir.' '.__LINE__, basename(__FILE__), __LINE__);
                goto INTERLUDE_END;
            }
        }
        
        // Fortune確認
        $isDone = false;
        $employeeFortuneFilePath = $employeePoolDir."/fortune.log";
        if(file_exists($employeeFortuneFilePath)){
            // 日付確認
            $now = date('Y-m-d');
            $fp = fopen($employeeFortuneFilePath, 'rb');
            $contents = stream_get_contents($fp);
            fclose($fp);
            if($now == $contents){
                $isDone = true;
            }
        }else{
            // ファイル作成
            if(!touch($employeeFortuneFilePath)){
                $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create employee fortune file failed. '.$employeeFortuneFilePath.' '.__LINE__, basename(__FILE__), __LINE__);
                goto INTERLUDE_END;
            }
        }
        $ret = $this->getResourceFortuneDir($resourceDir);
        if($ret != 0) goto INTERLUDE_END;
        $resourceDir = $root.$resourceDir;
        if($isDone){
            $resourceContextIndex = strval(mt_rand(FORTUNE_RSC_CONTEXT_MIN, FORTUNE_RSC_CONTEXT_MAX));
            $resourceIcon = $resourceDir."/".strval(FORTUNE_RSC_WORST).".png";
            $fp = fopen($resourceIcon, 'rb');
            $contents = stream_get_contents($fp);
            fclose($fp);
            $resourceContext = $resourceDir."/7_fuck.txt";
            $resourceContext = file($resourceContext);
            $resourceContext = $resourceContext[$resourceContextIndex];
        }else{
            $resourceIndex = strval(mt_rand(FORTUNE_RSC_MIN, FORTUNE_RSC_MAX));
            $resourceContextIndex = strval(mt_rand(FORTUNE_RSC_CONTEXT_MIN, FORTUNE_RSC_CONTEXT_MAX));
            $resourceIcon = $resourceDir."/".$resourceIndex.".png";
            $fp = fopen($resourceIcon, 'rb');
            $contents = stream_get_contents($fp);
            fclose($fp);
            $resourceContext = $resourceDir."/".$resourceIndex.".txt";
            $resourceContext = file($resourceContext);
            $resourceContext = $resourceContext[$resourceContextIndex];
        }

        // Employeeファイル更新
        $now = date('Y-m-d');
        $fp = fopen($employeeFortuneFilePath, 'wb');
        fwrite($fp, $now);
        fclose($fp);

        // 結果作成
        $response["result"] = 0;
        $response["resource"] = "data:image/png;base64,".base64_encode($contents);
        $response["context"] = $resourceContext;
        $response = json_encode($response);
        if(!file_put_contents($workOutFilePath, $response, LOCK_EX)){
            $this->setError($ret = INTERNALERR_RTN_READFILE_FAILED, '<!> Cannot put result. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // 結果を標準出力
        if(!empty($_REQUEST['X_CMD_OUT_JSON'])){
            if(!$this->isExistError()){
                $ret = XCgiTools::readfile($workOutFilePath);
                if(is_bool($ret)){
                    $boolStr = $ret ? 'True' : 'False';
                    $this->setError($ret = INTERNALERR_RTN_READFILE_FAILED, "<!> readfile() failed. return($boolStr) ".__LINE__, basename(__FILE__), __LINE__);
                    goto INTERLUDE_END;
                } else {
                    if($ret <= 0){
                        $length = $ret;
                        $this->setError($ret = INTERNALERR_RTN_READFILE_FAILED, "<!> readfile() failed. return($length) ".__LINE__, basename(__FILE__), __LINE__);
                        goto INTERLUDE_END;
                    }
                }
            }
        }

INTERLUDE_END:

        if(isset($isExistWorkDir) and $isExistWorkDir){
            // ワークディレクトリ削除
            //if(file_exists($workFullDir)){
                XCgiTools::unlink($workInFilePath);
                XCgiTools::unlink($workOutFilePath);
                XCgiTools::rmdir($workFullDir);
            //}
        }

        if($this->isExistError()){
            return $this->getErrorId();
        }

        return 0;
    }

}

?>
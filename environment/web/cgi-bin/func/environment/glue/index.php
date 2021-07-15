<?php

require_once "launcher/XCgiCommon.php";

class EnvironmentGlueLauncher extends CommonLauncher
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
        $this->_m_WORK_IN_FILE = 'input_EnvironmentGlue.json';
        $this->_m_WORK_OUT_FILE = 'output_EnvironmentGlue.json';

        if(($ret = parent::prelude()) != 0) return -1;
        
        $_REQUEST['X_CMD_ARGS'] = "-postenvironmentglue ";
        $get = XCgiRequestApi::get();
        $requestMethod = XCgiRequestApi::getRequestMethod();

        //機能名確定
        switch($requestMethod){
        case 'GET':
            $_REQUEST['X_CMD_ARGS'] = "-getenvironmentglue ";
            if(isset($get['employee_number'])){
                $inJsonTmp['employee_number'] = $get['employee_number'];
            }
            if(isset($get['id'])){
                $inJsonTmp['id'] = $get['id'];
            }
            if(!empty($inJsonTmp)){
                $_REQUEST['X_CMD_IN_JSON'] = json_encode($inJsonTmp);
            }
            if(isset($get['type'])){
                $inJsonTmp['type'] = $get['type'];
            }
            break;
        case 'POST':
            $_REQUEST['X_CMD_ARGS'] = "-postenvironmentglue ";
            break;
        case 'PUT':
            $_REQUEST['X_CMD_ARGS'] = "-putenvironmentglue ";
            break;
        case 'DELETE':
            $_REQUEST['X_CMD_ARGS'] = "-deleteenvironmentglue ";
            if(isset($get['employee_number'])){
                $inJsonTmp['employee_number'] = $get['employee_number'];
            }
            if(isset($get['id'])){
                $inJsonTmp['id'] = $get['id'];
            }
            if(isset($get['type'])){
                $inJsonTmp['type'] = $get['type'];
            }
            if(!empty($inJsonTmp)){
                $_REQUEST['X_CMD_IN_JSON'] = json_encode($inJsonTmp);
            }
            break;
        default:
            $_REQUEST['X_CMD_ARGS'] = "-getheartbeatglue ";
        }
        return 0;
    }

    // 実処理
    public function interlude()
    {
        $requestMethod = XCgiRequestApi::getRequestMethod();
        switch($requestMethod){
        case 'GET':
            return $this->_get();
        case 'POST':
            return $this->_post();
        case 'PUT':
            return $this->_put();
        case 'DELETE':
            return $this->_delete();
        default:
            return $this->_get();
        }
    }

    // GET
    private function _get()
    {
        // NOP
        return 0;
    }

    // POST
    private function _post()
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

        // IDPoolRoot取得
        $ret = $this->getIdPoolRoot($idPoolRoot);
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
            $module      = $inidata[$api]['Module'];
            $workDir     = $inidata[$api]['Work'];
            $workDir     = parse_url($workDir, PHP_URL_PATH);
            $workFullDir = $root.$workDir;
            $workInFile  = $workFullDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile = $workFullDir."/".$this->_m_WORK_OUT_FILE;
            // クラウド系
            $glueMachineLearningResource = $inidata[$api]['GlueMachineLearningResource'];
            $glueMachineLearningRole = $inidata[$api]['GlueMachineLearningRole'];
            $glueMachineLearningScriptLocation = $inidata[$api]['GlueMachineLearningScriptLocation'];
            $glueMachineLearningExtraFiles = $inidata[$api]['GlueMachineLearningExtraFiles'];
            $glueMachineLearningTempDir = $inidata[$api]['GlueMachineLearningTempDir'];
            $glueCrawlerResource = $inidata[$api]['GlueCrawlerResource'];
            $glueCrawlerRole = $inidata[$api]['GlueCrawlerRole'];
        }else{
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist INI section. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // パス設定
        $workFullDir = str_replace("__SESSION_ID__", $sessionID, $workFullDir);
        $workInFilePath = str_replace("__SESSION_ID__", $sessionID, $workInFile);
        $workOutFilePath = str_replace("__SESSION_ID__", $sessionID, $workOutFile);
        $idPoolDir = $root.$idPoolRoot;
        $glueMachineLearningResourceTemplatePath = $root.$glueMachineLearningResource;
        $glueMachineLearningResourceDir = dirname($glueMachineLearningResourceTemplatePath)."/temp";
        $glueMachineLearningResource = file_get_contents($glueMachineLearningResourceTemplatePath);
        if($glueMachineLearningResource === false){
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist json data. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }
        $glueCrawlerResourceTemplatePath = $root.$glueCrawlerResource;
        $glueCrawlerResourceDir = dirname($glueCrawlerResourceTemplatePath)."/temp";
        $glueCrawlerResource = file_get_contents($glueCrawlerResourceTemplatePath);
        if($glueCrawlerResource === false){
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist json data. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

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
        
        // IDプール取得
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $id = $request["id"];
        $type = $request["type"];
        $idPoolDir = $idPoolDir."/".$id;

        if($type == CLOUD_EXISTCHECK_QUEUETYPE_GLUE_MACHINE_LEARNING){
            // [Glue (machine-learning)] create-job
            $glueJobName = $id."_glue_machine_learning";
            $glueMachineLearningResource = sprintf($glueMachineLearningResource, $glueJobName, $glueMachineLearningRole, $glueMachineLearningScriptLocation, $glueMachineLearningExtraFiles, $glueMachineLearningTempDir);
            $rand = mt_rand(100000, 200000);
            $glueMachineLearningResourceFileName = $id."_".strval($rand).".json";
            $glueMachineLearningResourcePath = $glueMachineLearningResourceDir."/".$glueMachineLearningResourceFileName;
            file_put_contents($glueMachineLearningResourcePath, $glueMachineLearningResource, LOCK_EX);
            $ret = $this->getGlueCreateJob($glueMachineLearningResourcePath, $glueFuntion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($glueFuntion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_GLUE_DB){
            // [Glue (db)] create-db
            $glueDBName = $id."_glue_db";
            $ret = $this->getGlueCreateDBConfig($glueDBName, $glueDBConfig);
            if($ret != 0) goto INTERLUDE_END;
            $glueDBConfig = "\"$glueDBConfig\"";  // XCgi.iniでは両端にダブルクォーテーションが付与できないようなので…
            $ret = $this->getGlueCreateDB($glueDBConfig, $glueFuntion);
            if($ret != 0) goto INTERLUDE_END;
            //echo $glueFuntion."\n";
            $ret = $this->proc($glueFuntion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_GLUE_CRAWLER){
            // [Glue (crawler)] create-crawler
            $glueCrawlerName = $id."_glue_crawler";
            $glueCrawlerTargetS3Name = $id."-s3-datalake";
            $glueDBName = $id."_glue_db";
            $glueCrawlerResource = sprintf($glueCrawlerResource, $glueCrawlerTargetS3Name);
            $rand = mt_rand(100000, 200000);
            $glueCrawlerResourceFileName = $id."_".strval($rand).".json";
            $glueCrawlerResourcePath = $glueCrawlerResourceDir."/".$glueCrawlerResourceFileName;
            file_put_contents($glueCrawlerResourcePath, $glueCrawlerResource, LOCK_EX);
            $ret = $this->getGlueCreateCrawler($glueCrawlerName, $glueCrawlerRole, $glueDBName, $glueCrawlerResourcePath, $glueFuntion);
            if($ret != 0) goto INTERLUDE_END;
            //echo $glueFuntion."\n";
            $ret = $this->proc($glueFuntion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }else{
            $this->setError($ret = INTERNALERR_RTN_INVALID_REQUEST, '<!> Not support type. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // 結果作成
        $response["result"] = 0;
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
                XCgiTools::unlink($glueMachineLearningResourcePath);
                XCgiTools::unlink($glueCrawlerResourcePath);
                XCgiTools::rmdir($workFullDir);
            //}
        }

        if($this->isExistError()){
            return $this->getErrorId();
        }

        return 0;
    }

    // PUT
    private function _put()
    {
        // NOP
        return 0;
    }

    // DELETE
    private function _delete()
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

        // IDPoolRoot取得
        $ret = $this->getIdPoolRoot($idPoolRoot);
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
            $module      = $inidata[$api]['Module'];
            $workDir     = $inidata[$api]['Work'];
            $workDir     = parse_url($workDir, PHP_URL_PATH);
            $workFullDir = $root.$workDir;
            $workInFile  = $workFullDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile = $workFullDir."/".$this->_m_WORK_OUT_FILE;
            // クラウド系
            $glueMachineLearningResource = $inidata[$api]['GlueMachineLearningResource'];
            $glueMachineLearningRole = $inidata[$api]['GlueMachineLearningRole'];
            $glueMachineLearningScriptLocation = $inidata[$api]['GlueMachineLearningScriptLocation'];
            $glueMachineLearningExtraFiles = $inidata[$api]['GlueMachineLearningExtraFiles'];
            $glueMachineLearningTempDir = $inidata[$api]['GlueMachineLearningTempDir'];
            $glueCrawlerResource = $inidata[$api]['GlueCrawlerResource'];
            $glueCrawlerRole = $inidata[$api]['GlueCrawlerRole'];
        }else{
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist INI section. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // パス設定
        $workFullDir = str_replace("__SESSION_ID__", $sessionID, $workFullDir);
        $workInFilePath = str_replace("__SESSION_ID__", $sessionID, $workInFile);
        $workOutFilePath = str_replace("__SESSION_ID__", $sessionID, $workOutFile);
        $idPoolDir = $root.$idPoolRoot;
        $glueMachineLearningResourceTemplatePath = $root.$glueMachineLearningResource;
        $glueMachineLearningResourceDir = dirname($glueMachineLearningResourceTemplatePath)."/temp";
        $glueMachineLearningResource = file_get_contents($glueMachineLearningResourceTemplatePath);
        if ($glueMachineLearningResource === false) {
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist json data. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }
        $glueCrawlerResourceTemplatePath = $root.$glueCrawlerResource;
        $glueCrawlerResourceDir = dirname($glueCrawlerResourceTemplatePath)."/temp";
        $glueCrawlerResource = file_get_contents($glueCrawlerResourceTemplatePath);
        if($glueCrawlerResource === false){
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist json data. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

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
        
        // IDプール取得
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $id = $request["id"];
        $type = $request["type"];
        $idPoolDir = $idPoolDir."/".$id;

        if($type == CLOUD_EXISTCHECK_QUEUETYPE_GLUE_MACHINE_LEARNING){
            // [Glue (machine-learning)] delete-job
            $glueJobName = $id."_glue_machine_learning";
            $ret = $this->getGlueDeleteJob($glueJobName, $glueFuntion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($glueFuntion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_GLUE_DB){
            // [Glue (db)] delete-db
            $glueDBName = $id."_glue_db";
            $ret = $this->getGlueDeleteDB($glueDBName, $glueFuntion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($glueFuntion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_GLUE_CRAWLER){
            // [Glue (crawler)] delete-crawler
            $glueCrawlerName = $id."_glue_crawler";
            $ret = $this->getGlueDeleteCrawler($glueCrawlerName, $glueFuntion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($glueFuntion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }else{
            $this->setError($ret = INTERNALERR_RTN_INVALID_REQUEST, '<!> Not support type. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // 結果作成
        $response["result"] = 0;
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
                XCgiTools::unlink($glueMachineLearningResourcePath);
                XCgiTools::unlink($glueCrawlerResourcePath);
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
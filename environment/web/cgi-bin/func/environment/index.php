<?php

require_once "launcher/XCgiCommon.php";

class EnvironmentLauncher extends CommonLauncher
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
        $this->_m_WORK_IN_FILE = 'input_Environment.json';
        $this->_m_WORK_OUT_FILE = 'output_Environment.json';

        if(($ret = parent::prelude()) != 0) return -1;
        
        $_REQUEST['X_CMD_ARGS'] = "-postenvironment ";
        $get = XCgiRequestApi::get();
        $requestMethod = XCgiRequestApi::getRequestMethod();

        // 機能名確定
        switch($requestMethod){
        case 'GET':
            $_REQUEST['X_CMD_ARGS'] = "-getenvironment ";
            if(isset($get['employee_number'])){
                $inJsonTmp['employee_number'] = $get['employee_number'];
            }
            if(isset($get['id'])){
                $inJsonTmp['id'] = $get['id'];
            }
            if(!empty($inJsonTmp)){
                $_REQUEST['X_CMD_IN_JSON'] = json_encode($inJsonTmp);
            }
            break;
        case 'POST':
            $_REQUEST['X_CMD_ARGS'] = "-postenvironment ";
            break;
        case 'PUT':
            $_REQUEST['X_CMD_ARGS'] = "-putenvironment ";
            break;
        case 'DELETE':
            $_REQUEST['X_CMD_ARGS'] = "-deleteenvironment ";
            if(isset($get['employee_number'])){
                $inJsonTmp['employee_number'] = $get['employee_number'];
            }
            if(isset($get['id'])){
                $inJsonTmp['id'] = $get['id'];
            }
            if(!empty($inJsonTmp)){
                $_REQUEST['X_CMD_IN_JSON'] = json_encode($inJsonTmp);
            }
            break;
        default:
            $_REQUEST['X_CMD_ARGS'] = "-getheartbeat ";
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
            $module      = $inidata[$api]['Module'];
            $workDir     = $inidata[$api]['Work'];
            $workDir     = parse_url($workDir, PHP_URL_PATH);
            $workFullDir = $root.$workDir;
            $workInFile  = $workFullDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile = $workFullDir."/".$this->_m_WORK_OUT_FILE;
            // クラウド系
            $lambdaUploadRole    = $inidata[$api]['LambdaUploadRole'];
            $lambdaUploadHandler = $inidata[$api]['LambdaUploadHandler'];
            $lambdaUploadCode    = $inidata[$api]['LambdaUploadCode'];
            $lambdaUploadTimeout = $inidata[$api]['LambdaUploadTimeout'];
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

        $queue = array();

        // Employee別Environment確認
        $ret = INTERNALERR_RTN_NONE;
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $employeeNumber = $request["employee_number"];
        $id = $request["id"];
        $environmentDir = $employeePoolDir."/".$employeeNumber."/environment";
        //echo $environmentDir."\n";
        $environments = array();
        if(file_exists($environmentDir)){
            $files = glob($environmentDir."/*");
            $bExist = false;
            foreach($files as &$file){
                $pathData = pathinfo($file);
                if($pathData["filename"] == $id){
                    $bExist = true;
                    break;
                }
            }
            if(!$bExist){
                // 当該EmployeeにはEnvironmentは現状存在しない
                $ret = INTERNALERR_RTN_NOT_FOUND_FILE;
                goto GET_END;
            }
        }else{
            // 当該EmployeeにはEnvironmentは現状存在しない
            $ret = INTERNALERR_RTN_NOT_FOUND_FILE;
            goto GET_END;
        }

        // [S3 (upload)] get-bucket
        $s3Name = $id."-s3-upload";
        $ret = $this->getS3GetBucket($s3Name, $s3Bucket);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($s3Bucket, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_UPLOAD);
        }

        // [S3 (datalake)] get-bucket
        $s3Name = $id."-s3-datalake";
        $ret = $this->getS3GetBucket($s3Name, $s3Bucket);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($s3Bucket, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_DATALAKE);
        }

        // [Lambda (upload)] get-function
        $lambdaName = $id."_lambda_upload";
        $ret = $this->getLambdaGetFuntion($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_UPLOAD);
        }

        // [Lambda (machine-learning)] get-function
        $lambdaName = $id."_lambda_machine_learning";
        $ret = $this->getLambdaGetFuntion($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_MACHINE_LEARNING);
        }

        // [Glue (machine-learning)] get-job
        $glueJobName = $id."_glue_machine_learning";
        $ret = $this->getGlueGetJob($glueJobName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_MACHINE_LEARNING);
        }

        // [Glue (db)] get-db
        $glueDBName = $id."_glue_db";
        $ret = $this->getGlueGetDB($glueDBName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_DB);
        }

        // [Glue (crawler)] get-crawler
        $glueCrawlerName = $id."_glue_crawler";
        $ret = $this->getGlueGetCrawler($glueCrawlerName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_CRAWLER);
        }

        // [Lambda (policy)] get-policy
        $lambdaName = $id."_lambda_upload";
        $ret = $this->getLambdaGetPolicy($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_PERMISSION);
        }

        // [S3 (notification)] 無条件で積む
        $s3Name = $id."-s3-upload";
        $ret = $this->getS3GetNotification($s3Name, $s3Funtion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($s3Funtion, true, $stdout, $stderr);
        if($ret != 0){
            goto INTERLUDE_END;
        }else{
            if(!$stdout){
                array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_NOTIFICATION);
            }
        }

GET_END:

        // 結果作成
        $response["result"] = $ret;
        $response["environment_types"] = $queue;
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
            $module      = $inidata[$api]['Module'];
            $workDir     = $inidata[$api]['Work'];
            $workDir     = parse_url($workDir, PHP_URL_PATH);
            $workFullDir = $root.$workDir;
            $workInFile  = $workFullDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile = $workFullDir."/".$this->_m_WORK_OUT_FILE;
            // クラウド系
            $lambdaUploadRole    = $inidata[$api]['LambdaUploadRole'];
            $lambdaUploadHandler = $inidata[$api]['LambdaUploadHandler'];
            $lambdaUploadCode    = $inidata[$api]['LambdaUploadCode'];
            $lambdaUploadTimeout = $inidata[$api]['LambdaUploadTimeout'];
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
        
        // Employee別Environment確認
        $ret = INTERNALERR_RTN_NONE;
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $employeeNumber = $request["employee_number"];
        $id = $request["id"];
        $customer = $request["customer"];
        $memo = $request["memo"];
        $environmentDir = $employeePoolDir."/".$employeeNumber."/environment";
        //echo $environmentDir."\n";
        $environments = array();
        if(!file_exists($environmentDir)){
            $bRet = XCgiTools::mkdir($environmentDir);
            if(!$bRet){
                $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create environment dir failed. '.$environmentDir.' '.__LINE__, basename(__FILE__), __LINE__);
                goto INTERLUDE_END;
            }
        }
        $files = glob($environmentDir."/*");
        $bExist = false;
        foreach($files as &$file){
            $pathData = pathinfo($file);
            if($pathData["filename"] == $id){
                $bExist = true;
                break;
            }
        }
        if(!$bExist){
            $file = $environmentDir."/".$id.".ini";
        }
        $data = array('COMMON' => array('Customer' => $customer, 'Memo' => $memo));
        if(!$this->_write_ini_file($data, $file)){
            $this->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, '<!> Cannot write environment INI file. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        $queue = array();

        // [S3 (upload)] get-bucket
        $s3Name = $id."-s3-upload";
        $ret = $this->getS3GetBucket($s3Name, $s3Bucket);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($s3Bucket, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_UPLOAD);
        }

        // [S3 (datalake)] get-bucket
        $s3Name = $id."-s3-datalake";
        $ret = $this->getS3GetBucket($s3Name, $s3Bucket);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($s3Bucket, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_DATALAKE);
        }

        // [Lambda (upload)] get-function
        $lambdaName = $id."_lambda_upload";
        $ret = $this->getLambdaGetFuntion($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_UPLOAD);
        }

        // [Lambda (machine-learning)] get-function
        $lambdaName = $id."_lambda_machine_learning";
        $ret = $this->getLambdaGetFuntion($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_MACHINE_LEARNING);
        }

        // [Glue (machine-learning)] get-job
        $glueJobName = $id."_glue_machine_learning";
        $ret = $this->getGlueGetJob($glueJobName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_MACHINE_LEARNING);
        }

        // [Glue (db)] get-db
        $glueDBName = $id."_glue_db";
        $ret = $this->getGlueGetDB($glueDBName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_DB);
        }

        // [Glue (crawler)] get-crawler
        $glueCrawlerName = $id."_glue_crawler";
        $ret = $this->getGlueGetCrawler($glueCrawlerName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_CRAWLER);
        }

        // [Lambda (policy)] get-policy
        $lambdaName = $id."_lambda_upload";
        $ret = $this->getLambdaGetPolicy($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret != 0){
            // 存在しないと判断
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_PERMISSION);
        }

        // [S3 (notification)] 無条件で積む
        array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_NOTIFICATION);

        // 結果作成
        $response["result"] = 0;
        $response["environment_types"] = $queue;
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

    // PUT
    private function _put()
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
            $module      = $inidata[$api]['Module'];
            $workDir     = $inidata[$api]['Work'];
            $workDir     = parse_url($workDir, PHP_URL_PATH);
            $workFullDir = $root.$workDir;
            $workInFile  = $workFullDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile = $workFullDir."/".$this->_m_WORK_OUT_FILE;
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

        // Employee別Environment確認
        $ret = INTERNALERR_RTN_NONE;
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $employeeNumber = $request["employee_number"];
        $id = $request["id"];
        $customer = $request["customer"];
        $memo = $request["memo"];
        $environmentDir = $employeePoolDir."/".$employeeNumber."/environment";
        //echo $environmentDir."\n";
        $environments = array();
        if(file_exists($environmentDir)){
            $files = glob($environmentDir."/*");
            $bExist = false;
            foreach($files as &$file){
                $pathData = pathinfo($file);
                if($pathData["filename"] == $id){
                    $bExist = true;
                    break;
                }
            }
            if($bExist){
                $data = array('COMMON' => array('Customer' => $customer, 'Memo' => $memo));
                if(!$this->_write_ini_file($data, $file)){
                    $this->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, '<!> Cannot write environment INI file. '.__LINE__, basename(__FILE__), __LINE__);
                    goto INTERLUDE_END;
                }
            }else{
                // 当該EmployeeにはEnvironmentは現状存在しない
                $ret = INTERNALERR_RTN_NOT_FOUND_FILE;
            }
        }else{
            // 当該EmployeeにはEnvironmentは現状存在しない
            $ret = INTERNALERR_RTN_NOT_FOUND_FILE;
        }

        // 結果作成
        $response["result"] = $ret;
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
            $module      = $inidata[$api]['Module'];
            $workDir     = $inidata[$api]['Work'];
            $workDir     = parse_url($workDir, PHP_URL_PATH);
            $workFullDir = $root.$workDir;
            $workInFile  = $workFullDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile = $workFullDir."/".$this->_m_WORK_OUT_FILE;
            // クラウド系
            $lambdaUploadRole    = $inidata[$api]['LambdaUploadRole'];
            $lambdaUploadHandler = $inidata[$api]['LambdaUploadHandler'];
            $lambdaUploadCode    = $inidata[$api]['LambdaUploadCode'];
            $lambdaUploadTimeout = $inidata[$api]['LambdaUploadTimeout'];
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
        
        // Employee別Environment確認
        $ret = INTERNALERR_RTN_NONE;
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $employeeNumber = $request["employee_number"];
        $id = $request["id"];
        $environmentDir = $employeePoolDir."/".$employeeNumber."/environment";
        //echo $environmentDir."\n";
        $environments = array();
        if(file_exists($environmentDir)){
            $files = glob($environmentDir."/*");
            $bExist = false;
            foreach($files as &$file){
                $pathData = pathinfo($file);
                if($pathData["filename"] == $id){
                    $bExist = true;
                    break;
                }
            }
            if($bExist){
                // 存在する
            }else{
                // 当該EmployeeにはEnvironmentは現状存在しない
                $ret = INTERNALERR_RTN_NOT_FOUND_FILE;
                goto METHOD_END;
            }
        }else{
            // 当該EmployeeにはEnvironmentは現状存在しない
            $ret = INTERNALERR_RTN_NOT_FOUND_FILE;
            goto METHOD_END;
        }

        $queue = array();

        // [S3 (upload)] get-bucket
        $s3Name = $id."-s3-upload";
        $ret = $this->getS3GetBucket($s3Name, $s3Bucket);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($s3Bucket, true, $stdout, $stderr);
        if($ret == 0){
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_UPLOAD);
        }

        // [S3 (datalake)] get-bucket
        $s3Name = $id."-s3-datalake";
        $ret = $this->getS3GetBucket($s3Name, $s3Bucket);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($s3Bucket, true, $stdout, $stderr);
        if($ret == 0){
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_S3_DATALAKE);
        }

        // [Lambda (upload)] get-function
        $lambdaName = $id."_lambda_upload";
        $ret = $this->getLambdaGetFuntion($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret == 0){
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_UPLOAD);
        }

        // [Lambda (machine-learning)] get-function
        $lambdaName = $id."_lambda_machine_learning";
        $ret = $this->getLambdaGetFuntion($lambdaName, $lambdaFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($lambdaFuntion, true, $stdout, $stderr);
        if($ret == 0){
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_MACHINE_LEARNING);
        }

        // [Glue (machine-learning)] get-job
        $glueJobName = $id."_glue_machine_learning";
        $ret = $this->getGlueGetJob($glueJobName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret == 0){
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_MACHINE_LEARNING);
        }

        // [Glue (db)] get-db
        $glueDBName = $id."_glue_db";
        $ret = $this->getGlueGetDB($glueDBName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret == 0){
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_DB);
        }

        // [Glue (crawler)] get-crawler
        $glueCrawlerName = $id."_glue_crawler";
        $ret = $this->getGlueGetCrawler($glueCrawlerName, $glueFuntion);
        if($ret != 0) goto INTERLUDE_END;
        $ret = $this->proc($glueFuntion, true, $stdout, $stderr);
        if($ret == 0){
            array_push($queue, CLOUD_EXISTCHECK_QUEUETYPE_GLUE_CRAWLER);
        }

        $ret = 0;  // リセット

METHOD_END:

        // 結果作成
        $response["result"] = $ret;
        $response["environment_types"] = $queue;
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

    private function _write_ini_file($in_array, $in_path)
    {
        $content = "";
        foreach($in_array as $key => $elem){
            $content .= "[" . $key . "]\n";
            foreach($elem as $key2 => $elem2){
                if(is_array($elem2)){
                    for($i = 0; $i < count($elem2); $i++){
                        $content .= $key2 . "[] = \"" . $elem2[$i] . "\"\n";
                    }
                }else if ($elem2 == ""){
                    $content .= $key2 . " = \n";
                }else{
                    $content .= $key2 . " = \"" . $elem2 . "\"\n";
                }
            }
        }
        if(!$handle = fopen($in_path, 'w')){
            return false;
        }
        if(!fwrite($handle, $content)){
            return false;
        }
        fclose($handle);

        return true;
    }
}

?>
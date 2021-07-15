<?php

require_once "launcher/XCgiCommon.php";

class EnvironmentS3Launcher extends CommonLauncher
{
    // �G���g���[�|�C���g
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

    // �O����
    public function prelude()
    {
        $this->_m_WORK_IN_FILE = 'input_EnvironmentS3.json';
        $this->_m_WORK_OUT_FILE = 'output_EnvironmentS3.json';

        if(($ret = parent::prelude()) != 0) return -1;
        
        $_REQUEST['X_CMD_ARGS'] = "-postenvironments3 ";
        $get = XCgiRequestApi::get();
        $requestMethod = XCgiRequestApi::getRequestMethod();

        //�@�\���m��
        switch($requestMethod){
        case 'GET':
            $_REQUEST['X_CMD_ARGS'] = "-getenvironments3 ";
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
        case 'POST':
            $_REQUEST['X_CMD_ARGS'] = "-postenvironments3 ";
            break;
        case 'PUT':
            $_REQUEST['X_CMD_ARGS'] = "-putenvironments3 ";
            break;
        case 'DELETE':
            $_REQUEST['X_CMD_ARGS'] = "-deleteenvironments3 ";
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
            $_REQUEST['X_CMD_ARGS'] = "-getheartbeats3 ";
        }
        return 0;
    }

    // ������
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
        // �R�}���h�擾
        $api = XCgiRequestApi::getApi();
        if(!isset($api)){
            $this->setError($ret = INTERNALERR_RTN_NOT_SET_CMD, '<!> Not exist request command. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // Arguments�擾
        if(isset($_REQUEST['X_CMD_ARGS'])){
            $arguments = $_REQUEST['X_CMD_ARGS'];
        }

        // IntegRoot�擾
        $ret = $this->getIntegRoot($root);
        if($ret != 0) goto INTERLUDE_END;

        // DocumentRoot�擾
        $ret = $this->getDocumentRoot($documentRoot);
        if($ret != 0) goto INTERLUDE_END;

        // IDPoolRoot�擾
        $ret = $this->getIdPoolRoot($idPoolRoot);
        if($ret != 0) goto INTERLUDE_END;

        // �Z�b�V����ID�擾
        $sessionID = $this->startSession();

        // INI����ݒ���擾
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

        // �p�X�ݒ�
        $workFullDir = str_replace("__SESSION_ID__", $sessionID, $workFullDir);
        $workInFilePath = str_replace("__SESSION_ID__", $sessionID, $workInFile);
        $workOutFilePath = str_replace("__SESSION_ID__", $sessionID, $workOutFile);
        $idPoolDir = $root.$idPoolRoot;

        // IN�o�͐�f�B���N�g���쐬
        if(!empty($_REQUEST['X_CMD_IN_JSON'])){
            if(!file_exists($workFullDir)){
                $bRet = XCgiTools::mkdir($workFullDir);
                if(!$bRet){
                    $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create work dir failed. '.$workFullDir.' '.__LINE__, basename(__FILE__), __LINE__);
                    goto INTERLUDE_END;
                }
                $isExistWorkDir = True;
            }
            // JSON�t�@�C���쐬
            for($i = 0; $i < FS_RETRY_COUNT; $i++){
                if(file_put_contents($workInFilePath, $_REQUEST['X_CMD_IN_JSON'], LOCK_EX)) break;
                else usleep(FS_RETRY_WAIT_TIME);
            }
            $workInFileQuotedPath = "\"$workInFilePath\"";
        }else{
            $inArg = $workInFileQuotedPath = $workInFilePath = $workInFile = "";
        }

        // OUT�o�͐�f�B���N�g���쐬
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

        // �Z�b�V�����I��
        $this->exitSession();
        
        // ID�v�[���擾
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $id = $request["id"];
        $type = $request["type"];
        $idPoolDir = $idPoolDir."/".$id;

        if($type == CLOUD_EXISTCHECK_QUEUETYPE_S3_UPLOAD){
            // [S3 (upload)] create-bucket
            $s3Name = $id."-s3-upload";
            $ret = $this->getS3CreateBucket($s3Name, $s3Funtion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($s3Funtion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_S3_NOTIFICATION){
            // [S3 (notification)] notification
            $lambdaName = $id."_lambda_upload";
            $ret = $this->getS3NotificationConfig($lambdaName, $s3NotificationConfig);
            if($ret != 0) goto INTERLUDE_END;
            $s3NotificationConfig = "\"$s3NotificationConfig\"";  // XCgi.ini�ł͗��[�Ƀ_�u���N�H�[�e�[�V�������t�^�ł��Ȃ��悤�Ȃ̂Łc
            $s3Name = $id."-s3-upload";
            $ret = $this->getS3Notification($s3Name, $s3NotificationConfig, $s3Funtion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($s3Funtion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_S3_DATALAKE){
            // [S3 (datalake)] create-bucket
            $s3Name = $id."-s3-datalake";
            $ret = $this->getS3CreateBucket($s3Name, $s3Funtion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($s3Funtion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }else{
            $this->setError($ret = INTERNALERR_RTN_INVALID_REQUEST, '<!> Not support type. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // ���ʍ쐬
        $response["result"] = 0;
        $response = json_encode($response);
        if(!file_put_contents($workOutFilePath, $response, LOCK_EX)){
            $this->setError($ret = INTERNALERR_RTN_READFILE_FAILED, '<!> Cannot put result. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // ���ʂ�W���o��
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
            // ���[�N�f�B���N�g���폜
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
        // NOP
        return 0;
    }

    // DELETE
    private function _delete()
    {
        // �R�}���h�擾
        $api = XCgiRequestApi::getApi();
        if(!isset($api)){
            $this->setError($ret = INTERNALERR_RTN_NOT_SET_CMD, '<!> Not exist request command. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // Arguments�擾
        if(isset($_REQUEST['X_CMD_ARGS'])){
            $arguments = $_REQUEST['X_CMD_ARGS'];
        }

        // IntegRoot�擾
        $ret = $this->getIntegRoot($root);
        if($ret != 0) goto INTERLUDE_END;

        // DocumentRoot�擾
        $ret = $this->getDocumentRoot($documentRoot);
        if($ret != 0) goto INTERLUDE_END;

        // IDPoolRoot�擾
        $ret = $this->getIdPoolRoot($idPoolRoot);
        if($ret != 0) goto INTERLUDE_END;

        // �Z�b�V����ID�擾
        $sessionID = $this->startSession();

        // INI����ݒ���擾
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

        // �p�X�ݒ�
        $workFullDir = str_replace("__SESSION_ID__", $sessionID, $workFullDir);
        $workInFilePath = str_replace("__SESSION_ID__", $sessionID, $workInFile);
        $workOutFilePath = str_replace("__SESSION_ID__", $sessionID, $workOutFile);
        $idPoolDir = $root.$idPoolRoot;

        // IN�o�͐�f�B���N�g���쐬
        if(!empty($_REQUEST['X_CMD_IN_JSON'])){
            if(!file_exists($workFullDir)){
                $bRet = XCgiTools::mkdir($workFullDir);
                if(!$bRet){
                    $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create work dir failed. '.$workFullDir.' '.__LINE__, basename(__FILE__), __LINE__);
                    goto INTERLUDE_END;
                }
                $isExistWorkDir = True;
            }
            // JSON�t�@�C���쐬
            for($i = 0; $i < FS_RETRY_COUNT; $i++){
                if(file_put_contents($workInFilePath, $_REQUEST['X_CMD_IN_JSON'], LOCK_EX)) break;
                else usleep(FS_RETRY_WAIT_TIME);
            }
            $workInFileQuotedPath = "\"$workInFilePath\"";
        }else{
            $inArg = $workInFileQuotedPath = $workInFilePath = $workInFile = "";
        }

        // OUT�o�͐�f�B���N�g���쐬
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

        // �Z�b�V�����I��
        $this->exitSession();
        
        // ID�v�[���擾
        $request = json_decode($_REQUEST['X_CMD_IN_JSON'], true);
        $id = $request["id"];
        $type = $request["type"];
        $idPoolDir = $idPoolDir."/".$id;

        if($type == CLOUD_EXISTCHECK_QUEUETYPE_S3_UPLOAD){
            // [S3 (upload)] delete-bucket
            $s3Name = $id."-s3-upload";
            $ret = $this->getS3DeleteBucket($s3Name, $s3Funtion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($s3Funtion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_S3_NOTIFICATION){
            // [S3 (notification)] notification
            // NOP
        }elseif($type == CLOUD_EXISTCHECK_QUEUETYPE_S3_DATALAKE){
            // [S3 (datalake)] delete-bucket
            $s3Name = $id."-s3-datalake";
            $ret = $this->getS3DeleteBucket($s3Name, $s3Funtion);
            if($ret != 0) goto INTERLUDE_END;
            $ret = $this->proc($s3Funtion, false, $stdout, $stderr);
            if($ret != 0) goto INTERLUDE_END;
        }else{
            $this->setError($ret = INTERNALERR_RTN_INVALID_REQUEST, '<!> Not support type. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // ���ʍ쐬
        $response["result"] = 0;
        $response = json_encode($response);
        if(!file_put_contents($workOutFilePath, $response, LOCK_EX)){
            $this->setError($ret = INTERNALERR_RTN_READFILE_FAILED, '<!> Cannot put result. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // ���ʂ�W���o��
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
            // ���[�N�f�B���N�g���폜
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
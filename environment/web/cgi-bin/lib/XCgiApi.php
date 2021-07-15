<?php

require_once dirname(__FILE__)."/XCgiDefines.php";

class XCgiStatusApi
{
    private $_m_errId;
    private $_m_errMessage;
    private $_m_errFile;
    private $_m_errLine;

    // コンストラクタ
    public function __construct()
    {
        $this->_m_errId = 0;
        $this->_m_errLine = 0;
        $this->_m_errMessage = "";
        $this->_m_errFile = "";
    }

    // エラー存在確認
    public function isExistError()
    {
        if($this->_m_errId != 0){
            return True;
        }
        return False;
    }

    // エラー情報設定
    public function setError($in_id, $in_message, $in_file="", $in_line=0)
    {
        $this->_m_errId = $in_id;
        $this->_m_errMessage = $in_message;
        $this->_m_errFile = $in_file;
        $this->_m_errLine = $in_line;
    }

    // エラーID取得
    public function getErrorId()
    {
        return $this->_m_errId;
    }

    // エラーメッセージ取得
    public function getErrorMessage()
    {
        return $this->_m_errMessage;
    }

    // エラー発生ファイル取得
    public function getErrorFile()
    {
        return $this->_m_errFile;
    }

    // エラー発生ライン取得
    public function getErrorLine()
    {
        return $this->_m_errLine;
    }

    // 外部用エラーコード取得
    public function getExternalError($in_internalError)
    {
        switch($in_internalError){
        case INTERNALERR_RTN_INVALID_REQUEST:
            return ERR_RTN_BAD_REQUEST;
        case INTERNALERR_RTN_SESSION_END:
            return ERR_RTN_UNAUTHORIZED;
        case INTERNALERR_RTN_ORG_URI_NOT_FOUND:
        case INTERNALERR_RTN_EXCEPTION:
        case INTERNALERR_RTN_NOT_SET_CMD:
        case INTERNALERR_RTN_NOT_SET_MODULE:
        case INTERNALERR_RTN_TIMEOUT:
        case INTERNALERR_RTN_LAUNCH_CMD:
        case INTERNALERR_RTN_EXEC_CMD:
        case INTERNALERR_RTN_NOT_FOUND_FILE:
        case INTERNALERR_RTN_CANNOT_GET_DATA:
        case INTERNALERR_RTN_MKDIR_FAILED:
        case INTERNALERR_RTN_RMDIR_FAILED:
        default:
            return ERR_RTN_INTERNAL_SERVER_ERROR;
        }
    }
}

class XCgiRequestApi
{
    public static function getRequestMethod()
    {
        return  $_REQUEST['XCGI_SYS_REQUEST_METHOD'];
    }

    public static function getOrgUri()
    {
        return  $_REQUEST['XCGI_SYS_ORG_URI'];
    }

    public static function getApi()
    {
        return $_REQUEST['XCGI_SYS_API'];
    }

    public static function getQueryString()
    {
        return $_REQUEST['XCGI_SYS_QUERY_STRING'];
    }

    public static function getStdin()
    {
        return $_REQUEST['XCGI_SYS_STDIN'];
    }

    public static function getContentLength()
    {
        return $_REQUEST['XCGI_SYS_CONTENT_LENGTH'];
    }

    public static function getClientIPAddress()
    {
        return $_REQUEST['XCGI_SYS_CLIENT_IP_ADDRESS'];
    }

    public static function get()
    {
        return $_REQUEST['XCGI_SYS_GET'];
    }

    public static function post()
    {
        return $_REQUEST['XCGI_SYS_POST'];
    }
}

class XCgiResponseApi
{
    public static function redirect($in_resCode, $in_id, $in_message)
    {
        //XCgiErrorLogApi::write(basename(__FILE__).':'.__LINE__.':'. 'session_id()='.session_id().'$_COOKIE='.print_r($_COOKIE, true));
        //XCgiErrorLogApi::write(basename(__FILE__).':'.__LINE__.':'. 'session_id()='.session_id().'$_SESSION='.print_r($_SESSION, true));
    	http_response_code($in_resCode);
        $exception = array(
            "id" => $in_id,
            "message" => $in_message,
        );
        $exception = json_encode($exception);
        echo $exception;
    }
}

class XCgiErrorLogApi
{
    public static function write($in_message)
    {
    	$log_file = WIN_TEMP_FOLDER.PHP_LOGFILE;
    	$log_time = date('Y-m-d H:i:s');
    	error_log('['.$log_time.'] '.$in_message.PHP_EOL, 3, $log_file);
    }
}

class XCgiEnvApi
{
    private $_m_cStatusApi;
    private $_m_documentroot;
    private $_m_integRoot;
    private $_m_systemLib;
    private $_m_basicSubSystem;
    private $_m_sysOptDir;
    private $_m_serviceName;
    private $_m_basicSystem;
    private $_m_systemId;
    private $_m_treeId;
    private $_m_phase;
    private $_m_product;

    // コンストラクタ
    public function __construct(&$in_cStatusApi)
    {
        $this->_m_cStatusApi = $in_cStatusApi;
        $this->_m_documentRoot = "";
        $this->_m_integRoot = "";
        $this->_m_systemLib = "";
        $this->_m_basicSubSystem = "";
        $this->_m_serviceName = "";
        $this->_m_basicSystem = "";
        $this->_m_systemId = "";
        $this->_m_treeId = "";
        $this->_m_phase = "";
        $this->_m_product = "";
        $this->_m_idPoolRoot = "";
        $this->_m_employeePoolRoot = "";
        $this->_m_resourceRoot = "";
    }

    // 環境情報取得
    public function getEnvInfo(&$out_integRoot, &$out_systemLib, &$out_basicSubSystem, &$out_sysOptDir, &$out_serviceName, &$out_basicSystem, &$out_phase, &$out_product, &$out_idPoolRoot, &$out_employeePoolRoot)
    {
        $ret = 0;

        // IntegRoot取得
        if(($ret = $this->getIntegRoot($out_integRoot)) != 0) return $ret;

        // SystemLib取得
        if(($ret = $this->getSystemLib($out_systemLib)) != 0) return $ret;

        // BasicSubSystem取得
        if(($ret = $this->getBasicSubSystem($out_basicSubSystem)) != 0) return $ret;

        // SysOptDir取得
        if(($ret = $this->getSysOptDir($out_sysOptDir)) != 0) return $ret;

        // ServiceName取得
        if(($ret = $this->getServiceName($out_serviceName)) != 0) return $ret;

        // BasicSystem取得
        if(($ret = $this->getBasicSystem($out_basicSystem)) != 0) return $ret;

        // Phase取得
        if(($ret = $this->getPhase($out_phase)) != 0) return $ret;

        // Product取得
        if(($ret = $this->getProduct($out_product)) != 0) return $ret;
        
        // Pool取得
        if(($ret = $this->getIdPoolRoot($out_idPoolRoot)) != 0) return $ret;

        // Employee取得
        if(($ret = $this->getEmployeePoolRoot($out_employeePoolRoot)) != 0) return $ret;

        return $ret;
    }

    // IntegRoot取得
    public function getIntegRoot(&$out_integRoot)
    {
        $ret = 0;
        $integRoot = dirname(__FILE__)."/../../..";
        $integRoot = realpath($integRoot);
        $integRoot = str_replace('\\', '/', $integRoot);
        $out_integRoot = $integRoot;

        return $ret;
    }

    // DocumentRoot取得
    public function getDocumentRoot(&$out_documentRoot)
    {
        $ret = 0;
        $documentRoot = dirname(__FILE__)."/../..";
        $documentRoot = realpath($documentRoot);
        $documentRoot = str_replace('\\', '/', $documentRoot);
        $documentRoot = $documentRoot."/contents";
        $out_documentRoot = $documentRoot;

        return $ret;
    }

    // Phase取得
    public function getPhase(&$out_phase)
    {
        $ret = 0;
        if(!empty($this->_m_phase)){
            $out_phase = $this->_m_phase;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_phase = $this->_m_phase = $inidata['env']['Phase'];
        if(empty($out_phase)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get phase.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // Product取得
    public function getProduct(&$out_product)
    {
        $ret = 0;
        if(!empty($this->_m_product)){
            $out_product = $this->_m_product;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_product = $this->_m_product = $inidata['env']['Product'];
        if(empty($out_product)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get product.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // SystemLib取得
    public function getSystemLib(&$out_systemLib)
    {
        $ret = 0;
        if(!empty($this->_m_systemLib)){
            $out_systemLib = $this->_m_systemLib;
            return $ret;
        }

        $root = "";
        if(($ret = $this->getIntegRoot($root)) != 0) return $ret;
        $out_systemLib = $this->_m_systemLib = $root."/system/lib";

        return $ret;
    }

    // BasicSubSystemパス取得
    public function getBasicSubSystem(&$out_basicSubSystem)
    {
        $ret = 0;
        if(!empty($this->_m_basicSubSystem)){
            $out_basicSubSystem = $this->_m_basicSubSystem;
            return $ret;
        }

        $root = "";
        if(($ret = $this->getIntegRoot($root)) != 0) return $ret;
        
        // BasicSubSystem
        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_basicSubSystem = $this->_m_basicSubSystem = $root.$inidata['env']['BasicSubsystemFile'];
        if(empty($out_basicSubSystem)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get basic sub system.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // BasicSystemパス取得
    public function getBasicSystem(&$out_basicSystem)
    {
        $ret = 0;
        if(!empty($this->_m_basicSystem)){
            $out_basicSystem = $this->_m_basicSystem;
            return $ret;
        }

        $root = "";
        if(($ret = $this->getIntegRoot($root)) != 0) return $ret;
        
        // BasicSystem
        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_basicSystem = $this->_m_basicSystem = $root.$inidata['env']['BasicSystemFile'];
        if(empty($out_basicSystem)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get basic system.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // SysOptDir取得
    public function getSysOptDir(&$out_sysOptDir)
    {
        $ret = 0;
        $root = "";
        if(($ret = $this->getIntegRoot($root)) != 0) return $ret;

        return $ret;
    }

    // ServiceName取得
    public function getServiceName(&$out_serviceName)
    {
        $ret = 0;
        if(!empty($this->_m_serviceName)){
            $out_serviceName = $this->_m_serviceName;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_serviceName = $this->_m_serviceName = $inidata['env']['Service'];
        if(empty($out_serviceName)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get sys opt dir.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // SystemId取得
    public function getSystemId(&$out_systemId)
    {
        $ret = 0;
        if(!empty($this->_m_systemId)){
            $out_systemId = $this->_m_systemId;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_systemId = $this->_m_systemId = $inidata['env']['SystemId'];
        if(empty($out_systemId)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get sys opt dir.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // TreeId取得
    public function getTreeId(&$out_treeId)
    {
        $ret = 0;
        if(!empty($this->_m_treeId)){
            $out_treeId = $this->_m_treeId;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_treeId = $this->_m_treeId = $inidata['env']['TreeId'];
        if(empty($out_treeId)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get sys opt dir.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // IDプールルート取得
    public function getIdPoolRoot(&$out_idPoolRoot)
    {
        $ret = 0;
        if(!empty($this->_m_idPoolRoot)){
            $out_idPoolRoot = $this->_m_idPoolRoot;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_idPoolRoot = $this->_m_idPoolRoot = $inidata['env']['Pool'];
        if(empty($out_idPoolRoot)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get id pool dir.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // Employeeプールルート取得
    public function getEmployeePoolRoot(&$out_employeePoolRoot)
    {
        $ret = 0;
        if(!empty($this->_m_employeePoolRoot)){
            $out_employeePoolRoot = $this->_m_employeePoolRoot;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_employeePoolRoot = $this->_m_employeePoolRoot = $inidata['env']['Employee'];
        if(empty($out_employeePoolRoot)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get employee pool dir.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // Resourceルート取得
    public function getResourceRoot(&$out_resourceRoot)
    {
        $ret = 0;
        if(!empty($this->_m_resourceRoot)){
            $out_resourceRoot = $this->_m_resourceRoot;
            return $ret;
        }

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $out_resourceRoot = $this->_m_resourceRoot = $inidata['env']['Resource'];
        if(empty($out_resourceRoot)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get resource dir.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        return $ret;
    }

    // 環境変数設定
    public function setEnv($in_name, $in_value)
    {
        putenv("$in_name=$in_value");
    }
}

class XCgiCloudApi
{
    private $_m_cStatusApi;

    // コンストラクタ
    public function __construct(&$in_cStatusApi)
    {
    	$this->_m_cStatusApi = $in_cStatusApi;
        $this->_m_lambdaCreateFuntion = "";
    }

    // Account取得
    public function getAccount(&$out_account)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $account = $inidata['aws']['Account'];
        if(empty($account)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get account.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_account = $account;

        return $ret;
    }

    // LambdaCreateFuntion取得
    public function getLambdaCreateFuntion($in_name, $in_role, $in_handler, $in_code, $in_timeout, &$out_lambdaFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $lambdaFunction = $inidata['aws']['LambdaCreateFunction'];
        if(empty($lambdaFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get lambda create function.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_lambdaFunction = sprintf($lambdaFunction, $in_name, $in_role, $in_handler, $in_code, $in_timeout);

        return $ret;
    }

    // LambdaGetFuntion取得
    public function getLambdaGetFuntion($in_name, &$out_lambdaFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $lambdaFunction = $inidata['aws']['LambdaGetFunction'];
        if(empty($lambdaFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get lambda get function.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_lambdaFunction = sprintf($lambdaFunction, $in_name);

        return $ret;
    }

    // LambdaAddPermission取得
    public function getLambdaAddPermission($in_name, $in_optionalid, $in_srcarn, &$out_lambdaFunction)
    {
        $ret = 0;

        if(($ret = $this->getAccount($account)) != 0) return $ret;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $lambdaFunction = $inidata['aws']['LambdaAddPermission'];
        if(empty($lambdaFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get lambda add permission.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_lambdaFunction = sprintf($lambdaFunction, $in_name, $in_optionalid, $in_srcarn, $account);

        return $ret;
    }

    // LambdaGetPolicy取得
    public function getLambdaGetPolicy($in_name, &$out_lambdaFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $lambdaFunction = $inidata['aws']['LambdaGetPolicy'];
        if(empty($lambdaFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get lambda get policy.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_lambdaFunction = sprintf($lambdaFunction, $in_name);

        return $ret;
    }

    // LambdaDeleteFuntion取得
    public function getLambdaDeleteFuntion($in_name, &$out_lambdaFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $lambdaFunction = $inidata['aws']['LambdaDeleteFunction'];
        if(empty($lambdaFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get lambda delete function.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_lambdaFunction = sprintf($lambdaFunction, $in_name);

        return $ret;
    }

    // S3CreateBucket取得
    public function getS3CreateBucket($in_name, &$out_s3Function)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $s3Function = $inidata['aws']['S3CreateBucket'];
        if(empty($s3Function)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get s3 create bucket.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_s3Function = sprintf($s3Function, $in_name);

        return $ret;
    }

    // S3GetBucket取得
    public function getS3GetBucket($in_name, &$out_s3Function)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $s3Function = $inidata['aws']['S3GetBucket'];
        if(empty($s3Function)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get s3 get bucket.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_s3Function = sprintf($s3Function, $in_name);

        return $ret;
    }

    // S3Notification取得
    public function getS3Notification($in_name, $in_configuration, &$out_s3Function)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $s3Function = $inidata['aws']['S3Notification'];
        if(empty($s3Function)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get s3 notification.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_s3Function = sprintf($s3Function, $in_name, $in_configuration);

        return $ret;
    }

    // S3NotificationConfig取得
    public function getS3NotificationConfig($in_functionName, &$out_s3Function)
    {
        $ret = 0;

        if(($ret = $this->getAccount($account)) != 0) return $ret;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $s3Function = $inidata['aws']['S3NotificationConfig'];
        if(empty($s3Function)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get s3 notification config.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_s3Function = sprintf($s3Function, $account, $in_functionName);

        return $ret;
    }

    // S3GetNotification取得
    public function getS3GetNotification($in_name, &$out_s3Function)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $s3Function = $inidata['aws']['S3GetNotification'];
        if(empty($s3Function)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get s3 get notification.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_s3Function = sprintf($s3Function, $in_name);

        return $ret;
    }

    // S3DeleteBucket取得
    public function getS3DeleteBucket($in_name, &$out_s3Function)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $s3Function = $inidata['aws']['S3DeleteBucket'];
        if(empty($s3Function)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get s3 delete bucket.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_s3Function = sprintf($s3Function, $in_name);

        return $ret;
    }

    // GlueCreateJob取得
    public function getGlueCreateJob($in_configuration, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueCreateJob'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue create job.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_configuration);

        return $ret;
    }

    // GlueCreateDB取得
    public function getGlueCreateDB($in_configuration, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueCreateDB'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue create db.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_configuration);

        return $ret;
    }

    // GlueCreateDBConfig取得
    public function getGlueCreateDBConfig($in_name, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueCreateDBConfig'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue create db config.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name);

        return $ret;
    }

    // GlueCreateCrawler取得
    public function getGlueCreateCrawler($in_name, $in_role, $in_database, $in_configuration, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueCreateCrawler'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue create crawler.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name, $in_role, $in_database, $in_configuration);

        return $ret;
    }

    // GlueGetJob取得
    public function getGlueGetJob($in_name, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueGetJob'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue get job.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name);

        return $ret;
    }

    // GlueGetDB取得
    public function getGlueGetDB($in_name, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueGetDB'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue get db.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name);

        return $ret;
    }

    // GlueGetCrawler取得
    public function getGlueGetCrawler($in_name, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueGetCrawler'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue get crawler.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name);

        return $ret;
    }

    // GlueDeleteJob取得
    public function getGlueDeleteJob($in_name, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueDeleteJob'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue delete job.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name);

        return $ret;
    }

    // GlueDeleteDB取得
    public function getGlueDeleteDB($in_name, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueDeleteDB'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue delete db.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name);

        return $ret;
    }

    // GlueDeleteCrawler取得
    public function getGlueDeleteCrawler($in_name, &$out_glueFunction)
    {
        $ret = 0;

        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, "<!> Not exist system INI file.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }
        $glueFunction = $inidata['aws']['GlueDeleteCrawler'];
        if(empty($glueFunction)){
            $this->_m_cStatusApi->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, "<!> Cannot get glue delete crawler.".__LINE__, basename(__FILE__), __LINE__);
            return $ret;
        }

        $out_glueFunction = sprintf($glueFunction, $in_name);

        return $ret;
    }
}

class XCgiEncodingApi
{
    // エンコーディング
    static function encoding($in_str, $in_toEnc, $in_FromEnc="")
    {
        if(empty($in_FromEnc)){
            $in_FromEnc = "ASCII,UTF-8,sjis-win,eucjp-win,SJIS,JIS,EUC-JP,EUC-CN,BIG-5,EUC-TW,EUC-KR";
        }
        return mb_convert_encoding($in_str, $in_toEnc, $in_FromEnc);
    }
}

class XCgiTools
{
    // Windows確認
    static function isWindows()
    {
        $OS = PHP_OS;
        if(($OS == "WIN32") or ($OS == "WINNT")) return True;
    }

    // 他筐体確認
    static function isSeparateServer($in_host)
    {
        if((strcasecmp($in_host, "localhost") == 0) or (strcasecmp($in_host, "127.0.0.1") == 0) or (empty($in_host))){
            return False;
        }else{
            return True;
        }
    }
    
    // mkdir
    static function mkdir($dir)
    {
        for($i = 0; $i < FS_RETRY_COUNT; $i++){
            if($bRet = mkdir($dir, '0777', TRUE)) break;
            else{
                usleep(FS_RETRY_WAIT_TIME);
                if(file_exists($dir)){    // 馬鹿除け
                    $bRet = True;
                    break;
                }
            }
        }
        return $bRet;
    }
    
    // unlink
    static function unlink($file)
    {
        for($i = 0; $i < FS_RETRY_COUNT; $i++){
            if($bRet = unlink($file)) break;
            else{
                usleep(FS_RETRY_WAIT_TIME);
                if(!file_exists($file)){    // 馬鹿除け
                    $bRet = True;
                    break;
                }
            }
        }
        return $bRet;
    }
    
    // rmdir
    static function rmdir($dir)
    {
        for($i = 0; $i < FS_RETRY_COUNT; $i++){
            if($bRet = rmdir($dir)) break;
            else{
                usleep(FS_RETRY_WAIT_TIME);
                if(!file_exists($dir)){    // 馬鹿除け
                    $bRet = True;
                    break;
                }
            }
        }
        return $bRet;
    }

    // readfile
    static function readfile($file)
    {
		$size = filesize($file);
		$buffer = False;
		if((ob_get_level()) and ($size > ob_get_level())){
			while(ob_get_level()){
				ob_end_flush();
			}
			flush();
		}else{
			while(ob_get_level()==0){
				ob_start();
			}
		}
        for($i = 0; $i < FS_RETRY_COUNT; $i++){
            $ret = readfile($file);
            if(is_int($ret) and ($ret > 0)) break;
            usleep(FS_RETRY_WAIT_TIME);
        }
        return $ret;
    }

}

?>

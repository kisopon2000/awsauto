<?php

require_once dirname(__FILE__).'/XCgiLObject.php';

class XCgiLInfo extends XCgiLObject
{
    // メンバー変数
    public $_m_cEnvApi;
    public $_m_cCloudApi;

    // コンストラクタ
    public function __construct()
    {
        parent::__construct();
        $this->_m_cEnvApi = new XCgiEnvApi($this->_m_cStatus);
        $this->_m_cCloudApi = new XCgiCloudApi($this->_m_cStatus);
    }

    // デストラクタ
    public function __destruct()
    {
        parent::__destruct();
    }

    // 環境情報取得
    protected function getEnvInfo(&$out_integRoot, &$out_systemLib, &$out_basicSubSystem, &$out_sysOptDir, &$out_serviceName, &$out_basicSystem, &$out_phase, &$out_product, &$out_idPoolRoot, &$out_employeePoolRoot)
    {
        return $this->_m_cEnvApi->getEnvInfo($out_integRoot, $out_systemLib, $out_basicSubSystem, $out_sysOptDir, $out_serviceName, $out_basicSystem, $out_phase, $out_product, $out_idPoolRoot, $out_employeePoolRoot);
    }

    // IntegRoot取得
    protected function getIntegRoot(&$out_integRoot)
    {
        return $this->_m_cEnvApi->getIntegRoot($out_integRoot);
    }

    // DocumentRoot取得
    protected function getDocumentRoot(&$out_documentRoot)
    {
        return $this->_m_cEnvApi->getDocumentRoot($out_documentRoot);
    }

    // SystemLib取得
    protected function getSystemLib(&$out_systemLib)
    {
        return $this->_m_cEnvApi->getSystemLib($out_systemLib);
    }

    // BasicSubSystemパス取得
    protected function getBasicSubSystem(&$out_basicSubSystem)
    {
        return $this->_m_cEnvApi->getBasicSubSystem($out_basicSubSystem);
    }

    // ServiceName取得
    protected function getServiceName(&$out_serviceName)
    {
        return $this->_m_cEnvApi->getServiceName($out_serviceName);
    }

    // SystemId取得
    protected function getSystemId(&$out_serviceName)
    {
        return $this->_m_cEnvApi->getSystemId($out_serviceName);
    }

    // TreeId取得
    protected function getTreeId(&$out_serviceName)
    {
        return $this->_m_cEnvApi->getTreeId($out_serviceName);
    }

    // Pool取得
    protected function getIdPoolRoot(&$out_idPoolRoot)
    {
        return $this->_m_cEnvApi->getIdPoolRoot($out_idPoolRoot);
    }

    // Employee取得
    protected function getEmployeePoolRoot(&$out_employeePoolRoot)
    {
        return $this->_m_cEnvApi->getEmployeePoolRoot($out_employeePoolRoot);
    }

    // Resource取得
    protected function getResourceRoot(&$out_resourceRoot)
    {
        return $this->_m_cEnvApi->getResourceRoot($out_resourceRoot);
    }

    // Resource(Fortune)取得
    protected function getResourceFortuneDir(&$out_resourceFortuneDir)
    {
        $ret = $this->_m_cEnvApi->getResourceRoot($resourceRoot);
        if($ret != 0) return $ret;
        $out_resourceFortuneDir = $resourceRoot."/fortune";
        return $ret;
    }

    // LambdaCreateFuntion取得
    protected function getLambdaCreateFuntion($in_name, $in_role, $in_handler, $in_code, $in_timeout, &$out_lambdaFunction)
    {
        return $this->_m_cCloudApi->getLambdaCreateFuntion($in_name, $in_role, $in_handler, $in_code, $in_timeout, $out_lambdaFunction);
    }

    // LambdaGetFuntion取得
    protected function getLambdaGetFuntion($in_name, &$out_lambdaFunction)
    {
        return $this->_m_cCloudApi->getLambdaGetFuntion($in_name, $out_lambdaFunction);
    }

    // LambdaAddPermission取得
    protected function getLambdaAddPermission($in_name, $in_optionalid, $in_srcarn, &$out_lambdaFunction)
    {
        return $this->_m_cCloudApi->getLambdaAddPermission($in_name, $in_optionalid, $in_srcarn, $out_lambdaFunction);
    }

    // LambdaGetPolicy取得
    protected function getLambdaGetPolicy($in_name, &$out_lambdaFunction)
    {
        return $this->_m_cCloudApi->getLambdaGetPolicy($in_name, $out_lambdaFunction);
    }

    // LambdaDeleteFuntion取得
    protected function getLambdaDeleteFuntion($in_name, &$out_lambdaFunction)
    {
        return $this->_m_cCloudApi->getLambdaDeleteFuntion($in_name, $out_lambdaFunction);
    }

    // S3CreateBucket取得
    protected function getS3CreateBucket($in_name, &$out_s3Function)
    {
        return $this->_m_cCloudApi->getS3CreateBucket($in_name, $out_s3Function);
    }

    // S3GetBucket取得
    protected function getS3GetBucket($in_name, &$out_s3Function)
    {
        return $this->_m_cCloudApi->getS3GetBucket($in_name, $out_s3Function);
    }

    // S3Notification取得
    protected function getS3Notification($in_name, $in_configuration, &$out_s3Function)
    {
        return $this->_m_cCloudApi->getS3Notification($in_name, $in_configuration, $out_s3Function);
    }

    // S3NotificationConfig取得
    protected function getS3NotificationConfig($in_functionName, &$out_s3Function)
    {
        return $this->_m_cCloudApi->getS3NotificationConfig($in_functionName, $out_s3Function);
    }

    // S3GetNotification取得
    protected function getS3GetNotification($in_name, &$out_s3Function)
    {
        return $this->_m_cCloudApi->getS3GetNotification($in_name, $out_s3Function);
    }

    // S3DeleteBucket取得
    protected function getS3DeleteBucket($in_name, &$out_s3Function)
    {
        return $this->_m_cCloudApi->getS3DeleteBucket($in_name, $out_s3Function);
    }

    // GlueCreateJob取得
    protected function getGlueCreateJob($in_configuration, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueCreateJob($in_configuration, $out_glueFunction);
    }

    // GlueCreateDB取得
    protected function getGlueCreateDB($in_configuration, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueCreateDB($in_configuration, $out_glueFunction);
    }

    // GlueCreateDBConfig取得
    protected function getGlueCreateDBConfig($in_name, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueCreateDBConfig($in_name, $out_glueFunction);
    }

    // GlueCreateCrawler取得
    protected function getGlueCreateCrawler($in_name, $in_role, $in_database, $in_configuration, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueCreateCrawler($in_name, $in_role, $in_database, $in_configuration, $out_glueFunction);
    }

    // GlueGetJob取得
    protected function getGlueGetJob($in_name, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueGetJob($in_name, $out_glueFunction);
    }

    // GlueGetDB取得
    protected function getGlueGetDB($in_name, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueGetDB($in_name, $out_glueFunction);
    }

    // GlueGetCrawler取得
    protected function getGlueGetCrawler($in_name, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueGetCrawler($in_name, $out_glueFunction);
    }

    // GlueDeleteJob取得
    protected function getGlueDeleteJob($in_name, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueDeleteJob($in_name, $out_glueFunction);
    }

    // GlueDeleteDB取得
    protected function getGlueDeleteDB($in_name, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueDeleteDB($in_name, $out_glueFunction);
    }

    // GlueDeleteCrawler取得
    protected function getGlueDeleteCrawler($in_name, &$out_glueFunction)
    {
        return $this->_m_cCloudApi->getGlueDeleteCrawler($in_name, $out_glueFunction);
    }

    // 環境変数設定
    protected function setEnv($in_name, $in_value)
    {
        $this->_m_cEnvApi->setEnv($in_name, $in_value);
    }
    
    // 環境変数設定
    protected function setEnvs()
    {
        if(($ret = $this->_m_cEnvApi->getEnvInfo($root, $systemLib, $basicSubSystem, $sysOptDir, $serviceName, $basicSystem, $phase, $product, $idPoolRoot, $employeePoolRoot)) != 0) return $ret;
        $path = getenv('PATH') . ';' . $systemLib;
        $this->_m_cEnvApi->setEnv('PATH', $path);
        $this->_m_cEnvApi->setEnv('BASIC_SYSTEM_FILE', $basicSystem);
        $this->_m_cEnvApi->setEnv('BASIC_SUBSYSTEM_FILE', $basicSubSystem);
        $this->_m_cEnvApi->setEnv('SysOptDir', $sysOptDir);
        $this->_m_cEnvApi->setEnv('SysServiceName', $serviceName);
        $this->_m_cEnvApi->getTreeId($treeId);
        $this->_m_cEnvApi->setEnv('SysTaskMgrTreeID', $treeId);
        $this->_m_cEnvApi->getSystemId($systemId);
        $this->_m_cEnvApi->setEnv('SysTaskMgrSystemTreeID', $systemId);
        $this->_m_cEnvApi->setEnv('SysServicePhase', $phase);
        $this->_m_cEnvApi->setEnv('SysServiceProduct', $product);
        $this->_m_cEnvApi->setEnv('SysIdPoolRoot', $idPoolRoot);
        $this->_m_cEnvApi->setEnv('SysEmployeePoolRoot', $employeePoolRoot);
    }
}

?>

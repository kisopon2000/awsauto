<?php

require_once dirname(__FILE__)."/../XCgiDefines.php";

class XCgiSys
{
    public static function sysinit()
    {
        // オリジナルURI取得
        if(isset($_SERVER['REQUEST_URI'])){
            // Apache
            $_REQUEST['XCGI_SYS_ORG_URI'] = $_SERVER['REQUEST_URI'];
        }elseif(isset($_SERVER['HTTP_X_ORIGINAL_URL'])){
            // IIS
            $_REQUEST['XCGI_SYS_ORG_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
        }else{
            $this->redirect(ERR_RTN_INTERNAL_SERVER_ERROR, '<!> Cannot get original uri. '.__LINE__);
            return ERR_RTN_INTERNAL_SERVER_ERROR;
        }

        // リクエストAPI取得
        preg_match('/api\/([a-zA-Z0-9\/\-]+)/', $_REQUEST['XCGI_SYS_ORG_URI'], $api);
        $_REQUEST['XCGI_SYS_API'] = $api[1];
        if(!isset($_REQUEST['XCGI_SYS_API'])){
            preg_match('/api-sys\/([a-zA-Z0-9\/\-]+)/', $_REQUEST['XCGI_SYS_ORG_URI'], $api);
            $_REQUEST['XCGI_SYS_API'] = $api[1];
            if(!isset($_REQUEST['XCGI_SYS_API'])){
                $this->redirect(ERR_RTN_BAD_REQUEST, '<!> Cannot get request api. '.__LINE__);
                return ERR_RTN_BAD_REQUEST;
            }
        }

        // その他
        $_REQUEST['XCGI_SYS_REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];
        $_REQUEST['XCGI_SYS_QUERY_STRING'] = $_SERVER['QUERY_STRING'];
        $_REQUEST['XCGI_SYS_STDIN'] = file_get_contents('php://input');
        $_REQUEST['XCGI_SYS_CONTENT_LENGTH'] = strlen($_REQUEST['XCGI_SYS_STDIN']);
        $_REQUEST['XCGI_SYS_GET'] = $_GET;
        $_REQUEST['XCGI_SYS_POST'] = $_POST;
        $_REQUEST['XCGI_SYS_CLIENT_IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];

        return 0;
    }
    
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

    public static function redirect($in_id, $in_message)
    {
    	http_response_code($in_id);
        $exception = array(
            "id" => $in_id,
            "message" => $in_message,
        );
        $exception = json_encode($exception);
        echo $exception;
    }
}

?>

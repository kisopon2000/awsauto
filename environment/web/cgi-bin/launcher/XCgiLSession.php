<?php

require_once dirname(__FILE__)."/XCgiLLog.php";

class XCgiLSession extends XCgiLLog
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

    // セッション確認
    public function isSessionActive()
    {
        // セッション確認
        if(session_status() === PHP_SESSION_ACTIVE){
            return True;
        }else{
            return False;
        }
    }

    // セッションID取得
    public function getSessionId()
    {
    	return session_id();
    }

    // セッション開始及び継続
    public function startSession()
    {
        //セッションのクッキーは有るがセッションファイルが無い場合を検出
        if(isset($_COOKIE['PHPSESSID'])){
            $ses_file_name = WIN_TEMP_FOLDER.'sess_'.$_COOKIE['PHPSESSID'];
            if(!file_exists($ses_file_name)){
                //$this->writeLog(basename(__FILE__).':'.__LINE__.':'. "not found session file.($ses_file_name)");
            } else if(filesize($ses_file_name) == 0){
                //$this->writeLog(basename(__FILE__).':'.__LINE__.':'. "session file size is 0.($ses_file_name)");
            }
        }
        // セッション開始及び継続
        session_start();
        $sessionID = session_id() . md5(uniqid(session_id() . getmypid() . microtime(true) . mt_rand(), true));
        return $sessionID;
    }

    // セッション再開
    public function restartSession()
    {
        // セッション開始及び継続
        session_start();
    }

    // セッション変数にユーザーIDを保存
    public function setSessionUserId($userId)
    {
        if(isset($_SESSION['user_id'])){
            $_SESSION = array();
            session_regenerate_id(True);
        }
        $_SESSION['user_id'] = $userId;
    }

    // セッション終了
    public function exitSession()
    {
        $_SESSION = array();
        if (isset($_COOKIE["PHPSESSID"])) {
            setcookie("PHPSESSID", '', time() - 1800, '/');
        }
        session_destroy();
    }

    // セッション更新
    public function updateSession()
    {
        session_regenerate_id(True);
    }

    // セッション書き込み終了
    public function closeWriteSession()
    {
        session_write_close();
    }

    // ログインチェック
    public function chkLogin()
    {
        if(isset($_SESSION['user_id'])){
            return True;
        } else {
            return False;
        }
    }
}

?>
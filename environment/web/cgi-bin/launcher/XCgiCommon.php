<?php

require_once dirname(__FILE__)."/XCgiLauncher.php";

class CommonLauncher extends XCgiLauncher
{
    // メンバ変数
    protected $_m_WORK_IN_FILE;
    protected $_m_WORK_OUT_FILE;
    protected $_m_VALID_TOKEN_ID;

    // エントリーポイント
    public function run()
    {
        XCgiResponseApi::redirect(ERR_RTN_INTERNAL_SERVER_ERROR, ERR_RTN_INTERNAL_SERVER_ERROR, '<!> Not override run.');
        return INTERNALERR_RTN_EXCEPTION;
    }

    // 前処理
    public function prelude()
    {
        $_REQUEST['X_CMD_IN_JSON'] = "";
        $_REQUEST['X_CMD_ARGS'] = "";
        $_REQUEST['X_CMD_OUT_JSON'] = $this->_m_WORK_OUT_FILE;
        $get = XCgiRequestApi::get();
        $post = XCgiRequestApi::post();
        $stdin = XCgiRequestApi::getStdin();
        $requestMethod = XCgiRequestApi::getRequestMethod();
        if(is_string($stdin) && is_array(json_decode($stdin, true)) && (json_last_error() == JSON_ERROR_NONE)){
            $_REQUEST['X_CMD_IN_JSON'] = $stdin;
        }
        if($_SERVER['HTTP_'.HEADER_KEY_TOKEN_ID_R] ==""){
            $this->_m_VALID_TOKEN_ID = $_SERVER['HTTP_'.HEADER_KEY_TOKEN_ID_S];
        }else{
            $this->_m_VALID_TOKEN_ID = $_SERVER['HTTP_'.HEADER_KEY_TOKEN_ID_R];
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

        // セッション開始
//        session_start();
//        if(session_status() < 2){    // セッションの確認(0:PHP_SESSION_DISABLED, 1:PHP_SESSION_NONE, 2:PHP_SESSION_ACTIVE)
//            echo "INFO: Not exist session.<br/>";
//            echo 'ステータスは、 <b>[ '.session_status()." ]</b><br/>\n";   //セッション名
//            echo 'セッション名は、 <b>[ '.session_name()." ]</b><br/>\n";   //セッション名
//            echo 'セッションIDは、 <b>[ '.session_id()." ]</b><br/>\n";     //セッションID
//            とりあえず無視
//            exit();
//        }

        // セッションID取得
        $sessionID = $this->startSession();
        
        // ログインチェック
        //if(!$this->chkLogin()){
        //   $this->setError($ret = INTERNALERR_RTN_SESSION_END, '<!> Not Login. '.__LINE__, basename(__FILE__), __LINE__);
        //    goto INTERLUDE_END;
        //}
        
        //起動パラメータにログインユーザIDを追加
        //$arguments .= "-loginuser ".$_SESSION['user_id']." ";
        $arguments .= "-loginuser ".'Administrator'." ";		//仮ユーザー
        
        //起動パラメータにアクセス元IPアドレスを追加
        $arguments .= "-loginip ".$_SERVER['REMOTE_ADDR']." ";
        
        //起動パラメータにインテグルートを追加
        $arguments .= "-integroot \"".$root."\" ";
        
        //起動パラメータにドキュメントルートを追加
        $arguments .= "-documentroot \"".$documentRoot."\" ";
        
        //起動パラメータにセッションIDを追加
        $arguments .= "-sessionid ".session_id()." ";
        
        //起動パラメータにセッションIDを追加
        if($this->_m_VALID_TOKEN_ID){
            $arguments .= "-validtokenid ".$this->_m_VALID_TOKEN_ID." ";
        }
        
        // INIから設定を取得
        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, '<!> Not exist INI file. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }
        if(array_key_exists($api, $inidata)){
            $module        = $inidata[$api]['Module'];
            $encoding      = $inidata[$api]['Encoding'];
            $workAllDir    = $inidata[$api]['Work'];
            $workDir       = parse_url($workAllDir, PHP_URL_PATH);
            $workInFile    = $workDir."/".$this->_m_WORK_IN_FILE;
            $workOutFile   = $workDir."/".$this->_m_WORK_OUT_FILE;
            $inArg         = "-in";
            $outArg        = "-out";
        }else{
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist INI section. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }

        // 実行モジュール設定有無確認
        if(empty($module)){
            $this->setError($ret = INTERNALERR_RTN_NOT_SET_MODULE, '<!> Not setup module. '.__LINE__, basename(__FILE__), __LINE__);
            goto INTERLUDE_END;
        }else{
            $module = '..'.str_replace('/', '\\', $module);
        }

        // パス設定
        $workDir = str_replace("__SESSION_ID__", $sessionID, $workDir);
        if(!empty($workDir)) $workFullDir = $root.$workDir;
        $workInFile = str_replace("__SESSION_ID__", $sessionID, $workInFile);
        if(!empty($workInFile)) $workInFilePath = $root.$workInFile;
        $workOutFile = str_replace("__SESSION_ID__", $sessionID, $workOutFile);
        if(!empty($workOutFile)) $workOutFilePath = $root.$workOutFile;

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
        
        //セッション一時解放
        //$this->closeWriteSession();
        //セッション終了
        $this->exitSession();

        // コマンド実行
        if(!empty($encoding)){
            $arguments = XCgiEncodingApi::encoding($arguments, $encoding);
        }
        //echo $module." ".$arguments." ".$inArg." ".$workInFileQuotedPath." ".$outArg." ".$workOutFileQuotedPath;
        //return 0;
        $ret = $this->proc($module.' '.$arguments.' '.$inArg.' '.$workInFileQuotedPath.' '.$outArg.' '.$workOutFileQuotedPath, $stdout, $stderr);
        if($ret != 0) goto INTERLUDE_END;

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
        //セッション再開
        //$this->restartSession();

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

    // 後処理
    public function postlude()
    {
        return 0;
    }

	public function download($pPath, $pMimeType = null)
	{
		$ret = 0;
		
	    //-- ファイルが読めない時はエラー
	    /*if (!is_readable($pPath)) { 
	        $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create work dir failed. '.$workFullDir.' '.__LINE__, basename(__FILE__), __LINE__);
	        return $ret;
	    }*/

	    //-- 適切なMIMEタイプが得られない時は、未知のファイルを示すapplication/octet-streamとする
	    if (!preg_match('/\A\S+?\/\S+/', $mimeType)) {
	        $mimeType = 'application/octet-stream';
	    }

	    //-- Content-Type
	    header('Content-Type: ' . $mimeType);

	    //-- ウェブブラウザが独自にMIMEタイプを判断する処理を抑止する
	    header('X-Content-Type-Options: nosniff');

	    //-- ダウンロードファイルのサイズ
	    header('Content-Length: ' . filesize($pPath));

	    //-- ダウンロード時のファイル名
	    header('Content-Disposition: attachment; filename="' . basename($pPath) . '"');

	    //-- keep-aliveを無効にする
	    header('Connection: close');

	    //-- readfile()の前に出力バッファリングを無効化する ※詳細は後述
	    while (ob_get_level()) { ob_end_clean(); }

	    //-- 出力
	    readfile($pPath);
	    
	    return $ret;
	}
}

?>

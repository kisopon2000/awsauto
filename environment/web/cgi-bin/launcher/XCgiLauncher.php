<?php

require_once dirname(__FILE__).'/XCgiLProc.php';

class XCgiLauncher extends XCgiLProc
{
    // コンストラクタ
    public function __construct()
    {
    }

    // デストラクタ
    public function __destruct()
    {
    }

    // 初期化
    public function initialize()
    {
        parent::__construct();
        $ret = 0;
        if(($ret = $this->setEnvs()) != 0) return $ret;
        return $ret;
    }

    // コマンド実行
    public function run()
    {
        XCgiResponseApi::redirect(ERR_RTN_INTERNAL_SERVER_ERROR, ERR_RTN_INTERNAL_SERVER_ERROR, '<!> Not override run.');
        return INTERNALERR_RTN_EXCEPTION;
    }

    // 後処理
    public function finalize()
    {
        parent::__destruct();
        return 0;
    }

    // リモート実行
    public function remoteExec($in_host, $in_port, $in_rmtExecutor)
    {
        if(($ret = $this->getIntegRoot($root)) != 0) return $ret;

        $executor = sprintf($root.EXECUTOR, $in_host, $in_port, $in_rmtExecutor);
        return $this->proc($executor);
    }

    // リモートファイルアップロード
    public function remoteUpload($in_host, $in_port, $in_rmtDir, $in_localFile)
    {
        if(($ret = $this->getIntegRoot($root)) != 0) return $ret;

        $uploader = sprintf($root.UPLOADER, $in_host, $in_port, $in_rmtDir, $in_localFile);
        return $this->proc($uploader);
    }

    // リモートファイルダウンロード
    public function remoteDownload($in_host, $in_port, $in_rmtFile)
    {
        if(($ret = $this->getIntegRoot($root)) != 0) return $ret;

        $downloader = sprintf($root.DOWNLOADER, $in_host, $in_port, $in_rmtFile);
        return $this->proc($downloader);
    }

    // コマンド実行
/*    public function _launch($in_args)
    {
        // コマンド取得
        $api = XCgiRequestApi::getApi();
        if(!isset($api))
            $this->setError($ret = INTERNALERR_RTN_NOT_SET_CMD, '<!> Not exist request command. '.__LINE__, basename(__FILE__), __LINE__);
            goto LAUNCHER_END;
        }

        // Arguments取得
        if(isset($_REQUEST['X_CMD_ARGS'])){
            $arguments = $_REQUEST['X_CMD_ARGS'];
        }

        // IntegRoot取得
        $ret = $this->getIntegRoot($root);
        if($ret != 0) goto INTERLUDE_END;

        // セッション開始
        session_start();
        if(session_status() < 2){    // セッションの確認(0:PHP_SESSION_DISABLED, 1:PHP_SESSION_NONE, 2:PHP_SESSION_ACTIVE)
            echo "INFO: Not exist session.<br/>";
//            echo 'ステータスは、 <b>[ '.session_status()." ]</b><br/>\n";   //セッション名
//            echo 'セッション名は、 <b>[ '.session_name()." ]</b><br/>\n";   //セッション名
//            echo 'セッションIDは、 <b>[ '.session_id()." ]</b><br/>\n";     //セッションID
//            とりあえず無視
//            exit();
        }

        // セッションID取得
        $sessionID = session_id();

        // INIから設定を取得
        $inidata = parse_ini_file(INI_FILE, TRUE);
        if($inidata == null){
            $this->setError($ret = INTERNALERR_RTN_NOT_FOUND_FILE, '<!> Not exist INI file. '.__LINE__, basename(__FILE__), __LINE__);
            goto LAUNCHER_END;
        }
        if(array_key_exists($function, $inidata)){
            $module        = $inidata[$api]['Module'];
            $moduleArgs    = $inidata[$api]['Args'];
            $encodingIn    = $inidata[$api]['EncodingIn'];
            $accessIn      = $inidata[$api]['AccessIn'];
            $accessOut     = $inidata[$api]['AccessOut'];
            $encodingOut   = $inidata[$api]['EncodingOut'];
            $remoteRoot    = $inidata[$api]['RmtRoot'];
            $inArg         = "-in";
            $inFile        = parse_url($accessIn, PHP_URL_PATH);
            $inDir         = pathinfo($inFile, PATHINFO_DIRNAME);
            $outArg        = "-out";
            $outFile       = parse_url($accessOut, PHP_URL_PATH);
            $outDir        = pathinfo($outFile, PATHINFO_DIRNAME);
            $moduleHost    = parse_url($module, PHP_URL_HOST);
            $modulePort    = parse_url($module, PHP_URL_PORT);
            $accessInHost  = parse_url($accessIn, PHP_URL_HOST);
            $accessInPort  = parse_url($accessIn, PHP_URL_PORT);
            $accessOutHost = parse_url($accessOut, PHP_URL_HOST);
            $accessOutPort = parse_url($accessOut, PHP_URL_PORT);
        }else{
            $this->setError($ret = INTERNALERR_RTN_CANNOT_GET_DATA, '<!> Not exist INI section. '.__LINE__, basename(__FILE__), __LINE__);
            goto LAUNCHER_END;
        }

        // 実行モジュール設定有無確認
        if(!isset($module) or empty($module)){
            $this->setError($ret = INTERNALERR_RTN_NOT_SET_MODULE, '<!> Not setup module. '.__LINE__, basename(__FILE__), __LINE__);
            goto LAUNCHER_END;
        }else{
            $module = $root.$module;
        }

        // パス設定
        $inDir = str_replace("__SESSION_ID__", $sessionID, $inDir);
        if(!empty($inDir)) $inFullDir = $root.$inDir;
        $inFile = str_replace("__SESSION_ID__", $sessionID, $inFile);
        if(!empty($inFile)) $inFullFile = $root.$inFile;
        $outDir = str_replace("__SESSION_ID__", $sessionID, $outDir);
        if(!empty($outDir)) $outFullDir =  $root.$outDir;
        $outFile = str_replace("__SESSION_ID__", $sessionID, $outFile);
        if(!empty($outFile)) $outFullFile = $root.$outFile;
        $outFullHeaderFile = str_replace(".json", ".json_header.txt", $outFullFile);

        // IN出力先ディレクトリ作成
        if(!empty($accessIn) and !empty($in_args) and !empty($inFullDir)){
            if(XCgiTools::isSeparateServer($accessInHost)){
                $wdcreator = sprintf(LCL_WDHANDLER, $root);
                $ret = $this->remoteUpload($accessInHost, $accessInPort, RMT_WD, $wdcreator);
                if($ret != 0) goto LAUNCHER_END;
                if(XCgiTools::isWindows()) $rmtDir = str_replace("/", "\\", $inFullDir);
                if(empty($remoteRoot)) $remoteRoot = $root;
                $wdhandler = sprintf(RMT_WDHANDLER, $remoteRoot.RMT_WD, WDHANDLER_CREATE, $rmtDir);
                $ret = $this->remoteExec($accessInHost, $accessInPort, $wdhandler);
                if($ret != 0) goto LAUNCHER_END;
            }else{
                if(!file_exists($inFullDir)){
                    if(!mkdir($inFullDir)){
                        $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create work dir failed. '.__LINE__, basename(__FILE__), __LINE__);
                        goto LAUNCHER_END;
                    }
                }
            }
        }
        $isExistInDir = True;

        // OUT出力先ディレクトリ作成
        if(!empty($accessOut) and !empty($outFullDir)){
            if(XCgiTools::isSeparateServer($accessOutHost)){
                $wdcreator = sprintf(LCL_WDHANDLER, $root);
                $ret = $this->remoteUpload($accessOutHost, $accessOutPort, RMT_WD, $wdcreator);
                if($ret != 0) goto LAUNCHER_END;
                if(XCgiTools::isWindows()) $rmtDir = str_replace("/", "\\", $outFullDir);
                if(empty($remoteRoot)) $remoteRoot = $root;
                $wdhandler = sprintf(RMT_WDHANDLER, $remoteRoot.RMT_WD, WDHANDLER_CREATE, $rmtDir);
                $ret = $this->remoteExec($accessInHost, $accessInPort, $wdhandler);
                if($ret != 0) goto LAUNCHER_END;
            }else{
                if(!file_exists($outFullDir)){
                    if(!mkdir($outFullDir)){
                        $this->setError($ret = INTERNALERR_RTN_MKDIR_FAILED, '<!> Create work dir failed. '.__LINE__, basename(__FILE__), __LINE__);
                        goto LAUNCHER_END;
                    }
                }
            }
        }
        $isExistOutDir = True;

        // JSONファイル作成
        if(!empty($accessIn) and !empty($in_args)){
            if(!empty($encodingIn)){
                $json = XCgiTools::encoding($in_args, $encodingIn);
            }
            file_put_contents($root.$inFile, $json);
            
            if(XCgiTools::isSeparateServer($accessInHost)){
                // ファイルアップロード
                $ret = $this->remoteUpload($accessInHost, $accessInPort, $inDir, $inFullFile);
                if($ret != 0) goto LAUNCHER_END;
            }
        }else{
            $inArg = $inFile = "";
        }

        // コマンド実行
        if(!empty($encodingIn)){
            $arguments = XCgiTools::encoding($arguments, $encodingIn);
        }
        $ret = $this->proc($module." ".$arguments." ".$inArg." ".$inFile." ".$outArg." ".$outFile);
        if($ret != 0) goto LAUNCHER_END;

        if(!empty($accessOut) and !empty($outFullDir)){
            if(XCgiTools::isSeparateServer($accessOutHost)){
                // ファイルダウンロード
                $cwd = getcwd();
                chdir($outFullDir);
                $ret = $this->remoteDownload($accessOutHost, $accessOutPort, $outFile);
                if($ret != 0) goto LAUNCHER_END;
                chdir($cwd);
            }
        }

        // 結果を標準出力
        if(!$this->isExistError()){
            if(!empty($encodingOut)){
                $fp = fopen($outFullFile, "rb");
                while(($line = fgets($fp)) !== false){
                    if(!empty($encodingOut)){
                        $line = XCgiTools::encoding($line, "UTF-8", $encodingOut);
                    }
                    echo $line;
                }
                fclose($fp);
            }else{
                readfile($outFullFile);
            }
        }

LAUNCHER_END:

        if(isset($isExistInDir) and $isExistInDir){
            // IN出力先ディレクトリ削除
            if(XCgiTools::isSeparateServer($accessInHost)){
                if(XCgiTools::isWindows()) $rmtDir = str_replace("/", "\\", $inFullDir);
                if(empty($remoteRoot)) $remoteRoot = $root;
                $wdhandler = sprintf(RMT_WDHANDLER, $remoteRoot.RMT_WD, WDHANDLER_REMOVE, $rmtDir);
                $ret = $this->remoteExec($accessInHost, $accessInPort, $wdhandler);
            }else{
                if(file_exists($inFullDir)){
                    unlink($inFullFile);
                    rmdir($inFullDir);
                }
            }
        }
        if(isset($isExistOutDir) and $isExistOutDir){
            // OUT出力先ディレクトリ削除
            if(XCgiTools::isSeparateServer($accessOutHost)){
                if(XCgiTools::isWindows()) $rmtDir = str_replace("/", "\\", $outFullDir);
                if(empty($remoteRoot)) $remoteRoot = $root;
                $wdhandler = sprintf(RMT_WDHANDLER, $remoteRoot.RMT_WD, WDHANDLER_REMOVE, $rmtDir);
                $ret = $this->remoteExec($accessInHost, $accessInPort, $wdhandler);
            }else{
                if(file_exists($outFullDir)){
                    unlink($outFullFile);
                    rmdir($outFullDir);
                }
            }
        }

        if($this->isExistError()){
            XCgiResponseApi::redirect($this->getExternalError(), $this->getErrorMessage());
            return $this->getErrorId();
        }

        return 0;
    }
*/
}

?>

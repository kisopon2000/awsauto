<?php

//-----------------------------------
// INIファイル
//-----------------------------------
define("INI_FILE", dirname(__FILE__).'/../config/XCgi.ini');
define("ACCOUNT_INI_FILE", dirname(__FILE__).'/../config/account.ini');

//-----------------------------------
// Windows関連
//-----------------------------------
define("TASKKILL", '/system32/taskkill.exe  /F /T /PID %d 2>&1');
define("WIN_TEMP_FOLDER", 'C:/Windows/Temp/');
define("PHP_LOGFILE", 'sess_phperrorlog');
//-----------------------------------
// HTTP関連
//-----------------------------------
define("UPLOADER", '/system/cmd/http.exe UPLOAD -i %s -p %d -o %s:%s');
define("DOWNLOADER", '/system/cmd/http.exe GET -i %s -p %d -o %s');
define("EXECUTOR", '/system/cmd/http.exe EXEC -i %s -p %d -o "%s"');
define("LCL_WDHANDLER", '%s/Web/cgi-bin/DB/tools/WorkDirHandler.bat');
define("RMT_WD", '/Web/cgi-work');
define("RMT_WDHANDLER", '%s/WorkDirHandler.bat %d %s');
define("HEADER_KEY_TOKEN_ID_R", 'X_PWSP_AUTH');
define("HEADER_KEY_TOKEN_ID_S", 'X-PWSP-AUTH');

define("WDHANDLER_CREATE", 1);
define("WDHANDLER_REMOVE", 2);

//-----------------------------------
// エラーコード(外部)
//-----------------------------------
define("ERR_ID", 'ERR_ID');
define("ERR_MESSAGE", 'ERR_MESSAGE');

define("ERR_RTN_BAD_REQUEST", 400);
define("ERR_RTN_UNAUTHORIZED", 401);
define("ERR_RTN_PAYMENT_REQUIRED", 402);
define("ERR_RTN_FORBIDDEN", 403);
define("ERR_RTN_NOT_FOUND", 404);
define("ERR_RTN_METHOD_NOT_ALLOWED", 405);
define("ERR_RTN_NOT_ACCEPTABLE", 406);
define("ERR_RTN_PROXY_AUTHENTIFICATION_REQUIRED", 407);
define("ERR_RTN_REQUEST_TIMEOUT", 408);
define("ERR_RTN_CONFLICT", 409);
define("ERR_RTN_GONE", 410);
define("ERR_RTN_LENGTH_REQUIRED", 411);
define("ERR_RTN_PRECONDITION_FAILED", 412);
define("ERR_RTN_PAYLOAD_TOO_LARGE", 413);
define("ERR_RTN_URI_TOO_LONG", 414);
define("ERR_RTN_UNSUPPORTED_MEDIA_TYPE", 415);
define("ERR_RTN_RANGE_NOT_SATISFIABLE", 416);
define("ERR_RTN_EXPECTATION_FAILED", 417);
define("ERR_RTN_IM_A_TEAPOT", 418);
define("ERR_RTN_MISDIRECTED_REQUEST", 421);
define("ERR_RTN_UNPROCESSABLE_ENTITY", 422);
define("ERR_RTN_LOCKED", 423);
define("ERR_RTN_FAILED_DEPENDENCY", 424);
define("ERR_RTN_UPGRADE_REQUIRED", 426);
define("ERR_RTN_UNAVAILABLE_FOR_LEGAL_REQSONS", 451);

define("ERR_RTN_INTERNAL_SERVER_ERROR", 500);
define("ERR_RTN_NOT_IMPLEMENTED", 501);
define("ERR_RTN_BAD_GATEWAY", 502);
define("ERR_RTN_SERVICE_UNAVAILABLE", 503);
define("ERR_RTN_GATEWAY_TIMEOUT", 504);
define("ERR_RTN_HTTP_VERSION_NOT_SUPPORTED", 505);
define("ERR_RTN_VARIANT_ALSO_NEGOTIATES", 506);
define("ERR_RTN_INSUFFICIENT_STORAGE", 507);
define("ERR_RTN_LOOP_DETECTED", 508);
define("ERR_RTN_BANDWIDTH_LIMIT_EXCEEDED", 509);
define("ERR_RTN_NOT_EXTENDED", 510);

//-----------------------------------
// エラーコード(内部)
//-----------------------------------
define("INTERNALERR_RTN_NONE", 0);
define("INTERNALERR_RTN_INVALID_REQUEST", 1);
define("INTERNALERR_RTN_ORG_URI_NOT_FOUND", 2);
define("INTERNALERR_RTN_EXCEPTION", 3);
define("INTERNALERR_RTN_NOT_SET_CMD", 4);
define("INTERNALERR_RTN_NOT_SET_MODULE", 5);
define("INTERNALERR_RTN_TIMEOUT", 6);
define("INTERNALERR_RTN_LAUNCH_CMD", 7);
define("INTERNALERR_RTN_EXEC_CMD", 8);
define("INTERNALERR_RTN_SESSION_END", 9);
define("INTERNALERR_RTN_NOT_FOUND_FILE", 10);
define("INTERNALERR_RTN_CANNOT_GET_DATA", 11);
define("INTERNALERR_RTN_MKDIR_FAILED", 12);
define("INTERNALERR_RTN_RMDIR_FAILED", 13);
define("INTERNALERR_RTN_MOVE_FILE_FAILED", 14);
define("INTERNALERR_RTN_READFILE_FAILED", 15);
define("INTERNALERR_RTN_AUTH_ERROR", 20);
define("INTERNALERR_RTN_NOT_REGISTERED", 21);

//-----------------------------------
// ログ
//-----------------------------------
define("LOG_DIRECTORY", '/log/cgi/');
define("LOG_FILENAME", 'XCgi.');
define("MAX_LOTATES", 3);
define("MAX_LOGSIZE", 1024*1024);

//-----------------------------------
// クラウド
//-----------------------------------
define("CLOUD_EXISTCHECK_QUEUETYPE_S3_UPLOAD", 's3_upload');
define("CLOUD_EXISTCHECK_QUEUETYPE_S3_NOTIFICATION", 's3_notification');
define("CLOUD_EXISTCHECK_QUEUETYPE_S3_DATALAKE", 's3_datalake');
define("CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_UPLOAD", 'lambda_upload');
define("CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_MACHINE_LEARNING", 'lambda_machine_learning');
define("CLOUD_EXISTCHECK_QUEUETYPE_LAMBDA_PERMISSION", 'lambda_permission');
define("CLOUD_EXISTCHECK_QUEUETYPE_GLUE_MACHINE_LEARNING", 'glue_machine_learning');
define("CLOUD_EXISTCHECK_QUEUETYPE_GLUE_DB", 'glue_db');
define("CLOUD_EXISTCHECK_QUEUETYPE_GLUE_CRAWLER", 'glue_crawler');

//-----------------------------------
// その他
//-----------------------------------
define("PATH_DIRECTIONS_ORDER", '/directions/order');
define("PATH_DIRECTIONS_GROUP", '/directions/group');
define("PATH_DIRECTIONS_PRINTER", '/directions/printer');
define("PATH_IMAGE_PRESS", '/images/press');
define("PATH_IMAGE_POSTPRESS", '/images/postpress');
define("CMD_WAIT_TIME", 10000);
define("CMD_TIMEOUT", 600000000);
define("FS_RETRY_COUNT", 20);
define("FS_RETRY_WAIT_TIME", 500000);
define("RESIDENT_DIR", '/tmp');
define("SYS_SESSION_DIR", dirname(__FILE__).'/../../cgi-work');
define("FORTUNE_RSC_MIN", 1);
define("FORTUNE_RSC_MAX", 7);
define("FORTUNE_RSC_WORST", 7);
define("FORTUNE_RSC_CONTEXT_MIN", 0);
define("FORTUNE_RSC_CONTEXT_MAX", 4);
define("ACCOUNT_INI_SEC", 'ACCOUNT');

?>

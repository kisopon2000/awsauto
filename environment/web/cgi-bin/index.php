<?php

require_once dirname(__FILE__)."/ext.php";
require_once dirname(__FILE__)."/lib/sys/XCgiSys.php";

try{
    if(($ret = XCgiSys::sysinit()) != 0) return $ret;
    $launcher = new $_REQUEST[XCgiSys::getApi()];
    if(($ret = $launcher->initialize()) != 0) return $ret;
    if(($ret = $launcher->run()) != 0) return $ret;
    if(($ret = $launcher->finalize()) != 0) return $ret;
}catch(Exception $e){
    XCgiSys::redirect(ERR_RTN_INTERNAL_SERVER_ERROR, '<!> '.$e->getMessage());
}

?>

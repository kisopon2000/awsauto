<?php

//==========================================================================
// Add your file.
//==========================================================================
require_once 'func/heartbeat/index.php';
require_once 'func/auth/index.php';
require_once 'func/fortune/index.php';
require_once 'func/environment/index.php';
require_once 'func/environment/list/index.php';
require_once 'func/environment/s3/index.php';
require_once 'func/environment/lambda/index.php';
require_once 'func/environment/glue/index.php';


//==========================================================================
// Register api and class. (YourClass is defined above file)
//   URL : /api/<YourApi>
//     -> $_REQUEST[<YourApi>] = YourClass
//==========================================================================
$_REQUEST['heartbeat'] = HeartBeatLauncher;
$_REQUEST['auth'] = AuthLauncher;
$_REQUEST['fortune'] = FortuneLauncher;
$_REQUEST['environment'] = EnvironmentLauncher;
$_REQUEST['environment/list'] = EnvironmentListLauncher;
$_REQUEST['environment/s3'] = EnvironmentS3Launcher;
$_REQUEST['environment/lambda'] = EnvironmentLambdaLauncher;
$_REQUEST['environment/glue'] = EnvironmentGlueLauncher;


//==========================================================================
// You have to include 'XCgiLauncher.php' and extends 'XCgiLauncher' class.
//   ---------------------------------------------
//    require_one 'launcher/XCgiLauncher.php';
//
//    class YourClass extends XCgiLauncher{
//      ...
//      // You have to override below methods.
//      int run();
//      ...
//    }
//   ---------------------------------------------
//==========================================================================

?>

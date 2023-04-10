<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Origin, Content-Type, Authorization, access-control-allow-origin");


$app = require __DIR__.'/../bootstrap/app.php';

define('ROOT_PATH', dirname(__DIR__));

/*
@@ -32,4 +43,4 @@
|
*/

$app->run();

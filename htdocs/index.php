<?php
header("Content-Type:text/html; charset=utf-8");
error_reporting(0);
require '../SinglePHP.class.php';
$config = array(
        'APP_PATH' => '../app/',
        'USE_SESSION' => true,
        'SHOW_LOAD_TIME' => true,
    );
SinglePHP::getInstance($config) -> run();

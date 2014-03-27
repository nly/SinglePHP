<?php
header("Content-Type:text/html; charset=utf-8");
include '../SinglePHP.class.php';
$config = array(
        'APP_PATH' => '../app/',
        'USE_SESSION' => true
    );
SinglePHP::getInstance($config) -> run();

<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

$vendorAutoload = 'D:/OpenServer6.5.0/home/64web-2026.loc/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    $loader = require $vendorAutoload;
    $loader->addPsr4('larikmc\\metrika\\', dirname(__DIR__) . '/src', true);
    $loader->addPsr4('larikmc\\metrika\\tests\\', __DIR__);
}

require_once 'D:/OpenServer6.5.0/home/64web-2026.loc/vendor/yiisoft/yii2/Yii.php';

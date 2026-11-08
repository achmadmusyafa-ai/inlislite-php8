<?php
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

// --- INLISLite PHP 8: mode produksi -------------------------------------------
// Jangan tampilkan notice/warning/deprecation dari kode legacy ke browser
// (menghindari "headers already sent"). Fatal error tetap dicatat di error_log.
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
// ------------------------------------------------------------------------------

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/common/config/bootstrap.php');
require(__DIR__ . '/frontend/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/common/config/main.php'),
    require(__DIR__ . '/common/config/main-local.php'),
    require(__DIR__ . '/frontend/config/main.php'),
    require(__DIR__ . '/frontend/config/main-local.php')
);

$application = new yii\web\Application($config);
$application->run();
?>
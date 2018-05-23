<?php


$sapiName = php_sapi_name();
$sapiName = 'cli';

if (substr($sapiName, 0, 3) != 'cli') {
    die("Cron can be run only via CLI");
}

include "bootstrap.php";

$app = new \Core\Core\Application();
$app->runCron();


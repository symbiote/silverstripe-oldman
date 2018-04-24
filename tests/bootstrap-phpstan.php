<?php

$PROJECT_DIR = dirname(__FILE__).'/../..';
/**
 * This file is required to setup Silverstripe class autoloader
 */
$CORE_FILEPATH = $PROJECT_DIR.'/silverstripe/framework/src/includes/autoload.php';
if (!file_exists($CORE_FILEPATH)) {
    echo 'Unable to find "vendor/silverstripe/framework" folder for Silverstripe 4.X project.';
    exit;
}
require_once $CORE_FILEPATH;

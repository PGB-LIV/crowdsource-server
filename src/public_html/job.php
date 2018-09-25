<?php
use pgb_liv\crowdsource\Allocator\WorkUnitAllocator;

/**
 * Copyright 2018 University of Liverpool
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
error_reporting(E_ALL);
ini_set('display_errors', true);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

function fatal_handler()
{
    $error = error_get_last();

    if ($error === NULL) {
        return;
    }

    file_put_contents('/home/andrew/public_html/crowdsource-server/src/php_fatal.log', implode('::::', $error),
        FILE_APPEND);
}

register_shutdown_function("fatal_handler");

header('Content-Type: application/json');

$requestType = 'unknown';
if (isset($_GET['r'])) {
    $requestType = $_GET['r'];
}

$callback = 'parseResult';
if (isset($_GET['callback'])) {
    $callback = $_GET['callback'];
}

try {

    $workUnitAllocator = new WorkUnitAllocator($adodb);
    $response = $workUnitAllocator->getJsonResponse($requestType);
    echo $callback . '(' . $response . ');';
} catch (Exception $e) {
    file_put_contents('/home/andrew/public_html/crowdsource-server/src/php_exception.log', $e->getMessage() . PHP_EOL,
        FILE_APPEND);
    file_put_contents('/home/andrew/public_html/crowdsource-server/src/php_exception.log', $e->getTraceAsString() . PHP_EOL,
        FILE_APPEND);
}

$ip = 0;
if (isset($_SERVER['REMOTE_ADDR'])) {
    $ip = ip2long($_SERVER['REMOTE_ADDR']);
} else {
    // If REMOTE_ADDR is missing then either the server is configured wrong or we are in a unit test
    $ip = ip2long('127.0.0.1');
}

$column = 'requests';
if ($requestType == 'result') {
    $column = 'results';
}

$agent = '""';
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $agent = $adodb->quote($_SERVER['HTTP_USER_AGENT']);
}

$host = '""';
if (isset($_SERVER['HTTP_REFERER'])) {
    $components = parse_url($_SERVER['HTTP_REFERER']);
    $host = $adodb->quote($components['host']);
}

// Analytics
$jobId = $workUnitAllocator->getJob();
$date = date('Y-m-d');

$adodb->Execute(
    'INSERT INTO `analytic_host` (`job`, `date`, `host`, `' . $column . '`) VALUES (' . $jobId . ', "' . $date . '", ' .
    $host . ', 1) ON DUPLICATE KEY UPDATE `' . $column . '`=`' . $column . '`+1');
$adodb->Execute(
    'INSERT INTO `analytic_agent` (`job`, `date`, `agent`, `' . $column . '`) VALUES (' . $jobId . ', "' . $date . '",' .
    $agent . ', 1) ON DUPLICATE KEY UPDATE `' . $column . '`=`' . $column . '`+1');
$adodb->Execute(
    'INSERT INTO `analytic_ip` (`job`, `date`, `ip`, `' . $column . '`) VALUES (' . $jobId . ', "' . $date . '",' . $ip .
    ', 1) ON DUPLICATE KEY UPDATE `' . $column . '`=`' . $column . '`+1');

// logging
//$result = isset($_GET['result']) ? $_GET['result'] : null;
//$adodb->Execute(    'INSERT INTO `log` (`type`, `request`, `response`) VALUES (' . $adodb->quote($requestType) . ', ' .    $adodb->quote($result) . ', ' . $adodb->quote($response) . ')');

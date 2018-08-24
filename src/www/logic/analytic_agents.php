<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', true);

$jobId = 1;
if (isset($_GET['job'])) {
    $jobId = $_GET['job'];
}

$results = $adodb->Execute(
    'SELECT `agent`, SUM(`requests`) AS `requests`, SUM(`results`) AS `results` FROM `analytic_agent` WHERE `job` = ' .
    $jobId);

echo 'UserAgent,Requests,Results' . PHP_EOL;
foreach ($results as $value) {
    echo $value['agent'] . ',';
    echo $value['requests'] . ',';
    echo $value['results'] . PHP_EOL;
}

exit();

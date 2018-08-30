<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', true);

$jobId = 1;

if (isset($_GET['job'])) {
    $jobId = $_GET['job'];
}

$resultsPath = DATA_PATH . '/' . $jobId . '/results.csv';

if (!file_exists($resultsPath)) {
    die('No results');
}
echo file_get_contents($resultsPath);
exit();

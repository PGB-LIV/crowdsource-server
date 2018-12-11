<?php
$jobId = 1;

if (isset($_GET['job'])) {
    $jobId = $_GET['job'];
}

$format = 'csv';
$mime = 'text/plain';
if (isset($_GET['format']) && $_GET['format'] == 'mzid') {
    $format = $_GET['format'];
    $mime = 'application/xml';
}

$resultsPath = DATA_PATH . '/' . $jobId . '/results/results.' . $format;

if (! file_exists($resultsPath)) {
    http_response_code(404);
    die('No results');
}

header('Content-Type: ' . $mime);
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=results_" . $jobId . ".mzid");

echo file_get_contents($resultsPath);
exit();

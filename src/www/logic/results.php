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

header('Content-Type: ' . $mime);

$resultsPath = DATA_PATH . '/' . $jobId . '/results.' . $format;

if (! file_exists($resultsPath)) {
    die('No results');
}
echo file_get_contents($resultsPath);
exit();

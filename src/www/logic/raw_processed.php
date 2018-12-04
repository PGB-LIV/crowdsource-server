<?php
$jobId = 1;

if (isset($_GET['job'])) {
    $jobId = $_GET['job'];
}

$resultsPath = DATA_PATH . '/' . $jobId . '/results/processed.mgf';

if (! file_exists($resultsPath)) {
    http_response_code(404);
    die('No results');
}

header('Content-Type: text/plain');
echo file_get_contents($resultsPath);
exit();

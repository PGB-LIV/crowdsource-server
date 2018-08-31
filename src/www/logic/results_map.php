<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', true);

$jobId = 1;
if (isset($_GET['job'])) {
    $jobId = $_GET['job'];
}

$results = $adodb->Execute('SELECT `ip`, `requests`, `results` FROM `analytic_ip` WHERE `job` = ' . $jobId);

$country = array();

foreach ($results as $result) {
    $mapping = geoip_record_by_name($result['ip']);

    $key = $mapping['latitude'] . ',' . $mapping['longitude'];

    if ($mapping['city'] == '')
    {
        continue;
    }
    
    if (! isset($country[$key])) {
        $country[$key] = array(
            'city' => $mapping['city'],
            'country' => $mapping['country_name'],
            'requests' => 0,
            'results' => 0,
            'users' => 0
        );
    }
    
    $country[$key]['requests'] += $result['requests'];
    $country[$key]['results'] += $result['results'];
    $country[$key]['users'] ++;
}

echo 'Latitude,Longitude,City,Country,Users,Requests,Results' . PHP_EOL;
foreach ($country as $key => $value) {
    $location = explode(',', $key);

    echo $location[0] . ',';
    echo $location[1] . ',';
    echo $value['city'] . ',';
    echo $value['country'] . ',';
    echo $value['users'] . ',';
    echo $value['requests'] . ',';
    echo $value['results'] . PHP_EOL;
}

exit();

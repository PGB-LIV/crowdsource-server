<?php
header('Content-Type: text/plain');

$jobId = 1;
if (isset($_GET['job'])) {
    $jobId = $_GET['job'];
}

$users = file_get_contents(DATA_PATH . '/' . $jobId . '/results/user.json');
$results = json_decode($users, true);

$country = array();

foreach ($results as $result) {
    if ($result['ip'] == '0') {
        continue;
    }

    $mapping = geoip_record_by_name($result['ip']);

    $key = $mapping['latitude'] . ',' . $mapping['longitude'];

    if ($mapping['city'] == '') {
        continue;
    }

    if (! isset($country[$key])) {
        $country[$key] = array(
            'city' => $mapping['city'],
            'country' => $mapping['country_name'],
            'results' => 0,
            'users' => 0
        );
    }

    $country[$key]['results'] += $result['workunits'];
    $country[$key]['users'] ++;
}

echo 'Latitude,Longitude,City,Country,Users,Results' . PHP_EOL;
foreach ($country as $key => $value) {
    $location = explode(',', $key);

    echo $location[0] . ',';
    echo $location[1] . ',';
    echo $value['city'] . ',';
    echo $value['country'] . ',';
    echo $value['users'] . ',';
    echo $value['results'] . PHP_EOL;
}

exit();

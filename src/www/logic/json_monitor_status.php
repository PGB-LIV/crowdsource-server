<?php
$result = array();

$currentJob = $adodb->GetRow(
    'SELECT `id`, `workunits_created`, `workunits_returned`, `created_at` FROM `job_queue` WHERE `state` != "COMPLETE" && `state` != "NEW"');

$result['job_current'] = empty($currentJob) ? 'NONE' : $currentJob['id'];

if (! empty($currentJob)) {
    $result['job_progress'] = $currentJob['workunits_returned'] == 0 ? '0' : floor(
        ($currentJob['workunits_returned'] / $currentJob['workunits_created']) * 100);
    $result['job_start'] = $currentJob['created_at'];
}

$queuedJob = $adodb->GetOne('SELECT COUNT(*) FROM `job_queue` WHERE `state` = "NEW"');

$result['jobs_queued'] = $queuedJob;

$completedJobs = $adodb->GetRow(
    'SELECT COUNT(*) AS `count`, SUM(`workunits_returned`) AS `scans_count`, MAX(`process_end`) AS `last_job`, MIN(`created_at`) as `first_job` FROM `job_queue` WHERE `state` = "COMPLETE"');

$result['jobs_completed'] = $completedJobs['count'];
$result['scans_completed'] = $completedJobs['scans_count'];
$result['last_job_at'] = $completedJobs['last_job'];
$result['first_job_at'] = $completedJobs['first_job'];

$result['last_jobs'] = $adodb->GetAll(
    'SELECT `id` FROM `job_queue` WHERE `state` = "COMPLETE" ORDER BY `process_end` DESC LIMIT 0, 10');

foreach ($result['last_jobs'] as $index => $lastJob) {
    $jobId = $lastJob['id'];

    if (! file_exists(DATA_PATH . '/' . $jobId . '/results/precursor.json')) {
        $lastJob['precursors'] = 'Pending';
        $lastJob['duration'] = 'Pending';
        $lastJob['data'] = 'Pending';
        continue;
    }

    $jsonObj = json_decode(file_get_contents(DATA_PATH . '/' . $jobId . '/results/precursor.json'), JSON_OBJECT_AS_ARRAY);
    $lastJob['precursors'] = count($jsonObj);
    $duration = 0;
    $data = 0;
    foreach ($jsonObj as $stat) {
        $duration += $stat['cpu_total'];
        $data += $stat['bytes_sent'];
        $data += $stat['bytes_received'];
    }

    $lastJob['duration'] = $duration;
    $lastJob['data'] = $data;

    $result['last_jobs'][$index] = $lastJob;
}

header('Content-type: application/json;');
echo json_encode($result);
exit();
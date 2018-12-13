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
    'SELECT `id`, `workunits_returned`, SEC_TO_TIME(TIMESTAMPDIFF(SECOND, `process_start`, `process_end`)) AS `duration` FROM `job_queue` WHERE `state` = "COMPLETE" ORDER BY `process_end` DESC LIMIT 0, 5');

header('Content-type: application/json;');
echo json_encode($result);
exit();
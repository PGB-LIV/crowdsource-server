<html>
<head>
<meta http-equiv="refresh" content="30">
<script
    src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script
    src="http://pgb.liv.ac.uk/~andrew/crowdsource-js/src/crowdsearch.js"></script>
</head>
<h1>Job Monitor</h1>
<?php
$jobId = (int) $_GET['job'];

$state = $adodb->GetRow('SELECT `phase`, `state` FROM `job_queue` WHERE `id` = ' . $jobId);

if (empty($state)) {
    die('Job not found');
}

$queuePosition = $adodb->GetOne('SELECT COUNT(*) FROM `job_queue` WHERE `id` < ' . $jobId . ' && `state` != "COMPLETE"');

if ($state['phase'] == 0) {
    echo '<p>Your job is currently in the queue for pre-processing.</p>';
}

if ($state['phase'] == 1 && $state['state'] == 'PREPARING') {
    echo '<p>Your job is currently being prepared.</p>';
    echo '<p>You are position ' . $queuePosition . ' in queue for processing once complete.</p>';
}

if ($state['phase'] == 1 && $state['state'] == 'READY') {
    
    if ($queuePosition > 0) {
        echo '<p>Your job is now queued for processing. You are position ' . $queuePosition . ' in queue.</p>';
    } else {
        echo '<p>Your job has started! It may take a few minutes before any progress is shown.</p>';
        $progress = $adodb->GetAssoc('SELECT `status`, COUNT(`status`) FROM `workunit1` WHERE `job` = ' . $jobId . ' GROUP BY `status`');
        
        echo 'Total work: ' . number_format(array_sum($progress)) . '<br />';
        echo 'Completed: ' . number_format($progress['COMPLETE']) . ' (' . number_format($progress['COMPLETE'] / array_sum($progress) * 100, 2) . '%)<br />';
        echo 'Processing: ' . number_format($progress['ASSIGNED']) . ' (' . number_format($progress['ASSIGNED'] / array_sum($progress) * 100, 2) . '%)<br />';
        echo 'Awaiting: ' . number_format($progress['UNASSIGNED']) . ' (' . number_format($progress['UNASSIGNED'] / array_sum($progress) * 100, 2) . '%)<br />';
        
        $stat = $adodb->GetRow('SELECT UNIX_TIMESTAMP(MIN(`completed_at`)) AS `start`, UNIX_TIMESTAMP(MAX(`completed_at`)) AS `finish` FROM `workunit1` WHERE `job` = ' . $jobId);
        $duration = $stat['finish'] - $stat['start'];
        $remaining = (($duration / $progress['COMPLETE']) * array_sum($progress)) - $duration;
        
        $minutes = floor($duration / 60);
        $seconds = ($duration) - ($minutes * 60);
        
        echo '<p>Search running for ' . number_format($minutes) . ' minutes, ' . $seconds . ' seconds.</p>';
        $minutes = floor($remaining / 60);
        $seconds = floor(($remaining) - ($minutes * 60));
        
        echo '<p>Estimated to complete in ' . number_format($minutes) . ' minutes, ' . $seconds . ' seconds.</p>';
    }
}

if ($state['phase'] == 1 && $state['state'] == 'DONE') {
    $stat = $adodb->GetRow('SELECT UNIX_TIMESTAMP(MIN(`completed_at`)) AS `start`, UNIX_TIMESTAMP(MAX(`completed_at`)) AS `finish` FROM `workunit1` WHERE `job` = ' . $jobId);
    
    $minutes = floor(($stat['finish'] - $stat['start']) / 60);
    $seconds = ($stat['finish'] - $stat['start']) - ($minutes * 60);
    echo '<p>Search completed in ' . number_format($minutes) . ' minutes, ' . $seconds . ' seconds.</p>';
    
    echo '<p>Search results are being prepared.</p>';
}

if ($state['phase'] == 1 && $state['state'] == 'COMPLETE') {
    $stat = $adodb->GetRow('SELECT UNIX_TIMESTAMP(MIN(`completed_at`)) AS `start`, UNIX_TIMESTAMP(MAX(`completed_at`)) AS `finish` FROM `workunit1` WHERE `job` = ' . $jobId);
    
    $minutes = floor(($stat['finish'] - $stat['start']) / 60);
    $seconds = ($stat['finish'] - $stat['start']) - ($minutes * 60);
    echo '<p>Search completed in ' . number_format($minutes) . ' minutes, ' . $seconds . ' seconds.</p>';
    
    echo '<p>Analyse results <a href="http://pgb.liv.ac.uk/shiny/ash/?id=' . $jobId . '">here</a></p>';
}

exit();
?>
<h1>Job Monitor</h1>
<?php
$jobId = (int) $_GET['job'];

$state = $adodb->GetRow('SELECT `phase`, `state` FROM `job_queue` WHERE `id` = ' . $jobId);

$queuePosition = $adodb->GetOne('SELECT COUNT(*) FROM `job_queue` WHERE `id` < ' . $jobId . ' && `state` != "COMPLETE"');

if ($state['phase'] == 0) {
    echo '<p>Your job is currently in the queue for pre-processing.</p>';
}

if ($state['phase'] == 1 && $state['state'] == 'PREPARING') {
    echo '<p>Your job is currently being prepared.</p>';
    echo '<p>You are position ' . $queuePosition . ' in queue for processing once complete.</p>';
}

if ($state['phase'] == 1 && $state['state'] == 'READY') {
    
    if ($queuePosition > 1) {
        echo '<p>Your job is now queued for processing. You are position ' . $queuePosition . ' in queue.</p>';
    } else {
        echo '<p>Your job has started! It may take a few minutes before any progress is shown.</p>';
        $progress = $adodb->GetAssoc('SELECT `status`, COUNT(*) FROM `workunit1` WHERE `job` = ' . $jobId . ' GROUP BY `status`');
        
        echo 'Total work: ' . array_sum($progress) . '<br />';
        echo 'Completed: ' . $progress['COMPLETE'] . '<br />';
        echo 'Processing: ' . $progress['ASSIGNED'] . '<br />';
        echo 'Awaiting: ' . $progress['UNASSIGNED'] . '<br />';
    }
}

if ($state['phase'] == 1 && $state['state'] == 'COMPLETE') {    
    echo '<p>Your job has finished.</p>';
    
    echo '<p>Analyse results <a href="http://pgb.liv.ac.uk/shiny/ash/?data=http://pgb.liv.ac.uk/~andrew/crowdsource-server/src/public_html/?page=results&job=' . $jobId . '">here</a></p>';
}

exit();
?>
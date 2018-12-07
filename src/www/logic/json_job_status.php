<?php
$id = (int) $_GET['id'];

$status = $adodb->GetRow(
    'SELECT `state`, `workunits_returned`, `workunits_created`, `raw_file` FROM `job_queue` WHERE `id` = ' . $id);

$json = array();

$json['status'] = $status['state'];
$json['progress_curr'] = $status['workunits_returned'];
$json['progress_max'] = $status['workunits_created'];
$json['file'] = substr($status['raw_file'], strrpos($status['raw_file'], '/') + 1);

header('Content-type: application/json;');
echo json_encode($json);
exit();

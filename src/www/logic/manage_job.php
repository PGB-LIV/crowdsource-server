<?php
session_start();
if (isset($_SESSION["userId"])) {
    $userId = $_SESSION["userId"];
    $query = "SELECT * FROM users WHERE user_id = '$userId'";
    $rs = $adodb->Execute($query);
    $name = $rs->fields['name'];
    $email = $rs->fields['email'];
    
    $smarty->assign('name', $name);
    $smarty->assign('email', $email);
} else {
    header("Location: index.php?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['jobchecks'])) {
        $jobchecks = $_POST['jobchecks'];
        if (isset($_POST['Pause'])) {
            for ($i = 0; $i < count($jobchecks); $i ++) {
                $id = intval($jobchecks[$i]);
                $query = "SELECT status, old_status FROM job_queue WHERE id='$id'";
                $rs = $adodb->Execute($query);
                $status = $rs->fields['status']; // current status
                $oldstatus = $rs->fields['old_status'];
                if ($status == 'paused') {
                    // then unpause it
                    $query = "UPDATE job_queue SET status= '$oldstatus'WHERE id='$id'";
                } else {
                    // pause it
                    $query = "UPDATE job_queue SET status='paused',old_status='$status' WHERE id='$id'";
                }
                $rs = $adodb->Execute($query);
            }
        }
        
        if (isset($_POST['Delete'])) {
            for ($i = 0; $i < count($jobchecks); $i ++) {
                $id = intval($jobchecks[$i]);
                $query = "UPDATE job_queue SET status='to_be_deleted' WHERE id='$id'";
                $rs = $adodb->Execute($query);
            }
        }
    }
}

$visiString = "style='visibility: hidden'";

$query = "SELECT * FROM job_queue WHERE customer='$userId'";
$rs = $adodb->Execute($query);
$i = 0;
$jobsArray[0]['job_title'] = "xx-nojob-xx"; // in case none are found

while (! $rs->EOF) {
    $jobsArray[$i]['job_id'] = $rs->fields['id'];
    $jobsArray[$i]['job_title'] = $rs->fields['job_title'];
    
    $jobsArray[$i]['job_time'] = strtok($rs->fields['job_time'], ".");
    $jobsArray[$i]['database_file'] = basename($rs->fields['database_file']);
    $jobsArray[$i]['raw_file'] = basename($rs->fields['raw_file']);
    $jobsArray[$i]['enzyme'] = $rs->fields['enzyme'];
    $jobsArray[$i]['miss_cleave_max'] = $rs->fields['miss_cleave_max'];
    $jobsArray[$i]["charge"] = $rs->fields['charge_min'] . " - " . $rs->fields['charge_max'];
    $jobsArray[$i]['tolerance'] = $rs->fields['mass_tolerance'];
    $jobsArray[$i]['status'] = $rs->fields['status'];
    
    $rs->MoveNext();
    $i ++;
}
// var_dump($i);
if ($i) {
    $visiString = "";
}
// var_dump($jobsArray);

$smarty->assign('jobrecords', $jobsArray);
$smarty->assign('shown', $visiString);
    
    
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
    
    
    
    
    
    

<?php

class JobCreate
{
    // Andrew wants the jobcreate in a class --- or perhaps a constructor of class job would be better.
    public $userId;

    public $jobname;

    public $fastafile;

    public $rawfile;

    public $enzyme;

    public $missCleave;
    
    // public $chargeMin;
    // public $chargeMax;
    // public $tolerance;
    function __construct($id, $name, $fast, $raw, $enz = '1', $missedC = '2')
    {
        $this->$userId = $id;
        $this->$jobname = $name;
        $this->$fastafile = $fast;
        $this->$rawfile = $raw;
        $this->$enzyme = $enz;
        $this->$missCleave = $missedC;
    }
}

session_start();

// define variables set to empty values
$nameErr = $emailErr = $jobnameErr = $fastafileErr = $rawfileErr = "";
$name = $email = $jobname = $fastafile = $rawfile = "";
$errorNum = 0;
$userId = 0;
$procString = "";
$visiString = "style='visibility: hidden'";

$smarty->assign('name', $name);
$smarty->assign('email', $email);
$smarty->assign('jobname', $jobname);
$smarty->assign('fastafile', $fastafile);
$smarty->assign('rawfile', $rawfile);
$smarty->assign('procString', $procString);
$smarty->assign("shown", $visiString);

$smarty->assign('namecolor', 'black');
$smarty->assign('emailcolor', 'black');
$smarty->assign('jobcolor', 'black');
$smarty->assign('fastacolor', 'black');
$smarty->assign('rawcolor', 'black');

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

$fastaArray = glob("/mnt/nas/crowdsource/databases/curated/*.*");
for ($i = 0; $i < count($fastaArray); $i ++) {
    $fastaArray[$i] = basename($fastaArray[$i]);
}
$smarty->assign('fastaArray', $fastaArray);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["jobname"])) {
        $jobnameErr = "Job Name is required";
        $errorNum ++;
    } else {
        $jobname = test_input($_POST["jobname"]);
        // check if name only contains letters and whitespace
        if (! preg_match("/^[a-zA-Z0-9 ]*$/", $jobname)) {
            $jobnameErr = "Only letters, numbers and white space allowed";
            $errorNum ++;
        }
    }
    
    if (! empty($jobnameErr)) {
        $jobname = $jobnameErr;
        $smarty->assign('jobcolor', 'red');
    }
    
    $enzyme = intval($_POST["enzyme"]);
    $missedCleave = intval($_POST["missedCleave"]);
    $charge = intval($_POST["charge"]);
    $tolerance = intval($_POST['tolerance']);
    
    if ($_POST['fastasel'] == 'custom' && $_FILES["fastafile"]["name"] == '') {
        $fastafileErr = "Please specify a database";
        $errorNum ++;
    }
    
    if ($_FILES["rawfile"]["name"] == "") {
        $rawfileErr = "Please specify a raw MS data file ";
        $errorNum ++;
    }
    
    if ($errorNum <= 0) {
        
        $fastaBaseDir = "/mnt/nas/crowdsource/databases/";
        $rawBaseDir = "/mnt/nas/crowdsource/rawmgf/";
        $fastaNewDir = $fastaBaseDir . "user" . $userId . "/";
        
        if (! file_exists($fastaNewDir)) {
            mkdir($fastaNewDir);
        }
        
        $rawNewDir = $rawBaseDir . "user" . $userId . "/";
        if (! file_exists($rawNewDir)) {
            mkdir($rawNewDir);
        }
        
        if ($_POST['fastasel'] == 'custom') {
            $fastapath = $fastaNewDir . basename($_FILES["fastafile"]["name"]);
            move_uploaded_file($_FILES["fastafile"]["tmp_name"], $fastapath);
        } else {
            $fastaSource = "/mnt/nas/crowdsource/databases/curated/" . $_POST['fastasel'];
            $fastapath = $fastaNewDir . $_POST['fastasel'];
            if (! copy($fastaSource, $fastapath)) {
                var_dump($fastaSource);
            }
        }
        
        $rawpath = $rawNewDir . basename($_FILES["rawfile"]["name"]);
        move_uploaded_file($_FILES["rawfile"]["tmp_name"], $rawpath);
        
        $query = "INSERT INTO job_queue (customer,job_title,database_file,raw_file,enzyme,miss_cleave_max,charge_min,charge_max,mass_tolerance)";
        $query .= " VALUES ('$userId','$jobname','$fastapath','$rawpath','$enzyme','$missedCleave',0,'$charge','$tolerance')";
        
        $rs = $adodb->Execute($query);
        
        $procString = "Parsing and processing job upload";
        $visiString = "";
    }
    
    $smarty->assign('name', $name);
    $smarty->assign('email', $email);
    $smarty->assign('jobname', $jobname);
    $smarty->assign('fastafile', $fastafile);
    $smarty->assign('rawfile', $rawfile);
    $smarty->assign('procString', $procString);
    $smarty->assign('shown', $visiString);
}

function logJobInDB()
{
    global $name, $email, $jobname, $enzyme, $missedCleave, $userId;
    global $rawfile, $fastafile, $procString, $adodb, $visiString;
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

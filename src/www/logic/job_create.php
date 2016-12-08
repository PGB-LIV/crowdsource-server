

<?php

// include ('Smarty.class.php')
// $smarty = new Smarty;

// define variables set to empty values
$nameErr = $emailErr = $jobnameErr = $fastafileErr = $rawfileErr = "";
$name = $email = $jobname = $fastafile = $rawfile = "";
$errorNum = 0;
$procString = "";

$smarty->assign('name', $name);
$smarty->assign('email', $email);
$smarty->assign('jobname', $jobname);
$smarty->assign('fastafile', $fastafile);
$smarty->assign('rawfile', $rawfile);

$smarty->assign('namecolor', 'black');
$smarty->assign('emailcolor', 'black');
$smarty->assign('jobcolor', 'black');
$smarty->assign('fastacolor', 'black');
$smarty->assign('rawcolor', 'black');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // var_dump($_FILES);
    
    // Check name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
        $errorNum ++;
    } else {
        $name = test_input($_POST["name"]);
        // check if name only contains letters and whitespace
        if (! preg_match("/^[a-zA-Z ]*$/", $name)) {
            $nameErr = "Only letters and white space allowed";
            $errorNum ++;
        }
    }
    if (! empty($nameErr)) {
        $name = $nameErr;
        $smarty->assign('namecolor', 'red');
        $errorNum ++;
    }
    
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
        $errorNum ++;
    } else {
        $email = test_input($_POST["email"]);
        // check if e-mail address is well-formed
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
            $errorNum ++;
        }
    }
    if (! empty($emailErr)) {
        $email = $emailErr;
        $smarty->assign('emailcolor', 'red');
    }
    
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
    
    if (empty($_POST["fastafile"])) {
        $fastafileErr = "Please specify a database";
        $errorNum ++;
    } else {
        $fastafile = test_input($_POST["fastafile"]);
    }
    if (! empty($fastafileErr)) {
        $fastafile = $fastafileErr;
        $smarty->assign('fastacolor', 'red');
    }
    
    if (empty($_POST["rawfile"])) {
        $rawfileErr = "Please specify a raw MS data file ";
        $errorNum ++;
    } else {
        $rawfile = ($_POST["rawfile"]);
    }
    if (! empty($rawfileErr)) {
        $rawfile = $rawfileErr;
        $smarty->assign('jobcolor', 'red');
    }
    
    if ($errorNum <= 0) {
        logJobInDB();
    }
    
    $smarty->assign('name', $name);
    $smarty->assign('email', $email);
    $smarty->assign('jobname', $jobname);
    $smarty->assign('fastafile', $fastafile);
    $smarty->assign('rawfile', $rawfile);
    $smart->assign('procString', $procString);
    
    $smarty->display('newjob.tpl');
}

function logJobInDB()
{
    global $name, $email, $jobname;
    global $rawfile, $fastafile, $procString;
    
    $fastapath = "database/" . $fastafile;
    $rawpath = "rawdata/" . $rawfile;
    
    // $mysqli = new mysqli('localhost', 'crowdsourcing', 'fVenpEJ710RSKGXw', 'crowdsourcing');
    // if ($mysqli->connect_errno) {
    // echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    // } else {
    // echo "Connected to Database";
    
    $query = "INSERT INTO job_queue (customer,email,job_title,raw_file,database_file) VALUES ('$name', '$email', '$jobname','$rawpath','$fastapath')";
    $rs = $adodb->Execute($query);
    if (! $rs) {
        echo "<br>Values not successfully written";
    } else {
        echo "<br>Value successfully written";
    }
    
    $procString = "Parsing and processing job upload";
    
    /*
     *
     * $target_dir = "databases/";
     * $target_file = $target_dir . basename($_FILES["fastafile"]["name"]);
     *
     * move_uploaded_file($_FILES["fastafile"]["tmp_name"],$target_file);
     *
     */
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


	








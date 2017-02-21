
<?php
session_start();

$nameErr = $emailErr = "";
$name = $email = "";
$errorNum = 0;
$procString = "";
$nextString = "";
$userId = 0;
$visiString = "style='visibility: hidden'";
$nextPage = "#";

$smarty->assign('name', $name);
$smarty->assign('email', $email);
$smarty->assign('procString', $procString);
$smarty->assign('nextString', $nextString);
$smarty->assign('nextPage', $nextPage);
$smarty->assign("shown", $visiString);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    }
    // check email address (alpha numerically etc..
    
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
    }
    
    if ($errorNum <= 0) {
        // check if registered already on table users (use email?)
        $query = "SELECT * FROM users WHERE email = '$email'";
        $rs = $adodb->Execute($query);
        if (count($rs->fields) > 1) {
            $userId = $rs->fields['0'];
            $procString = "Welcome back " . $rs->fields['name']; // $name;
            $nextString = "Manage Job(s)";
            $nextPage = "index.php?page=manage_job"; // &userid=".$userId;
        } else {
            // else register them amd welcome them for the first time
            $query = "INSERT INTO users (name, email) VALUES ('$name','$email')";
            $rs = $adodb->Execute($query);
            $query = "SELECT * FROM users WHERE email = '$email'";
            $rs = $adodb->Execute($query);
            
            $userId = $rs->fields['user_id'];
            $procString = "Welcome " . $rs->fields['name'];
            $nextString = "Create Job";
            $nextPage = "index.php?page=job_create"; // &userid=".$userId;
        }
        $visiString = "";
        $_SESSION["userId"] = $userId;
    }
}

$smarty->assign('name', $name);
$smarty->assign('email', $email);
$smarty->assign('procString', $procString);
$smarty->assign('nextString', $nextString);
$smarty->assign('nextPage', $nextPage);
$smarty->assign('shown', $visiString);

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

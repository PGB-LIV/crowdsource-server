<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

require_once '../conf/config.php';
require_once '../conf/adodb.php';
require_once '../conf/smarty.php';

$page = 'welcome';

if (isset($_REQUEST['page']))
{
    $page = $_REQUEST['page'];
}

require_once '../www/logic/'. $page .'.php';

require_once '../www/template/'. $page .'.tpl';

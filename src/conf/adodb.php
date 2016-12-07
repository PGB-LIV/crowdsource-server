<?php
require_once '../lib/vendor/adodb/adodb-php/adodb.inc.php';

$adodb = newAdoConnection(DB_DRIVER);
$adodb->Connect(DB_HOST, DB_USER, DB_PASS, DB_DB);

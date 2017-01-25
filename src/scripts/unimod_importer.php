<?php
/**
 * Copyright 2016 University of Liverpool
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
error_reporting(E_ALL);
ini_set('display_errors', true);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

use pgb_liv\crowdsource\UnimodImport;

$unimod = new UnimodImport();
echo 'Fetching schema... ';
$schemaPath = $unimod->fetchRemoteData(SCHEMA_URL);
echo 'Done' . "\n" . 'Saved to ' . $schemaPath . "\n\n";

echo 'Parsing schema... ';
$schema = $unimod->getSqlSchema($schemaPath);
echo 'Done' . "\n\n";

echo 'Inserting schema... ';
foreach ($schema as $query) {
    $res = $adodb->Execute($query);
    if (! $res) {
        die($adodb->ErrorMsg() . "\n" . $query);
    }
}

echo 'Done' . "\n\n";

echo 'Fetching data... ';
$dataPath = $unimod->fetchRemoteData(DATA_URL);
echo 'Done' . "\n" . 'Saved to ' . $dataPath . "\n";

echo 'Parsing schema... ';
$data = $unimod->getSqlData($dataPath, $adodb);
echo 'Done' . "\n\n";

echo 'Inserting data... ';
foreach ($data as $tableName => $table) {
    echo "\n" . $tableName . '...';
    foreach ($table as $query) {
        $res = $adodb->Execute($query);
        if (! $res) {
            die($adodb->ErrorMsg() . "\n" . $query);
        }
    }
    
    echo 'Done.';
}

echo "\n\n" . 'Unimod update complete' . "\n\n";

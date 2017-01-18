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
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Reader\MgfReader;
use pgb_liv\crowdsource\Preprocessor\DatabasePreprocessor;
use pgb_liv\crowdsource\Preprocessor\RawPreprocessor;
use pgb_liv\crowdsource\Preprocessor\WorkUnitPreprocessor;

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

echo 'Starting: ' . date('r') . PHP_EOL;
$jobId = $adodb->GetOne('SELECT `id` FROM `job_queue` WHERE `status` = \'preprocessing\'');

if ($jobId !== null) {
    die('Exiting. Job running: ' . $jobId . PHP_EOL);
}

$job = $adodb->GetRow('SELECT `id`, `database_file`, `raw_file` FROM `job_queue` WHERE `status` = \'new\' ORDER BY `job_time` ASC');
$adodb->Execute('UPDATE `job_queue` SET `status` = \'preprocessing\', `old_status` = \'new\' WHERE `id` = '.$job['id']);

echo 'Pre-processing job: ' . $job['id'] . PHP_EOL;
echo 'Pre-processing database: ' . $job['database_file'] . PHP_EOL;

$fastaParser = new FastaReader($job['database_file']);

$databaseProcessor = new DatabasePreprocessor($adodb, $fastaParser, (int) $job['id']);
//$databaseProcessor->process();

$fastaParser = null;
$databaseProcessor = null;

echo 'Pre-processing raw data: ' . $job['raw_file'] . PHP_EOL;

$mgfParser = new MgfReader($job['raw_file']);

$rawProcessor = new RawPreprocessor($adodb, $mgfParser, (int) $job['id']);
$rawProcessor->setMs2PeakCount(50);
//$rawProcessor->process();

$mgfParser = null;
$rawProcessor = null;

echo 'Pre-processing work units.' . PHP_EOL;

$workProcessor = new WorkUnitPreprocessor($adodb, (int) $job['id']);
//$workProcessor->process();

$workProcessor = null;

$adodb->Execute('UPDATE `job_queue` SET `status` = \'preprocessed\', `old_status` = \'preprocessing\' WHERE `id` = '.$job['id']);

echo 'Finished: ' . date('r') . PHP_EOL;

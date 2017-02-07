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
use pgb_liv\crowdsource\Preprocessor\Phase1Preprocessor;
use pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor;
use pgb_liv\crowdsource\Preprocessor\Phase3Preprocessor;

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

echo 'Starting: ' . date('r') . PHP_EOL;
$jobId = $adodb->GetOne('SELECT `id` FROM `job_queue` WHERE `state` = \'PREPARING\'');

if ($jobId !== null) {
    die('Exiting. Job running: ' . $jobId . PHP_EOL);
}

$job = $adodb->GetRow('SELECT `id`, `phase` FROM `job_queue` WHERE `state` = \'DONE\' ORDER BY `job_time` ASC');
$phase = (int) $job['phase'];
$jobId = (int) $job['id'];

if (empty($job)) {
    die('Exit. Nothing to do.');
}

echo 'Pre-processing job: ' . $job['id'] . ' Phase: ' . $phase . PHP_EOL;
echo 'Started: ' . date('r') . PHP_EOL;

if ($phase == 0) {
    $phase1 = new Phase1Preprocessor($adodb, $jobId);
    $phase1->process();
} elseif ($phase == 1) {
    $phase2 = new Phase2Preprocessor($adodb, $jobId);
    $phase2->process();
} elseif ($phase == 2) {
    $phase3 = new Phase3Preprocessor($adodb, $jobId);
    $phase3->process();
}

echo 'Finished: ' . date('r') . PHP_EOL;

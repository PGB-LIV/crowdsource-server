<?php
/**
 * Copyright 2018 University of Liverpool
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
use pgb_liv\crowdsource\Postprocessor\Phase1Postprocessor;

error_reporting(E_ALL);
ini_set('display_errors', true);

chdir(__DIR__);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

$lockDir = DATA_PATH . '/.lock';
$lockFile = $lockDir . '/.PreprocessorLock';

if (! file_exists($lockFile)) {
    if (! is_dir($lockDir)) {
        mkdir($lockDir);
    }

    touch($lockFile);
}

$lock = fopen($lockFile, 'r+');

if (! flock($lock, LOCK_EX | LOCK_NB)) {
    die('Terminating. Process Running.');
}

echo '[' . date('r') . '] Starting preprocessor.' . PHP_EOL;

$job = $adodb->GetRow('SELECT `id`, `phase` FROM `job_queue` WHERE `state` = \'DONE\' ORDER BY `job_time` ASC');

if (empty($job)) {
    die('[' . date('r') . '] Terminating. No jobs awaiting processing.' . PHP_EOL);
}

$phase = (int) $job['phase'];
$jobId = (int) $job['id'];

if (empty($job)) {
    die('[' . date('r') . '] Terminating. Nothing to do.' . PHP_EOL);
}

echo '[' . date('r') . '] Found job: ' . $job['id'] . ' - Phase: ' . $phase . '.' . PHP_EOL;

if ($phase == 0) {
    $phase1 = new Phase1Preprocessor($adodb, $jobId);
    $phase1->process();
} elseif ($phase == 1) {
    $phase1  = new Phase1Postprocessor($adodb, $jobId);    
    echo '[' . date('r') . '] Generating results.' . PHP_EOL;
    $phase1->generateResults();
    
    echo '[' . date('r') . '] Purging temporary data.' . PHP_EOL;
    $phase1->clean();

    echo '[' . date('r') . '] Marking job complete.' . PHP_EOL;
    $phase1->finalise();
}

echo '[' . date('r') . '] Done.' . PHP_EOL;
